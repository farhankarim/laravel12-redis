import {
  Injectable,
  UnauthorizedException,
} from '@nestjs/common';
import { InjectModel } from '@nestjs/mongoose';
import { Model, Types } from 'mongoose';
import * as crypto from 'crypto';
import * as bcrypt from 'bcrypt';
import {
  PersonalAccessToken,
  PersonalAccessTokenDocument,
} from './schemas/personal-access-token.schema';
import { CreateTokenDto } from './dto/create-token.dto';

@Injectable()
export class SanctumService {
  constructor(
    @InjectModel(PersonalAccessToken.name)
    private readonly tokenModel: Model<PersonalAccessTokenDocument>,
  ) {}

  /**
   * Create a new personal access token for a user.
   * Returns the plain-text token (shown once) plus the stored record.
   */
  async createToken(
    userId: string,
    dto: CreateTokenDto,
  ): Promise<{ plainToken: string; record: PersonalAccessTokenDocument }> {
    const plainToken = crypto.randomBytes(40).toString('hex');
    const hashed = await bcrypt.hash(plainToken, 10);

    const record = await this.tokenModel.create({
      userId: new Types.ObjectId(userId),
      name: dto.name,
      token: hashed,
      abilities: dto.abilities ?? ['*'],
      expiresAt: dto.expiresAt ? new Date(dto.expiresAt) : null,
    });

    return { plainToken, record };
  }

  /** List all tokens for a user (token hash is not returned). */
  async listTokens(userId: string): Promise<PersonalAccessTokenDocument[]> {
    return this.tokenModel.find({ userId: new Types.ObjectId(userId) }).exec();
  }

  /** Revoke (delete) a token by its MongoDB id, scoped to the owner. */
  async revokeToken(userId: string, tokenId: string): Promise<void> {
    await this.tokenModel
      .findOneAndDelete({
        _id: new Types.ObjectId(tokenId),
        userId: new Types.ObjectId(userId),
      })
      .exec();
  }

  /**
   * Validate a plain-text token from an incoming request.
   * Returns the userId string if valid, throws otherwise.
   */
  async validateToken(plainToken: string): Promise<string> {
    // Fetch all tokens (we need the hash field) – in production consider indexing on a prefix
    const records = await this.tokenModel.find().select('+token').exec();

    for (const record of records) {
      const now = new Date();
      if (record.expiresAt && record.expiresAt < now) continue;

      const match = await bcrypt.compare(plainToken, record.token);
      if (match) {
        // Update lastUsedAt in the background
        void this.tokenModel
          .findByIdAndUpdate(record._id, { lastUsedAt: now })
          .exec();
        return record.userId.toString();
      }
    }

    throw new UnauthorizedException('Invalid or expired personal access token');
  }
}
