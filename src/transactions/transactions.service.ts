import { Injectable, Inject, NotFoundException } from '@nestjs/common';
import { TransactionDocument } from './schemas/transaction.schema';
import { CreateTransactionDto } from './dto/create-transaction.dto';
import { UpdateTransactionDto } from './dto/update-transaction.dto';
import {
  ITransactionRepository,
  TRANSACTION_REPOSITORY,
  TransactionFilters,
  TransactionPaginatedResult,
} from './repositories/transaction.repository.interface';

@Injectable()
export class TransactionsService {
  constructor(
    @Inject(TRANSACTION_REPOSITORY)
    private readonly transactionRepository: ITransactionRepository,
  ) {}

  async create(dto: CreateTransactionDto): Promise<TransactionDocument> {
    return this.transactionRepository.create(dto as unknown as Partial<TransactionDocument>);
  }

  async findAll(): Promise<TransactionDocument[]> {
    return this.transactionRepository.findAll();
  }

  async findPaginated(
    page: number,
    limit: number,
    filters: TransactionFilters = {},
  ): Promise<TransactionPaginatedResult> {
    return this.transactionRepository.findPaginated(page, limit, filters);
  }

  async findById(id: string): Promise<TransactionDocument> {
    const transaction = await this.transactionRepository.findById(id);
    if (!transaction) {
      throw new NotFoundException(`Transaction #${id} not found`);
    }
    return transaction;
  }

  async findByParticipantId(participantId: string): Promise<TransactionDocument[]> {
    return this.transactionRepository.findByParticipantId(participantId);
  }

  async update(id: string, dto: UpdateTransactionDto): Promise<TransactionDocument> {
    const updated = await this.transactionRepository.update(
      id,
      dto as unknown as Partial<TransactionDocument>,
    );
    if (!updated) {
      throw new NotFoundException(`Transaction #${id} not found`);
    }
    return updated;
  }

  async remove(id: string): Promise<void> {
    const deleted = await this.transactionRepository.delete(id);
    if (!deleted) {
      throw new NotFoundException(`Transaction #${id} not found`);
    }
  }
}
