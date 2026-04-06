import {
  Controller,
  Post,
  Get,
  Delete,
  Body,
  Param,
  Request,
  UseGuards,
  HttpCode,
  HttpStatus,
} from '@nestjs/common';
import {
  ApiTags,
  ApiBearerAuth,
  ApiOperation,
  ApiResponse,
} from '@nestjs/swagger';
import { JwtAuthGuard } from '../auth/jwt-auth.guard';
import { SanctumService } from './sanctum.service';
import { CreateTokenDto } from './dto/create-token.dto';

@ApiTags('sanctum')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard)
@Controller('auth/tokens')
export class SanctumController {
  constructor(private readonly sanctumService: SanctumService) {}

  @Post()
  @ApiOperation({
    summary: 'Create a personal access token (Sanctum)',
    description:
      'Issues a new long-lived personal access token. ' +
      'The plain-text token is returned **once** – store it securely.',
  })
  @ApiResponse({ status: 201, description: 'Token created' })
  async create(@Request() req: any, @Body() dto: CreateTokenDto) {
    const userId: string = req.user.userId;
    const { plainToken, record } = await this.sanctumService.createToken(
      userId,
      dto,
    );
    return {
      token: plainToken,
      id: record._id,
      name: record.name,
      abilities: record.abilities,
      expiresAt: record.expiresAt,
      createdAt: (record as any).createdAt,
    };
  }

  @Get()
  @ApiOperation({ summary: 'List personal access tokens for the current user' })
  @ApiResponse({ status: 200, description: 'Token list' })
  async list(@Request() req: any) {
    const userId: string = req.user.userId;
    const tokens = await this.sanctumService.listTokens(userId);
    return tokens.map((t) => ({
      id: t._id,
      name: t.name,
      abilities: t.abilities,
      lastUsedAt: t.lastUsedAt,
      expiresAt: t.expiresAt,
      createdAt: (t as any).createdAt,
    }));
  }

  @Delete(':id')
  @HttpCode(HttpStatus.NO_CONTENT)
  @ApiOperation({ summary: 'Revoke a personal access token' })
  @ApiResponse({ status: 204, description: 'Token revoked' })
  async revoke(@Request() req: any, @Param('id') id: string) {
    const userId: string = req.user.userId;
    await this.sanctumService.revokeToken(userId, id);
  }
}
