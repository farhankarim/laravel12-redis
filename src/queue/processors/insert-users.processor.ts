import { Processor, Process } from '@nestjs/bull';
import { Job } from 'bull';
import { Logger } from '@nestjs/common';
import { UsersService } from '../../users/users.service';

export interface InsertUsersChunkJobData {
  startIndex: number;
  chunkSize: number;
  runId: string;
  passwordHash: string;
}

@Processor('user-imports')
export class InsertUsersProcessor {
  private readonly logger = new Logger(InsertUsersProcessor.name);

  constructor(private readonly usersService: UsersService) {}

  @Process('insert-users-chunk')
  async handle(job: Job<InsertUsersChunkJobData>): Promise<void> {
    const { startIndex, chunkSize, runId, passwordHash } = job.data;

    const users = Array.from({ length: chunkSize }, (_, i) => {
      const index = startIndex + i;
      return {
        name: `User ${index}`,
        email: `user_${runId}_${index}@example.com`,
        password: passwordHash,
      };
    });

    await this.usersService.bulkInsert(users);

    this.logger.log(
      `Inserted chunk [${startIndex} – ${startIndex + chunkSize - 1}] for run ${runId}`,
    );
  }
}
