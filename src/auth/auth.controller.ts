import {
  Controller,
  Post,
  Body,
  Get,
  UseGuards,
  Request,
  HttpCode,
  HttpStatus,
} from '@nestjs/common';
import {
  ApiTags,
  ApiOperation,
  ApiResponse,
  ApiBearerAuth,
} from '@nestjs/swagger';
import { AuthService } from './auth.service';
import { UsersService } from '../users/users.service';
import { MailService } from '../mail/mail.service';
import { LoginDto } from './dto/login.dto';
import { CreateUserDto } from '../users/dto/create-user.dto';
import { JwtAuthGuard } from './jwt-auth.guard';
import { LocalAuthGuard } from './local-auth.guard';

@ApiTags('auth')
@Controller('auth')
export class AuthController {
  constructor(
    private readonly authService: AuthService,
    private readonly usersService: UsersService,
    private readonly mailService: MailService,
  ) {}

  @Post('register')
  @ApiOperation({ summary: 'Register a new user and send a magic verification link' })
  @ApiResponse({
    status: 201,
    description: 'Account created – verification email sent',
  })
  @ApiResponse({ status: 409, description: 'Email already registered' })
  async register(@Body() dto: CreateUserDto) {
    const user = await this.usersService.create(dto);

    // Fire-and-forget: send the magic verification link.
    // Swallow mail errors so registration still succeeds even if SMTP is misconfigured.
    this.mailService
      .sendVerificationEmail(user._id.toString(), user.email, user.name)
      .catch((err) => {
        // Logged inside MailService; silently ignored here
      });

    return {
      message:
        'Account created successfully. Please check your email and click the verification link to activate your account.',
      user: { _id: user._id, name: user.name, email: user.email },
    };
  }

  @Post('login')
  @HttpCode(HttpStatus.OK)
  @ApiOperation({ summary: 'Login and receive JWT token' })
  @ApiResponse({ status: 200, description: 'Login successful, returns JWT' })
  @ApiResponse({ status: 401, description: 'Invalid credentials' })
  async login(@Body() dto: LoginDto) {
    return this.authService.login(dto.email, dto.password);
  }

  @Post('login/local')
  @UseGuards(LocalAuthGuard)
  @HttpCode(HttpStatus.OK)
  @ApiOperation({
    summary: 'Login via Passport local strategy (email + password form)',
    description:
      'Authenticates using the Passport `local` strategy. ' +
      'Accepts `email` and `password` in the request body and returns a JWT.',
  })
  @ApiResponse({ status: 200, description: 'Login successful, returns JWT' })
  @ApiResponse({ status: 401, description: 'Invalid credentials' })
  async loginLocal(@Request() req: any) {
    return this.authService.loginWithUser(req.user);
  }

  @Get('profile')
  @UseGuards(JwtAuthGuard)
  @ApiBearerAuth()
  @ApiOperation({ summary: 'Get current authenticated user profile' })
  @ApiResponse({ status: 200, description: 'User profile' })
  async profile(@Request() req: any) {
    const user = await this.usersService.findById(req.user.userId);
    if (!user) return { userId: req.user.userId, email: req.user.email };
    return {
      _id: user._id,
      name: user.name,
      email: user.email,
      emailVerifiedAt: user.emailVerifiedAt,
    };
  }
}
