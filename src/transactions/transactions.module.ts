import { Module } from '@nestjs/common';
import { MongooseModule } from '@nestjs/mongoose';
import { Transaction, TransactionSchema } from './schemas/transaction.schema';
import { TransactionRepository } from './repositories/transaction.repository';
import { TRANSACTION_REPOSITORY } from './repositories/transaction.repository.interface';
import { TransactionsService } from './transactions.service';
import { TransactionsController } from './transactions.controller';

@Module({
  imports: [
    MongooseModule.forFeature([
      { name: Transaction.name, schema: TransactionSchema },
    ]),
  ],
  providers: [
    TransactionRepository,
    { provide: TRANSACTION_REPOSITORY, useClass: TransactionRepository },
    TransactionsService,
  ],
  controllers: [TransactionsController],
  exports: [TransactionsService],
})
export class TransactionsModule {}
