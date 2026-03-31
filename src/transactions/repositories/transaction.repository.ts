import { Injectable } from '@nestjs/common';
import { InjectModel } from '@nestjs/mongoose';
import { FilterQuery, Model } from 'mongoose';
import { Transaction, TransactionDocument } from '../schemas/transaction.schema';
import {
  ITransactionRepository,
  TransactionFilters,
  TransactionPaginatedResult,
} from './transaction.repository.interface';

@Injectable()
export class TransactionRepository implements ITransactionRepository {
  constructor(
    @InjectModel(Transaction.name)
    private readonly transactionModel: Model<TransactionDocument>,
  ) {}

  async findById(id: string): Promise<TransactionDocument | null> {
    return this.transactionModel.findById(id).exec();
  }

  async findAll(): Promise<TransactionDocument[]> {
    return this.transactionModel.find().sort({ createdAt: -1 }).exec();
  }

  async create(entity: Partial<TransactionDocument>): Promise<TransactionDocument> {
    const transaction = new this.transactionModel(entity);
    return transaction.save();
  }

  async update(
    id: string,
    entity: Partial<TransactionDocument>,
  ): Promise<TransactionDocument | null> {
    return this.transactionModel
      .findByIdAndUpdate(id, entity, { new: true })
      .exec();
  }

  async delete(id: string): Promise<boolean> {
    const result = await this.transactionModel.findByIdAndDelete(id).exec();
    return result !== null;
  }

  async findPaginated(
    page: number,
    limit: number,
    filters: TransactionFilters = {},
  ): Promise<TransactionPaginatedResult> {
    const skip = (page - 1) * limit;
    const query: FilterQuery<TransactionDocument> = {};

    if (filters.participantId) query.participantId = filters.participantId;
    if (filters.type) query.type = filters.type;
    if (filters.paymentStatus) query.paymentStatus = filters.paymentStatus;
    if (filters.status !== undefined) query.status = filters.status;

    const [data, total] = await Promise.all([
      this.transactionModel
        .find(query)
        .skip(skip)
        .limit(limit)
        .sort({ createdAt: -1 })
        .exec(),
      this.transactionModel.countDocuments(query).exec(),
    ]);

    return { data, total, page, lastPage: Math.ceil(total / limit) };
  }

  async findByParticipantId(participantId: string): Promise<TransactionDocument[]> {
    return this.transactionModel.find({ participantId }).sort({ createdAt: -1 }).exec();
  }
}
