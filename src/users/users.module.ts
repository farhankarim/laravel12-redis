import { Module } from '@nestjs/common';
import { MongooseModule } from '@nestjs/mongoose';
import { BullModule } from '@nestjs/bull';
import { UsersService } from './users.service';
import { UsersController } from './users.controller';
import { User, UserSchema } from './schemas/user.schema';
import { UserRepository } from './repositories/user.repository';
import { USER_REPOSITORY } from './repositories/user.repository.interface';

@Module({
  imports: [
    MongooseModule.forFeature([{ name: User.name, schema: UserSchema }]),
    BullModule.registerQueue({ name: 'user-imports' }),
  ],
  providers: [
    UserRepository,
    { provide: USER_REPOSITORY, useClass: UserRepository },
    UsersService,
  ],
  controllers: [UsersController],
  exports: [UsersService],
})
export class UsersModule {}
