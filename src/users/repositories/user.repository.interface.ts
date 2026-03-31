import { UserDocument } from '../schemas/user.schema';
import { IBaseRepository } from '../../common/interfaces/base-repository.interface';

export interface PaginatedResult<T> {
  data: T[];
  total: number;
  page: number;
  lastPage: number;
}

export interface UserSummary {
  total: number;
  verified: number;
  unverified: number;
  latestUser: Record<string, unknown> | null;
}

export const USER_REPOSITORY = 'USER_REPOSITORY';

export interface IUserRepository extends IBaseRepository<UserDocument> {
  findByEmail(email: string): Promise<UserDocument | null>;
  markEmailVerified(id: string): Promise<void>;
  getSummary(): Promise<UserSummary>;
  findPaginated(page: number, limit: number): Promise<PaginatedResult<UserDocument>>;
  bulkInsert(users: Array<{ name: string; email: string; password: string }>): Promise<void>;
  findByIds(ids: string[]): Promise<UserDocument[]>;
}
