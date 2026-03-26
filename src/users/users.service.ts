import { Injectable, ConflictException, NotFoundException } from '@nestjs/common';
import { InjectModel } from '@nestjs/mongoose';
import { Model } from 'mongoose';
import * as bcrypt from 'bcrypt';
import { User, UserDocument } from './schemas/user.schema';
import { CreateUserDto } from './dto/create-user.dto';

export interface UserSummary {
  total: number;
  verified: number;
  unverified: number;
  latestUser: Record<string, unknown> | null;
}

@Injectable()
export class UsersService {
  constructor(
    @InjectModel(User.name) private readonly userModel: Model<UserDocument>,
  ) {}

  async create(dto: CreateUserDto): Promise<UserDocument> {
    const existing = await this.userModel.findOne({ email: dto.email }).exec();
    if (existing) {
      throw new ConflictException('Email already registered');
    }
    const hashed = await bcrypt.hash(dto.password, 10);
    const user = new this.userModel({ ...dto, password: hashed });
    return user.save();
  }

  async findByEmail(email: string): Promise<UserDocument | null> {
    return this.userModel
      .findOne({ email: email.toLowerCase() })
      .select('+password')
      .exec();
  }

  async findById(id: string): Promise<UserDocument | null> {
    return this.userModel.findById(id).exec();
  }

  async markEmailVerified(id: string): Promise<void> {
    await this.userModel
      .findByIdAndUpdate(id, { emailVerifiedAt: new Date() })
      .exec();
  }

  async getSummary(): Promise<UserSummary> {
    const [total, verified, latestUser] = await Promise.all([
      this.userModel.countDocuments().exec(),
      this.userModel.countDocuments({ emailVerifiedAt: { $ne: null } }).exec(),
      this.userModel.findOne().sort({ createdAt: -1 }).exec(),
    ]);

    return {
      total,
      verified,
      unverified: total - verified,
      latestUser: latestUser
        ? {
            _id: latestUser._id,
            name: latestUser.name,
            email: latestUser.email,
            emailVerifiedAt: latestUser.emailVerifiedAt,
            createdAt: latestUser.createdAt,
          }
        : null,
    };
  }

  async findPaginated(
    page: number,
    limit: number,
  ): Promise<{ data: UserDocument[]; total: number; page: number; lastPage: number }> {
    const skip = (page - 1) * limit;
    const [data, total] = await Promise.all([
      this.userModel.find().skip(skip).limit(limit).sort({ createdAt: -1 }).exec(),
      this.userModel.countDocuments().exec(),
    ]);
    return { data, total, page, lastPage: Math.ceil(total / limit) };
  }

  /**
   * Bulk insert users (used by queue processor).
   * Accepts an array of plain user objects (name, email, password already hashed).
   */
  async bulkInsert(
    users: Array<{ name: string; email: string; password: string }>,
  ): Promise<void> {
    await this.userModel.insertMany(users, { ordered: false }).catch((err) => {
      // Ignore duplicate key errors so partial inserts succeed
      if (err.code !== 11000) throw err;
    });
  }

  async findUsersByIds(ids: string[]): Promise<UserDocument[]> {
    return this.userModel.find({ _id: { $in: ids } }).exec();
  }
}
