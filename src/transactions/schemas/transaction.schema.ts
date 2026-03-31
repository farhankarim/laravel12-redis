import { Prop, Schema, SchemaFactory } from '@nestjs/mongoose';
import { Document } from 'mongoose';

export type TransactionType = 'loan taken' | 'donation' | 'loan returned';

export type TransactionDocument = Transaction & Document & { createdAt: Date; updatedAt: Date };

@Schema({ timestamps: true })
export class Transaction {
  @Prop({ required: true })
  participantId: string;

  @Prop({ type: Date, default: null })
  date: Date | null;

  @Prop({
    type: String,
    enum: ['loan taken', 'donation', 'loan returned'],
    default: null,
  })
  type: TransactionType | null;

  @Prop({ type: String, default: null })
  amount: string | null;

  @Prop({ type: Number, default: 1 })
  status: number;

  @Prop({ type: Date, default: null })
  expectedReturnDate: Date | null;

  @Prop({ type: Number, default: null })
  repaymentAmount: number | null;

  @Prop({ type: String, default: null })
  tenure: string | null;

  @Prop({ type: String, default: null })
  paymentStatus: string | null;
}

export const TransactionSchema = SchemaFactory.createForClass(Transaction);
