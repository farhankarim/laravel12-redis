import { TransactionDocument } from '../schemas/transaction.schema';
import { IBaseRepository } from '../../common/interfaces/base-repository.interface';

export interface TransactionPaginatedResult {
  data: TransactionDocument[];
  total: number;
  page: number;
  lastPage: number;
}

export interface TransactionFilters {
  participantId?: string;
  type?: string;
  paymentStatus?: string;
  status?: number;
}

export const TRANSACTION_REPOSITORY = 'TRANSACTION_REPOSITORY';

export interface ITransactionRepository extends IBaseRepository<TransactionDocument> {
  findPaginated(
    page: number,
    limit: number,
    filters?: TransactionFilters,
  ): Promise<TransactionPaginatedResult>;
  findByParticipantId(participantId: string): Promise<TransactionDocument[]>;
}
