import { Prop, Schema, SchemaFactory } from '@nestjs/mongoose';
import { Document, Types } from 'mongoose';

export type PersonalAccessTokenDocument = PersonalAccessToken &
  Document & { createdAt: Date; updatedAt: Date };

@Schema({ timestamps: true })
export class PersonalAccessToken {
  @Prop({ type: Types.ObjectId, required: true, index: true })
  userId: Types.ObjectId;

  @Prop({ required: true })
  name: string;

  /** The hashed token stored in the database */
  @Prop({ required: true, select: false })
  token: string;

  /** Ability list (e.g. ["read", "write"] or ["*"] for all) */
  @Prop({ type: [String], default: ['*'] })
  abilities: string[];

  @Prop({ type: Date, default: null })
  lastUsedAt: Date | null;

  @Prop({ type: Date, default: null })
  expiresAt: Date | null;
}

export const PersonalAccessTokenSchema =
  SchemaFactory.createForClass(PersonalAccessToken);
