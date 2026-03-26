import { ApiProperty } from '@nestjs/swagger';
import { IsInt, IsOptional, Min, Max } from 'class-validator';
import { Type } from 'class-transformer';

export class QueueEmailVerificationsDto {
  @ApiProperty({
    description: 'Maximum number of users to process (0 = all unverified)',
    example: 0,
    default: 0,
  })
  @IsOptional()
  @Type(() => Number)
  @IsInt()
  @Min(0)
  limit?: number = 0;

  @ApiProperty({
    description: 'Number of users per email-verification job',
    example: 100,
    default: 100,
  })
  @IsOptional()
  @Type(() => Number)
  @IsInt()
  @Min(1)
  @Max(1000)
  chunkSize?: number = 100;
}
