import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { IsString, IsNotEmpty, IsArray, IsOptional, IsDateString } from 'class-validator';

export class CreateTokenDto {
  @ApiProperty({ example: 'my-api-token', description: 'Human-readable token name' })
  @IsString()
  @IsNotEmpty()
  name: string;

  @ApiPropertyOptional({
    example: ['read', 'write'],
    description: 'Token abilities/scopes. Use ["*"] for full access.',
    type: [String],
  })
  @IsArray()
  @IsString({ each: true })
  @IsOptional()
  abilities?: string[];

  @ApiPropertyOptional({
    example: '2026-12-31T23:59:59Z',
    description: 'Optional expiry date (ISO 8601)',
  })
  @IsDateString()
  @IsOptional()
  expiresAt?: string;
}
