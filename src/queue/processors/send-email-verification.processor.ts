import { Processor, Process } from '@nestjs/bull';
import { Job } from 'bull';
import { Logger } from '@nestjs/common';
import { UsersService } from '../../users/users.service';
import { MailService } from '../../mail/mail.service';

export interface SendEmailVerificationChunkJobData {
  userIds: string[];
}

@Processor('email-verifications')
export class SendEmailVerificationProcessor {
  private readonly logger = new Logger(SendEmailVerificationProcessor.name);

  constructor(
    private readonly usersService: UsersService,
    private readonly mailService: MailService,
  ) {}

  @Process('send-verification-chunk')
  async handle(job: Job<SendEmailVerificationChunkJobData>): Promise<void> {
    const { userIds } = job.data;
    const users = await this.usersService.findUsersByIds(userIds);

    let sent = 0;
    let failed = 0;

    for (const user of users) {
      try {
        await this.mailService.sendVerificationEmail(
          user._id.toString(),
          user.email,
          user.name,
        );
        sent++;
      } catch {
        failed++;
      }
    }

    this.logger.log(
      `Email verification chunk processed: ${sent} sent, ${failed} failed (batch size ${userIds.length})`,
    );
  }
}
