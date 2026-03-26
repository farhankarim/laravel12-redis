import {
  Controller,
  Post,
  Get,
  Body,
  UseGuards,
  HttpCode,
  HttpStatus,
  Query,
} from '@nestjs/common';
import {
  ApiTags,
  ApiBearerAuth,
  ApiOperation,
  ApiResponse,
} from '@nestjs/swagger';
import { InjectQueue } from '@nestjs/bull';
import { Queue } from 'bull';
import { InjectModel } from '@nestjs/mongoose';
import { Model } from 'mongoose';
import { JwtAuthGuard } from '../auth/jwt-auth.guard';
import { QueueEmailVerificationsDto } from './dto/queue-email-verifications.dto';
import { User, UserDocument } from '../users/schemas/user.schema';

@ApiTags('queue')
@Controller('queue')
@UseGuards(JwtAuthGuard)
@ApiBearerAuth()
export class QueueController {
  constructor(
    @InjectQueue('user-imports') private readonly userImportsQueue: Queue,
    @InjectQueue('email-verifications') private readonly emailVerificationsQueue: Queue,
    @InjectModel(User.name) private readonly userModel: Model<UserDocument>,
  ) {}

  @Get('stats')
  @ApiOperation({ summary: 'Get raw Bull queue job counts' })
  @ApiResponse({ status: 200, description: 'Queue statistics' })
  async stats() {
    const [userImportsCounts, emailVerificationCounts] = await Promise.all([
      this.userImportsQueue.getJobCounts(),
      this.emailVerificationsQueue.getJobCounts(),
    ]);

    return {
      queues: {
        'user-imports': userImportsCounts,
        'email-verifications': emailVerificationCounts,
      },
    };
  }

  @Post('email-verifications')
  @HttpCode(HttpStatus.ACCEPTED)
  @ApiOperation({
    summary: 'Queue email verification jobs for all unverified users',
    description:
      'Finds unverified users and dispatches SendEmailVerificationChunk jobs to the email-verifications queue.',
  })
  @ApiResponse({ status: 202, description: 'Email verification jobs dispatched' })
  async queueEmailVerifications(@Body() dto: QueueEmailVerificationsDto) {
    const chunkSize = dto.chunkSize ?? 100;
    const limit = dto.limit ?? 0;

    const query = this.userModel.find({ emailVerifiedAt: null }).select('_id');
    if (limit > 0) query.limit(limit);

    const users = await query.exec();
    if (users.length === 0) {
      return { message: 'No unverified users found', jobsDispatched: 0 };
    }

    const jobs: Array<{ name: string; data: object }> = [];
    for (let i = 0; i < users.length; i += chunkSize) {
      const chunk = users.slice(i, i + chunkSize).map((u) => u._id.toString());
      jobs.push({ name: 'send-verification-chunk', data: { userIds: chunk } });
    }

    await this.emailVerificationsQueue.addBulk(jobs);

    return {
      message: 'Email verification jobs dispatched',
      totalUsers: users.length,
      chunkSize,
      jobsDispatched: jobs.length,
    };
  }
}
