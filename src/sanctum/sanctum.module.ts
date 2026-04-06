import { Module } from '@nestjs/common';
import { MongooseModule } from '@nestjs/mongoose';
import { SanctumService } from './sanctum.service';
import { SanctumController } from './sanctum.controller';
import {
  PersonalAccessToken,
  PersonalAccessTokenSchema,
} from './schemas/personal-access-token.schema';

@Module({
  imports: [
    MongooseModule.forFeature([
      {
        name: PersonalAccessToken.name,
        schema: PersonalAccessTokenSchema,
      },
    ]),
  ],
  providers: [SanctumService],
  controllers: [SanctumController],
  exports: [SanctumService],
})
export class SanctumModule {}
