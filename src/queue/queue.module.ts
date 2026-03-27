import { Module } from '@nestjs/common';
import { BullModule } from '@nestjs/bull';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { MongooseModule } from '@nestjs/mongoose';
import { QueueController } from './queue.controller';
import { InsertUsersProcessor } from './processors/insert-users.processor';
import { SendEmailVerificationProcessor } from './processors/send-email-verification.processor';
import { UsersModule } from '../users/users.module';
import { MailModule } from '../mail/mail.module';
import { User, UserSchema } from '../users/schemas/user.schema';

@Module({
  imports: [
    // Register Bull with Redis configuration
    BullModule.forRootAsync({
      imports: [ConfigModule],
      inject: [ConfigService],
      useFactory: (config: ConfigService) => ({
        redis: {
          host: config.get<string>('REDIS_HOST', '127.0.0.1'),
          port: config.get<number>('REDIS_PORT', 6379),
          password: config.get<string>('REDIS_PASSWORD') || undefined,
        },
      }),
    }),
    BullModule.registerQueue(
      { name: 'user-imports' },
      { name: 'email-verifications' },
    ),
    MongooseModule.forFeature([{ name: User.name, schema: UserSchema }]),
    UsersModule,
    MailModule,
  ],
  controllers: [QueueController],
  providers: [InsertUsersProcessor, SendEmailVerificationProcessor],
  exports: [BullModule],
})
export class QueueModule {}
