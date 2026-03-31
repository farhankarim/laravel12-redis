import { Injectable } from '@nestjs/common';
import { InjectModel } from '@nestjs/mongoose';
import { Model } from 'mongoose';
import { User, UserDocument } from '../schemas/user.schema';
import { IUserRepository, PaginatedResult, UserSummary } from './user.repository.interface';

@Injectable()
export class UserRepository implements IUserRepository {
  constructor(
    @InjectModel(User.name) private readonly userModel: Model<UserDocument>,
  ) {}

  async findById(id: string): Promise<UserDocument | null> {
    return this.userModel.findById(id).exec();
  }

  async findAll(): Promise<UserDocument[]> {
    return this.userModel.find().exec();
  }

  async create(entity: Partial<UserDocument>): Promise<UserDocument> {
    const user = new this.userModel(entity);
    return user.save();
  }

  async update(id: string, entity: Partial<UserDocument>): Promise<UserDocument | null> {
    return this.userModel.findByIdAndUpdate(id, entity, { new: true }).exec();
  }

  async delete(id: string): Promise<boolean> {
    const result = await this.userModel.findByIdAndDelete(id).exec();
    return result !== null;
  }

  async findByEmail(email: string): Promise<UserDocument | null> {
    return this.userModel
      .findOne({ email: email.toLowerCase() })
      .select('+password')
      .exec();
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
  ): Promise<PaginatedResult<UserDocument>> {
    const skip = (page - 1) * limit;
    const [data, total] = await Promise.all([
      this.userModel.find().skip(skip).limit(limit).sort({ createdAt: -1 }).exec(),
      this.userModel.countDocuments().exec(),
    ]);
    return { data, total, page, lastPage: Math.ceil(total / limit) };
  }

  async bulkInsert(
    users: Array<{ name: string; email: string; password: string }>,
  ): Promise<void> {
    await this.userModel.insertMany(users, { ordered: false }).catch((err) => {
      if (err.code !== 11000) throw err;
    });
  }

  async findByIds(ids: string[]): Promise<UserDocument[]> {
    return this.userModel.find({ _id: { $in: ids } }).exec();
  }
}
