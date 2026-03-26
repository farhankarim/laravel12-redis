import {
  Injectable,
  Logger,
  OnModuleInit,
  OnModuleDestroy,
  UnauthorizedException,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { JwtService } from '@nestjs/jwt';
import { InjectQueue } from '@nestjs/bull';
import { Queue } from 'bull';
import Redis from 'ioredis';

export interface QueueRedisStats {
  name: string;
  pending: number;
  reserved: number;
  delayed: number;
}

export interface QueueSummary {
  queues: QueueRedisStats[];
  totals: {
    pending: number;
    reserved: number;
    delayed: number;
    failed: number;
    completed: number;
  };
  cachedAt: string;
}

export interface UserSummaryCache {
  total: number;
  verified: number;
  unverified: number;
  latestUser: object | null;
  cachedAt: string;
}

@Injectable()
export class DashboardService implements OnModuleInit, OnModuleDestroy {
  private readonly logger = new Logger(DashboardService.name);
  private redisClient: Redis;
  private redisSubscriber: Redis;

  private readonly QUEUE_SUMMARY_KEY = 'dashboard:queue_summary';
  private readonly USERS_SUMMARY_KEY = 'dashboard:users_summary';
  private readonly REFRESH_CHANNEL = 'dashboard.summary.refresh';
  private readonly UPDATED_CHANNEL = 'dashboard.summary.updated';
  private readonly TTL = 3600; // 1 hour

  // Callback invoked when a pub/sub refresh arrives
  onRefreshCallback: (() => Promise<void>) | null = null;

  constructor(
    private readonly config: ConfigService,
    private readonly jwtService: JwtService,
    @InjectQueue('user-imports') private readonly userImportsQueue: Queue,
    @InjectQueue('email-verifications') private readonly emailVerificationsQueue: Queue,
  ) {}

  onModuleInit() {
    const redisOpts = {
      host: this.config.get<string>('REDIS_HOST', '127.0.0.1'),
      port: this.config.get<number>('REDIS_PORT', 6379),
      password: this.config.get<string>('REDIS_PASSWORD') || undefined,
    };
    this.redisClient = new Redis(redisOpts);
    this.redisSubscriber = new Redis(redisOpts);

    // Subscribe to refresh channel
    this.redisSubscriber.subscribe(this.REFRESH_CHANNEL, (err) => {
      if (err) this.logger.error(`Redis subscribe error: ${err.message}`);
    });

    this.redisSubscriber.on('message', async (channel, _message) => {
      if (channel === this.REFRESH_CHANNEL && this.onRefreshCallback) {
        await this.onRefreshCallback();
      }
    });
  }

  onModuleDestroy() {
    this.redisClient?.disconnect();
    this.redisSubscriber?.disconnect();
  }

  // ── Queue Summary ─────────────────────────────────────────────────────────

  async getQueueSummary(): Promise<QueueSummary> {
    // Try cache first
    const cached = await this.redisClient.get(this.QUEUE_SUMMARY_KEY);
    if (cached) {
      try {
        return JSON.parse(cached) as QueueSummary;
      } catch {
        // fall through to rebuild
      }
    }
    return this.buildAndCacheQueueSummary();
  }

  async buildAndCacheQueueSummary(): Promise<QueueSummary> {
    const queueNames = this.config
      .get<string>('QUEUE_NAMES', 'default,user-imports,email-verifications')
      .split(',')
      .map((n) => n.trim());

    // Get Bull job counts from both managed queues
    const [userImportsCounts, emailVerificationCounts] = await Promise.all([
      this.userImportsQueue.getJobCounts(),
      this.emailVerificationsQueue.getJobCounts(),
    ]);

    const bullCountsMap: Record<string, typeof userImportsCounts> = {
      'user-imports': userImportsCounts,
      'email-verifications': emailVerificationCounts,
    };

    // For each queue name, get Redis raw key counts (matching Laravel's approach)
    const queues: QueueRedisStats[] = await Promise.all(
      queueNames.map(async (name): Promise<QueueRedisStats> => {
        const [pending, reserved, delayed] = await Promise.all([
          this.redisClient.llen(`bull:${name}:wait`),
          this.redisClient.zcard(`bull:${name}:active`),
          this.redisClient.zcard(`bull:${name}:delayed`),
        ]);
        return { name, pending, reserved, delayed };
      }),
    );

    const allFailed =
      (userImportsCounts.failed || 0) + (emailVerificationCounts.failed || 0);
    const allCompleted =
      (userImportsCounts.completed || 0) + (emailVerificationCounts.completed || 0);

    const summary: QueueSummary = {
      queues,
      totals: {
        pending: queues.reduce((s, q) => s + q.pending, 0),
        reserved: queues.reduce((s, q) => s + q.reserved, 0),
        delayed: queues.reduce((s, q) => s + q.delayed, 0),
        failed: allFailed,
        completed: allCompleted,
      },
      cachedAt: new Date().toISOString(),
    };

    await this.redisClient.setex(this.QUEUE_SUMMARY_KEY, this.TTL, JSON.stringify(summary));
    await this.redisClient.publish(this.UPDATED_CHANNEL, JSON.stringify({ type: 'queue' }));

    return summary;
  }

  // ── Users Summary ─────────────────────────────────────────────────────────

  async getUsersSummary(): Promise<UserSummaryCache | null> {
    const cached = await this.redisClient.get(this.USERS_SUMMARY_KEY);
    if (cached) {
      try {
        return JSON.parse(cached) as UserSummaryCache;
      } catch {
        return null;
      }
    }
    return null;
  }

  async cacheUsersSummary(summary: Omit<UserSummaryCache, 'cachedAt'>): Promise<UserSummaryCache> {
    const full: UserSummaryCache = { ...summary, cachedAt: new Date().toISOString() };
    await this.redisClient.setex(this.USERS_SUMMARY_KEY, this.TTL, JSON.stringify(full));
    await this.redisClient.publish(this.UPDATED_CHANNEL, JSON.stringify({ type: 'users' }));
    return full;
  }

  // ── Pub/Sub helpers ───────────────────────────────────────────────────────

  async publishRefresh(): Promise<void> {
    await this.redisClient.publish(this.REFRESH_CHANNEL, JSON.stringify({ ts: Date.now() }));
  }

  // ── Email Verification ────────────────────────────────────────────────────

  async verifyEmailToken(token: string): Promise<{ message: string }> {
    let payload: { sub: string; email: string; type: string };
    try {
      payload = this.jwtService.verify(token, {
        secret: this.config.get<string>('JWT_SECRET', 'changeme'),
      });
    } catch {
      throw new UnauthorizedException('Invalid or expired verification token');
    }

    if (payload.type !== 'email-verification') {
      throw new UnauthorizedException('Invalid token type');
    }

    // Invalidate cache so dashboard shows updated verified count
    await this.redisClient.del(this.USERS_SUMMARY_KEY);

    return {
      message: `Email ${payload.email} verified successfully. You may now log in.`,
    };
  }
}
