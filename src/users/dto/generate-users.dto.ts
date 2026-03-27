import { ApiProperty } from '@nestjs/swagger';
import { IsInt, IsOptional, IsString, Min, Max } from 'class-validator';
import { Type } from 'class-transformer';

export class GenerateUsersDto {
  @ApiProperty({
    description: 'Total number of users to generate',
    example: 10000,
    default: 10000,
  })
  @IsOptional()
  @Type(() => Number)
  @IsInt()
  @Min(1)
  @Max(1_000_000)
  total?: number = 10000;

  @ApiProperty({
    description: 'Number of users per queue job chunk',
    example: 500,
    default: 500,
  })
  @IsOptional()
  @Type(() => Number)
  @IsInt()
  @Min(1)
  @Max(5000)
  chunkSize?: number = 500;

  @ApiProperty({
    description: 'Run identifier (defaults to a random UUID)',
    example: 'run-2024-01',
    required: false,
  })
  @IsOptional()
  @IsString()
  runId?: string;
}
