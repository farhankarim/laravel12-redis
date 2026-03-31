import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import {
  IsDateString,
  IsEnum,
  IsIn,
  IsNotEmpty,
  IsNumber,
  IsOptional,
  IsString,
} from 'class-validator';

export class CreateTransactionDto {
  @ApiProperty({ example: '60d21b4667d0d8992e610c85' })
  @IsNotEmpty()
  @IsString()
  participantId: string;

  @ApiPropertyOptional({ example: '2024-01-15' })
  @IsOptional()
  @IsDateString()
  date?: string;

  @ApiPropertyOptional({ enum: ['loan taken', 'donation', 'loan returned'] })
  @IsOptional()
  @IsEnum(['loan taken', 'donation', 'loan returned'])
  type?: 'loan taken' | 'donation' | 'loan returned';

  @ApiPropertyOptional({ example: '5000.00' })
  @IsOptional()
  @IsString()
  amount?: string;

  @ApiPropertyOptional({ example: 1, default: 1 })
  @IsOptional()
  @IsNumber()
  status?: number;

  @ApiPropertyOptional({ example: '2024-07-15' })
  @IsOptional()
  @IsDateString()
  expectedReturnDate?: string;

  @ApiPropertyOptional({ example: 5500 })
  @IsOptional()
  @IsNumber()
  repaymentAmount?: number;

  @ApiPropertyOptional({ example: '6 months' })
  @IsOptional()
  @IsString()
  tenure?: string;

  @ApiPropertyOptional({ example: 'pending', description: 'pending | paid | overdue' })
  @IsOptional()
  @IsString()
  paymentStatus?: string;
}
