import { Module } from '@nestjs/common';
import { JwtModule } from '@nestjs/jwt';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { BullModule } from '@nestjs/bull';
import { DashboardService } from './dashboard.service';
import { DashboardGateway } from './dashboard.gateway';
import { DashboardController } from './dashboard.controller';
import { UsersModule } from '../users/users.module';

@Module({
  imports: [
    JwtModule.registerAsync({
      imports: [ConfigModule],
      inject: [ConfigService],
      useFactory: (config: ConfigService) => ({
        secret: config.get<string>('JWT_SECRET', 'changeme'),
      }),
    }),
    BullModule.registerQueue(
      { name: 'user-imports' },
      { name: 'email-verifications' },
    ),
    UsersModule,
  ],
  providers: [DashboardService, DashboardGateway],
  controllers: [DashboardController],
})
export class DashboardModule {}
