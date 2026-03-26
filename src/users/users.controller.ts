import {
  Controller,
  Get,
  Post,
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
} from '@nestjs/swagger';
import { InjectQueue } from '@nestjs/bull';
import { Queue } from 'bull';
import { v4 as uuidv4 } from 'uuid';
import * as bcrypt from 'bcrypt';
import { UsersService } from './users.service';
import { CreateUserDto } from './dto/create-user.dto';
import { GenerateUsersDto } from './dto/generate-users.dto';
import { JwtAuthGuard } from '../auth/jwt-auth.guard';

@ApiTags('users')
@Controller('users')
export class UsersController {
  constructor(
    private readonly usersService: UsersService,
    @InjectQueue('user-imports') private readonly userImportsQueue: Queue,
  ) {}

  @Post()
  @UseGuards(JwtAuthGuard)
  @ApiBearerAuth()
  @ApiOperation({ summary: 'Create a single user' })
  @ApiResponse({ status: 201, description: 'User created successfully' })
  @ApiResponse({ status: 409, description: 'Email already registered' })
  async create(@Body() dto: CreateUserDto) {
    const user = await this.usersService.create(dto);
    return {
      _id: user._id,
      name: user.name,
      email: user.email,
      emailVerifiedAt: user.emailVerifiedAt,
    };
  }

  @Get()
  @UseGuards(JwtAuthGuard)
  @ApiBearerAuth()
  @ApiOperation({ summary: 'List users with pagination' })
  @ApiQuery({ name: 'page', required: false, type: Number })
  @ApiQuery({ name: 'limit', required: false, type: Number })
  async findAll(
    @Query('page') page = 1,
    @Query('limit') limit = 20,
  ) {
    return this.usersService.findPaginated(+page, +limit);
  }

  @Get('summary')
  @UseGuards(JwtAuthGuard)
  @ApiBearerAuth()
  @ApiOperation({ summary: 'Get user statistics summary' })
  async summary() {
    return this.usersService.getSummary();
  }

  @Get(':id')
  @UseGuards(JwtAuthGuard)
  @ApiBearerAuth()
  @ApiOperation({ summary: 'Get a single user by ID' })
  async findOne(@Param('id') id: string) {
    return this.usersService.findById(id);
  }

  @Post('generate')
  @UseGuards(JwtAuthGuard)
  @ApiBearerAuth()
  @HttpCode(HttpStatus.ACCEPTED)
  @ApiOperation({
    summary: 'Trigger bulk user generation via queue',
    description:
      'Dispatches multiple InsertUsersChunk jobs to Redis queue. ' +
      'Each job inserts a chunk of fake users. Monitor the queue dashboard for progress.',
  })
  @ApiResponse({ status: 202, description: 'Jobs dispatched successfully' })
  async generate(@Body() dto: GenerateUsersDto) {
    const total = dto.total ?? 10000;
    const chunkSize = dto.chunkSize ?? 500;
    const runId = dto.runId ?? uuidv4();

    // Pre-hash password once for all jobs (performance optimisation)
    const passwordHash = await bcrypt.hash(`password_${runId}`, 10);

    const jobs: Array<{ name: string; data: object }> = [];
    for (let start = 1; start <= total; start += chunkSize) {
      const size = Math.min(chunkSize, total - start + 1);
      jobs.push({
        name: 'insert-users-chunk',
        data: { startIndex: start, chunkSize: size, runId, passwordHash },
      });
    }

    await this.userImportsQueue.addBulk(jobs);

    return {
      message: 'Bulk user generation queued',
      runId,
      total,
      chunkSize,
      jobsDispatched: jobs.length,
    };
  }
}
