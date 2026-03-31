import {
  Controller,
  Get,
  Post,
  Put,
  Delete,
  Body,
  Param,
  Query,
  UseGuards,
  HttpCode,
  HttpStatus,
} from '@nestjs/common';
import {
  ApiTags,
  ApiBearerAuth,
  ApiOperation,
  ApiResponse,
  ApiQuery,
  ApiParam,
} from '@nestjs/swagger';
import { JwtAuthGuard } from '../auth/jwt-auth.guard';
import { TransactionsService } from './transactions.service';
import { CreateTransactionDto } from './dto/create-transaction.dto';
import { UpdateTransactionDto } from './dto/update-transaction.dto';

@ApiTags('transactions')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard)
@Controller('transactions')
export class TransactionsController {
  constructor(private readonly transactionsService: TransactionsService) {}

  @Post()
  @ApiOperation({ summary: 'Create a transaction' })
  @ApiResponse({ status: 201, description: 'Transaction created successfully' })
  async create(@Body() dto: CreateTransactionDto) {
    return this.transactionsService.create(dto);
  }

  @Get()
  @ApiOperation({ summary: 'List transactions with pagination and filters' })
  @ApiQuery({ name: 'page', required: false, type: Number, example: 1 })
  @ApiQuery({ name: 'limit', required: false, type: Number, example: 20 })
  @ApiQuery({ name: 'participantId', required: false, type: String })
  @ApiQuery({ name: 'type', required: false, enum: ['loan taken', 'donation', 'loan returned'] })
  @ApiQuery({ name: 'paymentStatus', required: false, type: String })
  @ApiQuery({ name: 'status', required: false, type: Number })
  async findAll(
    @Query('page') page = 1,
    @Query('limit') limit = 20,
    @Query('participantId') participantId?: string,
    @Query('type') type?: string,
    @Query('paymentStatus') paymentStatus?: string,
    @Query('status') status?: number,
  ) {
    return this.transactionsService.findPaginated(+page, +limit, {
      participantId,
      type,
      paymentStatus,
      status: status !== undefined ? +status : undefined,
    });
  }

  @Get(':id')
  @ApiOperation({ summary: 'Get a transaction by ID' })
  @ApiParam({ name: 'id', description: 'Transaction MongoDB ObjectId' })
  @ApiResponse({ status: 200, description: 'Transaction found' })
  @ApiResponse({ status: 404, description: 'Transaction not found' })
  async findOne(@Param('id') id: string) {
    return this.transactionsService.findById(id);
  }

  @Put(':id')
  @ApiOperation({ summary: 'Update a transaction' })
  @ApiParam({ name: 'id', description: 'Transaction MongoDB ObjectId' })
  @ApiResponse({ status: 200, description: 'Transaction updated successfully' })
  @ApiResponse({ status: 404, description: 'Transaction not found' })
  async update(@Param('id') id: string, @Body() dto: UpdateTransactionDto) {
    return this.transactionsService.update(id, dto);
  }

  @Delete(':id')
  @HttpCode(HttpStatus.NO_CONTENT)
  @ApiOperation({ summary: 'Delete a transaction' })
  @ApiParam({ name: 'id', description: 'Transaction MongoDB ObjectId' })
  @ApiResponse({ status: 204, description: 'Transaction deleted successfully' })
  @ApiResponse({ status: 404, description: 'Transaction not found' })
  async remove(@Param('id') id: string) {
    return this.transactionsService.remove(id);
  }
}
