import { Injectable, ConflictException, Inject } from '@nestjs/common';
import * as bcrypt from 'bcrypt';
import { UserDocument } from './schemas/user.schema';
import { CreateUserDto } from './dto/create-user.dto';
import {
  IUserRepository,
  USER_REPOSITORY,
  UserSummary,
  PaginatedResult,
} from './repositories/user.repository.interface';

@Injectable()
export class UsersService {
  constructor(
    @Inject(USER_REPOSITORY) private readonly userRepository: IUserRepository,
  ) {}

  async create(dto: CreateUserDto): Promise<UserDocument> {
    const existing = await this.userRepository.findByEmail(dto.email);
    if (existing) {
      throw new ConflictException('Email already registered');
    }
    const hashed = await bcrypt.hash(dto.password, 10);
    return this.userRepository.create({ ...dto, password: hashed });
  }

  async findByEmail(email: string): Promise<UserDocument | null> {
    return this.userRepository.findByEmail(email);
  }

  async findById(id: string): Promise<UserDocument | null> {
    return this.userRepository.findById(id);
  }

  async markEmailVerified(id: string): Promise<void> {
    return this.userRepository.markEmailVerified(id);
  }

  async getSummary(): Promise<UserSummary> {
    return this.userRepository.getSummary();
  }

  async findPaginated(
    page: number,
    limit: number,
  ): Promise<PaginatedResult<UserDocument>> {
    return this.userRepository.findPaginated(page, limit);
  }

  /**
   * Bulk insert users (used by queue processor).
   * Accepts an array of plain user objects (name, email, password already hashed).
   */
  async bulkInsert(
    users: Array<{ name: string; email: string; password: string }>,
  ): Promise<void> {
    return this.userRepository.bulkInsert(users);
  }

  async findUsersByIds(ids: string[]): Promise<UserDocument[]> {
    return this.userRepository.findByIds(ids);
  }
}
