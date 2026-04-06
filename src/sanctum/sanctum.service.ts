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
   * The plain-text token is in the format `{mongoId}|{secret}` (Sanctum-style),
   * allowing O(1) lookup by ID during validation.
   * Returns the plain-text token (shown once) plus the stored record.
   */
  async createToken(
    userId: string,
    dto: CreateTokenDto,
  ): Promise<{ plainToken: string; record: PersonalAccessTokenDocument }> {
    const secret = crypto.randomBytes(40).toString('hex');
    const hashed = await bcrypt.hash(secret, 10);

    const record = await this.tokenModel.create({
      userId: new Types.ObjectId(userId),
      name: dto.name,
      token: hashed,
      abilities: dto.abilities ?? ['*'],
      expiresAt: dto.expiresAt ? new Date(dto.expiresAt) : null,
    });

    // Plain token format: `{id}|{secret}` – mirrors Laravel Sanctum's convention.
    const plainToken = `${record._id.toString()}|${secret}`;
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
   * Token format: `{mongoId}|{secret}`.  The ID is used for an O(1) index
   * lookup; bcrypt verification is then run only on that single record.
   */
  async validateToken(plainToken: string): Promise<string> {
    const separatorIndex = plainToken.indexOf('|');
    if (separatorIndex === -1) {
      throw new UnauthorizedException('Invalid personal access token format');
    }

    const tokenId = plainToken.slice(0, separatorIndex);
    const secret = plainToken.slice(separatorIndex + 1);

    if (!Types.ObjectId.isValid(tokenId)) {
      throw new UnauthorizedException('Invalid personal access token');
    }

    const record = await this.tokenModel
      .findById(tokenId)
      .select('+token')
      .exec();

    if (!record) {
      throw new UnauthorizedException('Invalid or expired personal access token');
    }

    const now = new Date();
    if (record.expiresAt && record.expiresAt < now) {
      throw new UnauthorizedException('Personal access token has expired');
    }

    const match = await bcrypt.compare(secret, record.token);
    if (!match) {
      throw new UnauthorizedException('Invalid personal access token');
    }

    // Update lastUsedAt in the background
    void this.tokenModel
      .findByIdAndUpdate(record._id, { lastUsedAt: now })
      .exec();

    return record.userId.toString();
  }
}
