# Complete Project Deep-Dive Reference

> **Audience:** Laravel developers reading the NestJS codebase for the first time.
> **Purpose:** Explain every file, every data flow, every architectural decision, and how each NestJS concept maps back to the Laravel equivalent you already know.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Technology Stack](#2-technology-stack)
3. [Repository Layout — Every File Explained](#3-repository-layout--every-file-explained)
4. [Application Bootstrap](#4-application-bootstrap)
5. [Module System — The NestJS Service Provider](#5-module-system--the-nestjs-service-provider)
6. [Authentication Module (`src/auth/`)](#6-authentication-module-srcauth)
7. [Users Module (`src/users/`)](#7-users-module-srcusers)
8. [Queue Module (`src/queue/`)](#8-queue-module-srcqueue)
9. [Dashboard Module (`src/dashboard/`)](#9-dashboard-module-srcdashboard)
10. [Mail Module (`src/mail/`)](#10-mail-module-srcmail)
11. [Static Frontend Pages (`public/`)](#11-static-frontend-pages-public)
12. [Complete Data-Flow Walkthroughs](#12-complete-data-flow-walkthroughs)
13. [Redis Architecture — Keys, Channels & TTLs](#13-redis-architecture--keys-channels--ttls)
14. [Environment Variables — Full Reference](#14-environment-variables--full-reference)
15. [Security Architecture](#15-security-architecture)
16. [Running the Application](#16-running-the-application)
17. [Laravel → NestJS Cheat Sheet](#17-laravel--nestjs-cheat-sheet)

---

## 1. Project Overview

This application started as a **Laravel 12 + Livewire + MySQL + Redis** project. It was fully rewritten in **NestJS + TypeScript + MongoDB + Bull/Redis**. Every feature of the original is preserved:

| Feature | Description |
|---|---|
| User management | Create, list, paginate, and inspect users |
| Bulk user generation | Dispatch thousands of queue jobs that insert users in parallel chunks |
| Email verification | Signed JWT links, queued via Redis for mass sending |
| Real-time dashboards | Live queue statistics and user statistics via WebSockets (Socket.io) |
| Redis pub/sub | Dashboard refresh signals broadcast to all connected clients |
| JWT authentication | Protected API endpoints and dashboard pages |
| Swagger API docs | Interactive at `/api/docs` |

The app exposes:
- An **HTTP REST API** (JSON) for all data operations
- A **WebSocket namespace** (`/dashboard`) for real-time dashboard updates
- **Static HTML pages** at `/dashboard/queue.html`, `/dashboard/users.html`, `/dashboard/login.html`, and `/dashboard/signup.html`
- An **email verification endpoint** at `/email/verify?token=…`

---

## 2. Technology Stack

| Layer | Technology | Laravel equivalent |
|---|---|---|
| Language | TypeScript 5 / Node.js 18+ | PHP 8.3 |
| Framework | NestJS v10 | Laravel 12 |
| HTTP Server | Express (via `@nestjs/platform-express`) | PHP-FPM / `php artisan serve` |
| Database | MongoDB 7 via Mongoose ODM | MySQL via Eloquent ORM |
| Queue backend | Redis + Bull (`@nestjs/bull`) | Redis + Laravel queues |
| Authentication | Passport.js + JWT (`@nestjs/passport`) | Laravel Sanctum / Passport |
| Real-time | Socket.io (`@nestjs/websockets`) | Livewire + polling |
| Email | Nodemailer | Laravel Mail (Mailable) |
| Validation | `class-validator` + `class-transformer` | Form Requests |
| API docs | `@nestjs/swagger` (OpenAPI 3) | `l5-swagger` (third-party) |
| Configuration | `@nestjs/config` (reads `.env`) | `config/` files + `env()` helper |
| Password hashing | `bcrypt` | Laravel `Hash` facade (bcrypt) |
| Package manager | npm | Composer |

---

## 3. Repository Layout — Every File Explained

```
laravel12-redis/
│
├── src/                          # All TypeScript application source
│   ├── main.ts                   # Application entry point (= public/index.php + bootstrap/app.php)
│   ├── app.module.ts             # Root module wiring everything together (= config/app.php)
│   │
│   ├── auth/                     # Authentication feature module
│   │   ├── auth.module.ts        # Module definition + JWT/Passport config
│   │   ├── auth.controller.ts    # POST /auth/register, POST /auth/login, GET /auth/profile
│   │   ├── auth.service.ts       # validateUser(), login() logic
│   │   ├── jwt.strategy.ts       # Passport JWT strategy — validates Bearer tokens
│   │   ├── jwt-auth.guard.ts     # Guard applied to protected routes
│   │   └── dto/
│   │       └── login.dto.ts      # Shape + validation for login request body
│   │
│   ├── users/                    # User management feature module
│   │   ├── users.module.ts       # Module definition
│   │   ├── users.controller.ts   # REST endpoints for users + bulk generate
│   │   ├── users.service.ts      # All MongoDB user operations
│   │   ├── schemas/
│   │   │   └── user.schema.ts    # Mongoose schema (= Eloquent model + migration)
│   │   └── dto/
│   │       ├── create-user.dto.ts    # Validation for single user creation
│   │       └── generate-users.dto.ts # Validation for bulk generation params
│   │
│   ├── queue/                    # Bull queue feature module
│   │   ├── queue.module.ts       # Bull registration + Redis config
│   │   ├── queue.controller.ts   # GET /queue/stats, POST /queue/email-verifications
│   │   ├── dto/
│   │   │   └── queue-email-verifications.dto.ts  # Validation for email-verification dispatch
│   │   └── processors/
│   │       ├── insert-users.processor.ts          # Worker: bulk insert users chunk
│   │       └── send-email-verification.processor.ts  # Worker: send verification emails chunk
│   │
│   ├── dashboard/                # Real-time dashboard feature module
│   │   ├── dashboard.module.ts   # Module definition
│   │   ├── dashboard.controller.ts  # REST: email verify, queue/users stats, refresh
│   │   ├── dashboard.service.ts  # Redis cache, pub/sub, JWT token verification
│   │   └── dashboard.gateway.ts  # Socket.io WebSocket gateway
│   │
│   └── mail/                     # Email sending feature module
│       ├── mail.module.ts        # Module definition + JWT (for token signing)
│       └── mail.service.ts       # Nodemailer transporter + verification URL generator
│
├── public/                       # Static files served directly by Express
│   ├── index.html                # Landing page with links to all sections
│   └── dashboard/
│       ├── login.html            # Login form (calls POST /auth/login)
│       ├── signup.html           # Registration form (calls POST /auth/register)
│       ├── queue.html            # Real-time queue stats dashboard (Socket.io)
│       └── users.html            # Real-time users stats dashboard (Socket.io)
│
├── docs/                         # Developer documentation
│   ├── livewire-redis-dashboard-step-by-step.md  # Original Laravel Livewire guide
│   ├── laravel-to-nest-changes.md                # What changed in the conversion
│   ├── laravel-nest-equivalents.md               # Laravel ↔ NestJS concept mapping
│   └── project-deep-dive.md                      # This document
│
├── .devcontainer/                # GitHub Codespaces configuration
│   ├── devcontainer.json         # Container definition + port forwards
│   ├── post-create.sh            # Installs MongoDB, Redis, npm packages
│   └── post-start.sh            # Starts services on each Codespace start
│
├── nest-cli.json                 # NestJS CLI configuration + build assets
├── tsconfig.json                 # TypeScript compiler options
├── tsconfig.build.json           # tsconfig for production builds (excludes tests)
├── package.json                  # Dependencies and npm scripts
├── .env.example                  # Template for the required .env file
└── .gitignore
```

---

## 4. Application Bootstrap

### `src/main.ts` — The Entry Point

```typescript
import { NestFactory } from '@nestjs/core';
import { AppModule } from './app.module';
import { ValidationPipe } from '@nestjs/common';
import { DocumentBuilder, SwaggerModule } from '@nestjs/swagger';
import { NestExpressApplication } from '@nestjs/platform-express';
import { join } from 'path';

async function bootstrap() {
  // 1. Create the NestJS application (Express adapter)
  const app = await NestFactory.create<NestExpressApplication>(AppModule);

  // 2. Serve public/ folder as static files
  //    → public/index.html is served at http://localhost:3000/
  //    → public/dashboard/*.html served at /dashboard/*.html
  app.useStaticAssets(join(__dirname, '..', 'public'));

  // 3. Global validation pipe — validates every request body automatically
  //    whitelist: true     → strips fields not declared on the DTO
  //    forbidNonWhitelisted → throws 400 if unknown fields arrive
  //    transform: true     → auto-casts "1" → 1 for @Type(() => Number) DTOs
  app.useGlobalPipes(new ValidationPipe({
    whitelist: true,
    transform: true,
    forbidNonWhitelisted: true,
  }));

  // 4. Swagger / OpenAPI setup
  const config = new DocumentBuilder()
    .setTitle('Redis NestJS Dashboard API')
    .setDescription('...')
    .setVersion('1.0')
    .addBearerAuth()
    .build();
  SwaggerModule.setup('api/docs', app, SwaggerModule.createDocument(app, config));

  // 5. Listen on configured port
  await app.listen(process.env.APP_PORT || 3000);
}
bootstrap();
```

**Laravel equivalent:**
- `NestFactory.create(AppModule)` = `new Application(dirname(__DIR__))` in `bootstrap/app.php`
- `useStaticAssets()` = `public_path()` / `asset()` helper / static file serving
- `useGlobalPipes(ValidationPipe)` = global middleware in `app/Http/Kernel.php`
- `SwaggerModule.setup(...)` = would require `l5-swagger` in Laravel

---

### `src/app.module.ts` — The Root Module

```typescript
@Module({
  imports: [
    // Reads .env file and makes ConfigService available everywhere
    ConfigModule.forRoot({ isGlobal: true }),

    // MongoDB connection — equivalent to config/database.php + DB_* env vars
    MongooseModule.forRootAsync({
      inject: [ConfigService],
      useFactory: (config: ConfigService) => ({
        uri: config.get<string>('MONGODB_URI', 'mongodb://localhost:27017/laravel12_redis'),
      }),
    }),

    AuthModule,       // auth/ feature
    UsersModule,      // users/ feature
    QueueModule,      // queue/ + Bull registration
    DashboardModule,  // dashboard/ + WebSocket gateway
    MailModule,       // mail/ + Nodemailer
  ],
})
export class AppModule {}
```

**Laravel equivalent:**
```php
// config/app.php — 'providers' array
// config/database.php — database connections
// AppServiceProvider, AuthServiceProvider, RouteServiceProvider, ...
```

The root `AppModule` is the central wiring point — every feature module is imported here, which is exactly what `config/app.php`'s `providers` array does in Laravel.

---

## 5. Module System — The NestJS Service Provider

In Laravel, you register services in **Service Providers** (`register()` + `boot()`) and list them in `config/app.php`. In NestJS, every feature uses a `@Module` decorator that does all of this at once.

### Anatomy of a NestJS Module

```typescript
@Module({
  imports:     [...],  // Modules whose exports can be used here
                       // = importing ServiceProviders / use $app->make()

  providers:   [...],  // Classes that this module contributes to the DI container
                       // = $app->singleton(ConcreteClass::class)

  controllers: [...],  // Controllers to register (routes become active)
                       // = Route::apiResource(...)

  exports:     [...],  // Providers that other modules can inject
                       // = making a service public/accessible from other providers
})
export class SomeModule {}
```

### Module Dependency Graph

```
AppModule
 ├── ConfigModule (global)
 ├── MongooseModule (global)
 ├── AuthModule
 │     ├── UsersModule  (imports)
 │     ├── MailModule   (imports)
 │     └── JwtModule    (imports + exports)
 ├── UsersModule
 │     ├── MongooseModule.forFeature([User])
 │     └── BullModule.registerQueue('user-imports')
 ├── QueueModule
 │     ├── BullModule.forRoot (Redis config)
 │     ├── BullModule.registerQueue('user-imports', 'email-verifications')
 │     ├── UsersModule  (imports)
 │     └── MailModule   (imports)
 ├── DashboardModule
 │     ├── JwtModule    (for token verification)
 │     ├── BullModule.registerQueue('user-imports', 'email-verifications')
 │     └── UsersModule  (imports)
 └── MailModule
       └── JwtModule    (for signing verification tokens)
```

---

## 6. Authentication Module (`src/auth/`)

### Files

| File | Purpose | Laravel equivalent |
|---|---|---|
| `auth.module.ts` | Wires JWT, Passport, UsersModule, MailModule | `AuthServiceProvider` + `config/auth.php` |
| `auth.controller.ts` | `POST /auth/register`, `POST /auth/login`, `GET /auth/profile` | `AuthController` |
| `auth.service.ts` | `validateUser()`, `login()` | `AuthService` or `AuthController` logic |
| `jwt.strategy.ts` | Validates Bearer tokens on every protected request | `app/Http/Middleware/Authenticate.php` |
| `jwt-auth.guard.ts` | Applied to routes to enforce authentication | `auth:sanctum` middleware |
| `dto/login.dto.ts` | Shape + validation rules for `POST /auth/login` body | `LoginRequest extends FormRequest` |

---

### `auth.module.ts` — JWT Configuration

```typescript
@Module({
  imports: [
    UsersModule,    // Need UsersService to look up users
    MailModule,     // Need MailService to send welcome/verification emails
    PassportModule,

    // Configure JWT secret + expiry from .env
    // Equivalent of config/auth.php + JWT_SECRET / JWT_EXPIRES_IN in .env
    JwtModule.registerAsync({
      imports: [ConfigModule],
      inject: [ConfigService],
      useFactory: (config: ConfigService) => ({
        secret: config.get<string>('JWT_SECRET', 'changeme'),
        signOptions: { expiresIn: config.get<string>('JWT_EXPIRES_IN', '7d') },
      }),
    }),
  ],
  providers: [AuthService, JwtStrategy],
  controllers: [AuthController],
  exports: [AuthService, JwtModule], // JwtModule exported so DashboardModule can verify tokens
})
export class AuthModule {}
```

---

### `jwt.strategy.ts` — How Bearer Tokens Are Validated

Every request that hits a route guarded by `@UseGuards(JwtAuthGuard)` goes through this strategy **before** reaching the controller method.

```typescript
@Injectable()
export class JwtStrategy extends PassportStrategy(Strategy) {
  constructor(config: ConfigService) {
    super({
      // Extract token from "Authorization: Bearer <token>" header
      jwtFromRequest: ExtractJwt.fromAuthHeaderAsBearerToken(),
      ignoreExpiration: false,        // Expired tokens are rejected
      secretOrKey: config.get<string>('JWT_SECRET', 'changeme'),
    });
  }

  // Called after the token signature is validated
  // Whatever you return here becomes req.user in the controller
  async validate(payload: { sub: string; email: string }) {
    return { userId: payload.sub, email: payload.email };
    //       ↑ This is now req.user in any @Request() decorated parameter
  }
}
```

**Laravel equivalent:** This is exactly what `app/Http/Middleware/Authenticate.php` does, plus what `Auth::guard('sanctum')->check()` does internally — parse the token, validate signature, put the user in the request.

---

### `jwt-auth.guard.ts` — Protecting Routes

```typescript
@Injectable()
export class JwtAuthGuard extends AuthGuard('jwt') {
  canActivate(context: ExecutionContext) {
    return super.canActivate(context);
    // If the JWT is invalid/missing, throws 401 UnauthorizedException
    // If valid, attaches req.user and proceeds to the controller
  }
}
```

**Usage:**
```typescript
// On a single route method:
@Get('profile')
@UseGuards(JwtAuthGuard)
async profile() { ... }

// On an entire controller — all routes in the controller are protected:
@Controller('queue')
@UseGuards(JwtAuthGuard)
export class QueueController { ... }
```

**Laravel equivalent:** `->middleware('auth:sanctum')` on routes, or `$this->middleware('auth:sanctum')` in the constructor.

---

### `auth.controller.ts` — The Three Auth Endpoints

#### `POST /auth/register`

```typescript
@Post('register')
async register(@Body() dto: CreateUserDto) {
  // 1. Create user in MongoDB (hashes password internally via UsersService)
  const user = await this.usersService.create(dto);

  // 2. Fire-and-forget: send verification email
  //    If SMTP fails, registration still succeeds (swallowed error)
  this.mailService
    .sendVerificationEmail(user._id.toString(), user.email, user.name)
    .catch(() => {});  // silent on mail error

  return {
    message: 'Account created. Check your email for the verification link.',
    user: { _id: user._id, name: user.name, email: user.email },
  };
}
```

**Laravel equivalent:** `AuthController@register` calling `User::create()` and `Mail::to($user)->queue(new VerificationEmail($user))`.

---

#### `POST /auth/login`

```typescript
@Post('login')
@HttpCode(HttpStatus.OK)  // Default POST returns 201; this forces 200
async login(@Body() dto: LoginDto) {
  return this.authService.login(dto.email, dto.password);
  // Returns: { access_token: '...', user: { _id, name, email, emailVerifiedAt } }
}
```

`AuthService.login()`:
```typescript
async login(email: string, password: string) {
  // 1. Find user (with password field included — normally excluded by select:false)
  const user = await this.usersService.findByEmail(email);
  if (!user) throw new UnauthorizedException('Invalid credentials');

  // 2. Verify password with bcrypt
  const valid = await bcrypt.compare(password, user.password);
  if (!valid) throw new UnauthorizedException('Invalid credentials');

  // 3. Sign a JWT { sub: userId, email }
  const payload = { sub: user._id.toString(), email: user.email };
  return {
    access_token: this.jwtService.sign(payload),
    user: { _id: user._id, name: user.name, email: user.email, emailVerifiedAt: user.emailVerifiedAt },
  };
}
```

**Laravel equivalent:**
```php
$user = User::where('email', $email)->first();
if (!$user || !Hash::check($password, $user->password)) abort(401);
$token = $user->createToken('api')->plainTextToken;
return ['access_token' => $token, 'user' => $user];
```

---

#### `GET /auth/profile` (protected)

```typescript
@Get('profile')
@UseGuards(JwtAuthGuard)
async profile(@Request() req: any) {
  // req.user was set by JwtStrategy.validate()
  // Contains: { userId: '...', email: '...' }
  const user = await this.usersService.findById(req.user.userId);
  return { _id: user._id, name: user.name, email: user.email, emailVerifiedAt: user.emailVerifiedAt };
}
```

**Laravel equivalent:** `auth()->user()` or `$request->user()` in a route protected by `auth:sanctum`.

---

### `dto/login.dto.ts`

```typescript
export class LoginDto {
  @ApiProperty({ example: 'admin@example.com' })
  @IsEmail()
  email: string;

  @ApiProperty({ example: 'secret123' })
  @IsString()
  @MinLength(6)   // Login allows slightly shorter passwords (6 chars) than registration (8 chars)
  password: string;
}
```

**Laravel equivalent:**
```php
public function rules(): array {
    return ['email' => 'required|email', 'password' => 'required|string|min:6'];
}
```

---

## 7. Users Module (`src/users/`)

### Files

| File | Purpose | Laravel equivalent |
|---|---|---|
| `users.module.ts` | Registers Mongoose model + Bull queue | Model + `config/database.php` binding |
| `users.controller.ts` | `GET/POST /users`, `GET /users/:id`, `GET /users/summary`, `POST /users/generate` | `UserController` |
| `users.service.ts` | All database operations on the User collection | `UserRepository` or fat Eloquent model |
| `schemas/user.schema.ts` | Mongoose schema defining the `users` collection | `User` Eloquent model + migration |
| `dto/create-user.dto.ts` | Validation for creating a single user | `CreateUserRequest` |
| `dto/generate-users.dto.ts` | Validation for bulk generation params | Custom FormRequest |

---

### `schemas/user.schema.ts` — The User Model

```typescript
export type UserDocument = User & Document & { createdAt: Date; updatedAt: Date };

@Schema({ timestamps: true })   // MongoDB equivalent of $table->timestamps()
export class User {

  @Prop({ required: true })
  name: string;                 // equivalent: $table->string('name')

  @Prop({ required: true, unique: true, lowercase: true, trim: true })
  email: string;                // equivalent: $table->string('email')->unique()
                                // lowercase: auto-lowercases on save (like a model boot hook)
                                // trim: removes surrounding whitespace

  @Prop({ required: true, select: false })
  password: string;             // select: false = $hidden = ['password']
                                // Won't appear in query results unless .select('+password')

  @Prop({ type: Date, default: null })
  emailVerifiedAt: Date | null; // equivalent: $table->timestamp('email_verified_at')->nullable()
}

export const UserSchema = SchemaFactory.createForClass(User);
// This call creates the actual Mongoose model schema from the decorated class
```

**Important MongoDB differences from MySQL:**
- Primary key is `_id` (MongoDB ObjectId), not an auto-increment integer.
- There are no migrations — the schema shape is enforced by Mongoose validators at the application layer, not by the database engine.
- `insertMany` with `{ ordered: false }` continues inserting even if some documents fail the unique constraint (duplicate email), whereas MySQL would stop on the first duplicate by default.

---

### `users.service.ts` — All Database Operations

```typescript
@Injectable()
export class UsersService {
  constructor(
    @InjectModel(User.name) private readonly userModel: Model<UserDocument>,
  ) {}

  // CREATE — equivalent of User::create($data)
  async create(dto: CreateUserDto): Promise<UserDocument> {
    const existing = await this.userModel.findOne({ email: dto.email }).exec();
    if (existing) throw new ConflictException('Email already registered'); // = 409

    const hashed = await bcrypt.hash(dto.password, 10);
    const user = new this.userModel({ ...dto, password: hashed });
    return user.save();
  }

  // FIND BY EMAIL (for login) — includes password field
  async findByEmail(email: string): Promise<UserDocument | null> {
    return this.userModel
      .findOne({ email: email.toLowerCase() })
      .select('+password')   // Override select:false to include password
      .exec();
  }

  // FIND BY ID
  async findById(id: string): Promise<UserDocument | null> {
    return this.userModel.findById(id).exec();
  }

  // MARK EMAIL VERIFIED
  async markEmailVerified(id: string): Promise<void> {
    await this.userModel
      .findByIdAndUpdate(id, { emailVerifiedAt: new Date() })
      .exec();
  }

  // STATISTICS SUMMARY — runs 3 queries in parallel using Promise.all
  async getSummary(): Promise<UserSummary> {
    const [total, verified, latestUser] = await Promise.all([
      this.userModel.countDocuments().exec(),                           // COUNT(*)
      this.userModel.countDocuments({ emailVerifiedAt: { $ne: null } }).exec(), // COUNT where verified
      this.userModel.findOne().sort({ createdAt: -1 }).exec(),         // ORDER BY created_at DESC LIMIT 1
    ]);
    return { total, verified, unverified: total - verified, latestUser };
  }

  // PAGINATED LIST
  async findPaginated(page: number, limit: number) {
    const skip = (page - 1) * limit;  // = OFFSET
    const [data, total] = await Promise.all([
      this.userModel.find().skip(skip).limit(limit).sort({ createdAt: -1 }).exec(),
      this.userModel.countDocuments().exec(),
    ]);
    return { data, total, page, lastPage: Math.ceil(total / limit) };
  }

  // BULK INSERT (used by queue processor)
  // { ordered: false } = continue on duplicate key, don't stop at first error
  async bulkInsert(users: Array<{ name: string; email: string; password: string }>): Promise<void> {
    await this.userModel.insertMany(users, { ordered: false }).catch((err) => {
      if (err.code !== 11000) throw err;  // 11000 = MongoDB duplicate key error
    });
  }

  // FIND MULTIPLE BY IDs (used by email verification processor)
  async findUsersByIds(ids: string[]): Promise<UserDocument[]> {
    return this.userModel.find({ _id: { $in: ids } }).exec(); // = whereIn('id', $ids)
  }
}
```

---

### `users.controller.ts` — REST Endpoints

#### `POST /users` — Create a single user

```typescript
@Post()
@UseGuards(JwtAuthGuard)
async create(@Body() dto: CreateUserDto) {
  const user = await this.usersService.create(dto);
  return { _id: user._id, name: user.name, email: user.email, emailVerifiedAt: user.emailVerifiedAt };
}
```

#### `GET /users` — Paginated list

```typescript
@Get()
@UseGuards(JwtAuthGuard)
async findAll(
  @Query('page') page = 1,
  @Query('limit') limit = 20,
) {
  return this.usersService.findPaginated(+page, +limit);
}
// Response: { data: [...], total: 50000, page: 1, lastPage: 2500 }
```

#### `GET /users/summary` — Statistics

```typescript
@Get('summary')
@UseGuards(JwtAuthGuard)
async summary() {
  return this.usersService.getSummary();
}
// Response: { total: 50000, verified: 123, unverified: 49877, latestUser: {...} }
```

#### `POST /users/generate` — Bulk Queue-Based Generation

This is one of the most important endpoints. It:
1. Accepts `total` (how many users) and `chunkSize` (users per job)
2. Pre-hashes a shared password once (performance optimisation)
3. Dispatches `N` Bull jobs to the `user-imports` queue
4. Returns immediately with `202 Accepted` — doesn't wait for jobs to complete

```typescript
@Post('generate')
@UseGuards(JwtAuthGuard)
@HttpCode(HttpStatus.ACCEPTED)   // 202, not 201 — async operation
async generate(@Body() dto: GenerateUsersDto) {
  const total     = dto.total     ?? 10000;
  const chunkSize = dto.chunkSize ?? 500;
  const runId     = dto.runId     ?? uuidv4();  // Unique ID for this run

  // Hash once, reuse in all jobs — avoids bcrypt overhead × N
  const passwordHash = await bcrypt.hash(`password_${runId}`, 10);

  // Build job array
  const jobs = [];
  for (let start = 1; start <= total; start += chunkSize) {
    const size = Math.min(chunkSize, total - start + 1);
    jobs.push({
      name: 'insert-users-chunk',
      data: { startIndex: start, chunkSize: size, runId, passwordHash },
    });
  }

  // Dispatch all jobs at once — Bull adds them to Redis
  await this.userImportsQueue.addBulk(jobs);

  return {
    message: 'Bulk user generation queued',
    runId,
    total,
    chunkSize,
    jobsDispatched: jobs.length,  // e.g. 50000 / 500 = 100 jobs
  };
}
```

**Laravel equivalent:**
```php
for ($start = 1; $start <= $total; $start += $chunkSize) {
    InsertUsersChunkJob::dispatch($data)->onQueue('user-imports');
}
```

---

### `dto/create-user.dto.ts`

```typescript
export class CreateUserDto {
  @ApiProperty({ example: 'Jane Doe' })
  @IsString()
  @IsNotEmpty()
  name: string;

  @ApiProperty({ example: 'jane@example.com' })
  @IsEmail()
  email: string;

  @ApiProperty({ example: 'secret123', minLength: 8 })
  @IsString()
  @MinLength(8)
  password: string;
}
```

**Laravel equivalent:**
```php
'name'     => 'required|string',
'email'    => 'required|email',
'password' => 'required|min:8',
```

### `dto/generate-users.dto.ts`

```typescript
export class GenerateUsersDto {
  @IsOptional() @Type(() => Number) @IsInt() @Min(1) @Max(1_000_000)
  total?: number = 10000;        // Defaults to 10,000 users

  @IsOptional() @Type(() => Number) @IsInt() @Min(1) @Max(5000)
  chunkSize?: number = 500;      // Default: 500 users per job

  @IsOptional() @IsString()
  runId?: string;                // Custom run identifier, defaults to UUID
}
```

`@Type(() => Number)` + `transform: true` on the global `ValidationPipe` is equivalent to Laravel's `integer` rule auto-casting query strings to integers.

---

## 8. Queue Module (`src/queue/`)

### How Bull Queues Work

Bull is a Redis-backed job queue library for Node. It is the direct functional equivalent of Laravel's built-in queue system when configured with the Redis driver.

```
Laravel                              NestJS (Bull)
─────────────────────────────────    ─────────────────────────────────
Job class + handle() method          @Processor class + @Process method
dispatch(new Job($data))             queue.add('job-name', data)
dispatch()->onQueue('name')          @InjectQueue('name') + queue.add(...)
php artisan queue:work redis         Bull processor runs in-process (no command needed)
Queue::size('name')                  queue.getJobCounts()
failed_jobs table                    Bull keeps failed jobs in Redis
```

### `queue.module.ts` — Bull Registration

```typescript
@Module({
  imports: [
    // Register Bull with Redis — equivalent of config/queue.php redis connection
    BullModule.forRootAsync({
      inject: [ConfigService],
      useFactory: (config: ConfigService) => ({
        redis: {
          host:     config.get<string>('REDIS_HOST', '127.0.0.1'),
          port:     config.get<number>('REDIS_PORT', 6379),
          password: config.get<string>('REDIS_PASSWORD') || undefined,
        },
      }),
    }),

    // Register the two named queues
    BullModule.registerQueue(
      { name: 'user-imports' },
      { name: 'email-verifications' },
    ),

    MongooseModule.forFeature([{ name: User.name, schema: UserSchema }]),
    UsersModule,
    MailModule,
  ],
  controllers: [QueueController],
  providers: [InsertUsersProcessor, SendEmailVerificationProcessor],
  exports: [BullModule],
})
export class QueueModule {}
```

`BullModule.forRootAsync` is the single place where Redis connection details are configured — equivalent to the `redis` key inside `config/queue.php`.

---

### `processors/insert-users.processor.ts` — The User Import Worker

```typescript
export interface InsertUsersChunkJobData {
  startIndex: number;   // First user index in this chunk (e.g. 1, 501, 1001, ...)
  chunkSize: number;    // How many users to create in this job (e.g. 500)
  runId: string;        // Unique run identifier (for unique email generation)
  passwordHash: string; // Pre-hashed password (avoids bcrypt × chunkSize)
}

@Processor('user-imports')              // = the queue name this worker listens to
export class InsertUsersProcessor {
  private readonly logger = new Logger(InsertUsersProcessor.name);

  constructor(private readonly usersService: UsersService) {}

  @Process('insert-users-chunk')        // = the job name this handler processes
  async handle(job: Job<InsertUsersChunkJobData>): Promise<void> {
    const { startIndex, chunkSize, runId, passwordHash } = job.data;

    // Build the array of user objects for this chunk
    const users = Array.from({ length: chunkSize }, (_, i) => {
      const index = startIndex + i;
      return {
        name:     `User ${index}`,
        email:    `user_${runId}_${index}@example.com`,  // Guaranteed unique via runId
        password: passwordHash,                           // Pre-hashed, shared across chunk
      };
    });

    // Bulk insert into MongoDB (ignores duplicate key errors)
    await this.usersService.bulkInsert(users);

    this.logger.log(
      `Inserted chunk [${startIndex} – ${startIndex + chunkSize - 1}] for run ${runId}`,
    );
  }
}
```

**Laravel equivalent:**
```php
class InsertUsersChunkJob implements ShouldQueue {
    public function __construct(private array $data) {}
    public function handle(UsersService $service): void {
        $service->bulkInsert($this->buildUsers($this->data));
    }
}
```

Key differences:
- In Laravel you pass the full data payload in the constructor; in Bull you pass it in `job.data`.
- There is no `@InjectQueue` needed inside a processor — Bull delivers the job automatically.
- Bull workers run in the **same process** as the web server. No `queue:work` command.

---

### `processors/send-email-verification.processor.ts` — Email Sender

```typescript
@Processor('email-verifications')
export class SendEmailVerificationProcessor {
  private readonly logger = new Logger(SendEmailVerificationProcessor.name);

  constructor(
    private readonly usersService: UsersService,
    private readonly mailService: MailService,
  ) {}

  @Process('send-verification-chunk')
  async handle(job: Job<SendEmailVerificationChunkJobData>): Promise<void> {
    const { userIds } = job.data;
    // Fetch full user documents for this chunk
    const users = await this.usersService.findUsersByIds(userIds);

    let sent = 0;
    let failed = 0;

    // Send one email per user — sequential to avoid overwhelming the SMTP server
    for (const user of users) {
      try {
        await this.mailService.sendVerificationEmail(
          user._id.toString(),
          user.email,
          user.name,
        );
        sent++;
      } catch {
        failed++;  // Don't rethrow — log and continue with next user
      }
    }

    this.logger.log(
      `Email chunk: ${sent} sent, ${failed} failed (batch: ${userIds.length})`,
    );
  }
}
```

---

### `queue.controller.ts` — Queue Stats & Dispatch

#### `GET /queue/stats`

```typescript
@Get('stats')
async stats() {
  const [userImportsCounts, emailVerificationCounts] = await Promise.all([
    this.userImportsQueue.getJobCounts(),
    this.emailVerificationsQueue.getJobCounts(),
  ]);
  return {
    queues: {
      'user-imports': userImportsCounts,
      'email-verifications': emailVerificationCounts,
    },
  };
}
// Returns: { queues: { 'user-imports': { waiting: 40, active: 2, completed: 58, failed: 0 } } }
```

#### `POST /queue/email-verifications`

```typescript
@Post('email-verifications')
@HttpCode(HttpStatus.ACCEPTED)
async queueEmailVerifications(@Body() dto: QueueEmailVerificationsDto) {
  const { chunkSize = 100, limit = 0 } = dto;

  // Find all unverified users (optionally limited)
  const query = this.userModel.find({ emailVerifiedAt: null }).select('_id');
  if (limit > 0) query.limit(limit);
  const users = await query.exec();

  if (users.length === 0) return { message: 'No unverified users', jobsDispatched: 0 };

  // Split into chunks and dispatch one job per chunk
  const jobs = [];
  for (let i = 0; i < users.length; i += chunkSize) {
    const chunk = users.slice(i, i + chunkSize).map(u => u._id.toString());
    jobs.push({ name: 'send-verification-chunk', data: { userIds: chunk } });
  }

  await this.emailVerificationsQueue.addBulk(jobs);
  return { totalUsers: users.length, chunkSize, jobsDispatched: jobs.length };
}
```

---

## 9. Dashboard Module (`src/dashboard/`)

This module has three parts:
1. **`DashboardService`** — Redis cache operations + pub/sub subscription
2. **`DashboardGateway`** — WebSocket server (Socket.io)
3. **`DashboardController`** — REST endpoints for stats + email verification

### `dashboard.service.ts` — Redis Cache & Pub/Sub

#### Redis Key Structure

| Redis Key | Type | TTL | Contents |
|---|---|---|---|
| `dashboard:queue_summary` | String (JSON) | 3600s | Queue stats snapshot |
| `dashboard:users_summary` | String (JSON) | 3600s | User stats snapshot |

#### Redis Channel Structure

| Channel | Direction | Trigger |
|---|---|---|
| `dashboard.summary.refresh` | Published by: any client requesting refresh | Signals all subscribers to rebuild summaries |
| `dashboard.summary.updated` | Published by: DashboardService after rebuild | Signals that new data is available |

#### Two Redis Clients

```typescript
onModuleInit() {
  // Redis requires a dedicated connection for subscribe mode
  // You cannot mix subscribe + get/set/publish on the same connection
  this.redisClient     = new Redis(redisOpts);  // For: GET, SETEX, PUBLISH, DEL
  this.redisSubscriber = new Redis(redisOpts);  // For: SUBSCRIBE only

  // Subscribe to refresh requests
  this.redisSubscriber.subscribe(this.REFRESH_CHANNEL);
  this.redisSubscriber.on('message', async (channel, _message) => {
    if (channel === this.REFRESH_CHANNEL && this.onRefreshCallback) {
      // Trigger a full summary rebuild + WebSocket broadcast
      await this.onRefreshCallback();
    }
  });
}
```

**Laravel equivalent:** This was a separate `php artisan dashboard:redis-listen` command that had to be started in a dedicated terminal. Here, the subscription starts automatically when `DashboardModule` initialises, because `DashboardService` implements `OnModuleInit`.

#### Cache-First Queue Summary

```typescript
async getQueueSummary(): Promise<QueueSummary> {
  // 1. Try to serve from Redis cache
  const cached = await this.redisClient.get(this.QUEUE_SUMMARY_KEY);
  if (cached) {
    try { return JSON.parse(cached); } catch { /* fall through */ }
  }
  // 2. Cache miss — build from live data
  return this.buildAndCacheQueueSummary();
}

async buildAndCacheQueueSummary(): Promise<QueueSummary> {
  // Get job counts from Bull for the two managed queues
  const [userImportsCounts, emailVerificationCounts] = await Promise.all([
    this.userImportsQueue.getJobCounts(),
    this.emailVerificationsQueue.getJobCounts(),
  ]);

  // Get raw Redis key counts (pending/active/delayed) for each configured queue
  const queueNames = this.config.get<string>('QUEUE_NAMES', 'default,user-imports,email-verifications')
    .split(',').map(n => n.trim());

  const queues = await Promise.all(
    queueNames.map(async (name) => ({
      name,
      pending:  await this.redisClient.llen(`bull:${name}:wait`),    // LIST length
      reserved: await this.redisClient.zcard(`bull:${name}:active`), // SORTED SET cardinality
      delayed:  await this.redisClient.zcard(`bull:${name}:delayed`),
    })),
  );

  const summary = {
    queues,
    totals: {
      pending:   queues.reduce((s, q) => s + q.pending, 0),
      reserved:  queues.reduce((s, q) => s + q.reserved, 0),
      delayed:   queues.reduce((s, q) => s + q.delayed, 0),
      failed:    (userImportsCounts.failed || 0) + (emailVerificationCounts.failed || 0),
      completed: (userImportsCounts.completed || 0) + (emailVerificationCounts.completed || 0),
    },
    cachedAt: new Date().toISOString(),
  };

  // Store in Redis with 1-hour TTL
  await this.redisClient.setex(this.QUEUE_SUMMARY_KEY, 3600, JSON.stringify(summary));
  // Notify all pub/sub subscribers that new data is ready
  await this.redisClient.publish(this.UPDATED_CHANNEL, JSON.stringify({ type: 'queue' }));

  return summary;
}
```

---

### `dashboard.gateway.ts` — The WebSocket Server

```typescript
@WebSocketGateway({
  cors: { origin: '*' },
  namespace: '/dashboard',  // = Socket.io namespace, connect with io('/dashboard')
})
export class DashboardGateway
  implements OnGatewayInit, OnGatewayConnection, OnGatewayDisconnect
{
  @WebSocketServer()
  server: Server;  // The Socket.io server instance — can broadcast to ALL clients

  afterInit() {
    // Called once the WebSocket server is ready (equivalent of AppServiceProvider::boot)
    // Wire up the Redis pub/sub callback: when a refresh signal arrives,
    // rebuild summaries and push them to ALL connected browser tabs
    this.dashboardService.onRefreshCallback = async () => {
      const [queueSummary, rawUserSummary] = await Promise.all([
        this.dashboardService.buildAndCacheQueueSummary(),
        this.usersService.getSummary(),
      ]);
      const usersSummary = await this.dashboardService.cacheUsersSummary(rawUserSummary);

      this.server.emit('queue-summary', queueSummary);  // Broadcast to ALL clients
      this.server.emit('users-summary', usersSummary);
    };
  }

  handleConnection(client: Socket) {
    this.logger.log(`Client connected: ${client.id}`);
  }
  handleDisconnect(client: Socket) {
    this.logger.log(`Client disconnected: ${client.id}`);
  }

  // A specific client requests queue data → send only to that client
  @SubscribeMessage('get-queue-summary')
  async onGetQueueSummary(client: Socket) {
    const summary = await this.dashboardService.getQueueSummary();
    client.emit('queue-summary', summary);  // Only to this client
  }

  // A specific client requests user data
  @SubscribeMessage('get-users-summary')
  async onGetUsersSummary(client: Socket) {
    let summary = await this.dashboardService.getUsersSummary();
    if (!summary) {
      const raw = await this.usersService.getSummary();
      summary = await this.dashboardService.cacheUsersSummary(raw);
    }
    client.emit('users-summary', summary);
  }

  // A client clicks "Refresh Now" → publishes to Redis → all clients get update
  @SubscribeMessage('refresh-dashboard')
  async onRefreshDashboard() {
    await this.dashboardService.publishRefresh();
    // publishRefresh() → Redis PUBLISH → onRefreshCallback() → server.emit() to ALL
  }
}
```

**Laravel equivalent:**
```php
// app/Livewire/QueueSummaryDashboard.php
class QueueSummaryDashboard extends Component {
    public function loadSummary(RedisDashboardSummaryService $s) {
        $this->summary = $s->getQueueSummary();
    }
    public function refreshSummary(RedisDashboardSummaryService $s) {
        $s->publishRefresh('queue');
        $this->summary = $s->refreshQueueSummary();
    }
}
```

---

### `dashboard.controller.ts` — REST Endpoints

#### `GET /email/verify?token=…` — Email Verification (public, no auth)

```typescript
@Get('email/verify')
async verifyEmail(@Query('token') token: string) {
  return this.dashboardService.verifyEmailToken(token);
}
```

`DashboardService.verifyEmailToken()`:
```typescript
async verifyEmailToken(token: string): Promise<{ message: string }> {
  let payload: { sub: string; email: string; type: string };

  // 1. Verify JWT signature + expiry
  try {
    payload = this.jwtService.verify(token, { secret: this.config.get('JWT_SECRET') });
  } catch {
    throw new UnauthorizedException('Invalid or expired verification token');
  }

  // 2. Check this is actually a verification token (not a login token)
  if (payload.type !== 'email-verification') {
    throw new UnauthorizedException('Invalid token type');
  }

  // 3. Mark user as verified in MongoDB
  await this.usersService.markEmailVerified(payload.sub);

  // 4. Bust the cache so dashboard shows updated verified count
  await this.redisClient.del(this.USERS_SUMMARY_KEY);

  return { message: `Email ${payload.email} verified successfully.` };
}
```

**Laravel equivalent:** `Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['signed'])` using Laravel's built-in email verification.

The key difference: Laravel uses a signed URL with the user's ID and a hash of their email stored in the URL. This implementation uses a **self-contained JWT** — no database lookup or stored tokens are needed. The JWT carries the proof of identity itself.

#### `GET /api/dashboard/queue` and `GET /api/dashboard/users` (protected)

These serve the same data as the WebSocket events but as plain HTTP JSON — useful for server-side polling or debugging.

#### `POST /api/dashboard/refresh` (protected)

```typescript
@Post('api/dashboard/refresh')
@UseGuards(JwtAuthGuard)
async refresh() {
  await this.dashboardService.publishRefresh();
  // Internally: Redis PUBLISH dashboard.summary.refresh → all subscribers rebuild
  return { message: 'Refresh signal published' };
}
```

---

## 10. Mail Module (`src/mail/`)

### `mail.service.ts` — Nodemailer + JWT Token Generation

```typescript
@Injectable()
export class MailService {
  private transporter: nodemailer.Transporter;

  constructor(
    private readonly config: ConfigService,
    private readonly jwtService: JwtService,
  ) {
    // Create the SMTP transporter once at injection time
    // Equivalent of config/mail.php + MAIL_* env vars in Laravel
    this.transporter = nodemailer.createTransport({
      host: config.get<string>('MAIL_HOST', 'smtp.mailtrap.io'),
      port: config.get<number>('MAIL_PORT', 587),
      auth: {
        user: config.get<string>('MAIL_USER', ''),
        pass: config.get<string>('MAIL_PASS', ''),
      },
    });
  }

  // Generates a signed JWT verification link valid for 24 hours
  // Laravel equivalent: URL::signedRoute('email.verify', [...], now()->addHours(24))
  generateVerificationUrl(userId: string, email: string): string {
    const token = this.jwtService.sign(
      {
        sub:   userId,
        email: email,
        type:  'email-verification',  // Custom claim to distinguish from login tokens
      },
      { expiresIn: '24h' },
    );
    const appUrl = this.config.get<string>('APP_URL', 'http://localhost:3000');
    return `${appUrl}/email/verify?token=${token}`;
  }

  // Sends a verification email via SMTP
  // Laravel equivalent: Mail::to($email)->send(new VerificationEmail($user))
  async sendVerificationEmail(userId: string, email: string, name: string): Promise<void> {
    const verificationUrl = this.generateVerificationUrl(userId, email);

    await this.transporter.sendMail({
      from:    `"${this.config.get('MAIL_FROM_NAME', 'App')}" <${this.config.get('MAIL_FROM', 'noreply@example.com')}>`,
      to:      email,
      subject: 'Verify your email address',
      html: `
        <div style="font-family: sans-serif; max-width: 600px;">
          <h2>Hello ${name},</h2>
          <p>Click below to verify your email. This link expires in 24 hours.</p>
          <a href="${verificationUrl}" style="display:inline-block;padding:12px 24px;background:#4F46E5;color:white;border-radius:6px;">
            Verify Email Address
          </a>
        </div>
      `,
    });

    this.logger.log(`Verification email sent to ${email}`);
  }
}
```

**Why JWT instead of signed URLs?**

| Laravel `URL::signedRoute` | This implementation (JWT) |
|---|---|
| URL has `signature` query param | URL has `token` query param |
| Signature = HMAC hash of the URL | Token = JWT with claims |
| Requires Laravel routing + middleware | Can be verified by any JWT library |
| Revocable by changing `APP_KEY` | Revocable by changing `JWT_SECRET` |
| Stored in URL only | All data in token (no DB lookup) |

Both approaches are secure. The JWT approach is slightly more portable and requires no session/database state.

---

## 11. Static Frontend Pages (`public/`)

All pages use plain HTML + Tailwind CSS (loaded from CDN) + vanilla JavaScript. There is no build step for the frontend. Express serves these files directly from the `public/` directory.

### Page Architecture

```
/                               → public/index.html       (landing page with links)
/dashboard/login.html           → Login form
/dashboard/signup.html          → Registration form
/dashboard/queue.html           → Real-time queue dashboard (Socket.io)
/dashboard/users.html           → Real-time users dashboard (Socket.io)
```

JWT tokens are stored in `localStorage` as `jwt_token`. The pages read this value to detect authentication state and include the token in API calls.

### How `queue.html` Works (annotated)

```html
<!-- 1. Load Tailwind CSS from CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- 2. Load the Socket.io client (served automatically by Socket.io server) -->
<script src="/socket.io/socket.io.js"></script>

<script>
  // 3. Connect to the /dashboard WebSocket namespace
  const socket = io('/dashboard', {
    path: '/socket.io',
    transports: ['websocket', 'polling'],  // WebSocket with polling fallback
  });

  // 4. On successful connection, request current queue data
  socket.on('connect', () => {
    socket.emit('get-queue-summary');  // → DashboardGateway.onGetQueueSummary()
  });

  // 5. Receive queue data and update the DOM
  socket.on('queue-summary', (data) => {
    // data = { queues: [...], totals: { pending, reserved, delayed, failed, completed }, cachedAt }
    document.getElementById('totalPending').textContent = data.totals.pending.toLocaleString();
    // ... update all stat displays
  });

  // 6. Refresh button → triggers Redis pub/sub → all connected clients update
  document.getElementById('refreshBtn').addEventListener('click', () => {
    socket.emit('refresh-dashboard');  // → DashboardGateway.onRefreshDashboard()
    setTimeout(() => socket.emit('get-queue-summary'), 500); // Re-request after short delay
  });

  // 7. Auto-refresh every 10 seconds
  setInterval(() => socket.emit('get-queue-summary'), 10000);
</script>
```

**Laravel Livewire equivalent:**
```blade
<section wire:poll.10s="loadSummary">
    <p>Pending: {{ $summary['totals']['pending'] }}</p>
    <button wire:click="refreshSummary">Refresh via Redis Pub/Sub</button>
</section>
```

The key architectural difference:
- **Livewire** makes an HTTP AJAX request to the server on every poll; the server re-renders the component and returns a diff.
- **Socket.io** keeps a persistent WebSocket connection open; data is pushed from the server to all clients instantly without polling round-trips.

---

## 12. Complete Data-Flow Walkthroughs

### Flow 1: User Registration

```
Browser             NestJS              MongoDB         SMTP
──────              ──────              ───────         ────
POST /auth/register
  { name, email, password }
        │
        ▼
  ValidationPipe validates CreateUserDto
  (IsEmail, IsString, MinLength)
        │
        ▼
  AuthController.register()
        │
        ├─► UsersService.create()
        │       ├── Find existing (ConflictException if duplicate)
        │       ├── bcrypt.hash(password, 10)
        │       └── new User({...}).save() ──────────────► users collection
        │
        ├─► MailService.sendVerificationEmail() ── fire-and-forget
        │       ├── jwtService.sign({ sub, email, type:'email-verification' }, '24h')
        │       └── transporter.sendMail(to: email) ─────────────────────────────────► SMTP
        │
        └─► HTTP 201 { message, user: { _id, name, email } }
```

### Flow 2: Login

```
Browser             NestJS              MongoDB
──────              ──────              ───────
POST /auth/login
  { email, password }
        │
        ▼
  ValidationPipe validates LoginDto
        │
        ▼
  AuthController.login()
        │
        ▼
  AuthService.login(email, password)
        ├── UsersService.findByEmail(email)
        │       └── findOne({ email }).select('+password') ──► users collection
        │
        ├── bcrypt.compare(password, user.password) → true/false
        │
        ├── jwtService.sign({ sub: user._id, email })
        │
        └─► HTTP 200 { access_token: 'eyJ...', user: { ... } }
```

### Flow 3: Bulk User Generation

```
Browser             NestJS              Redis (Bull)     MongoDB
──────              ──────              ────────────     ───────
POST /users/generate
  { total: 50000, chunkSize: 500 }
        │
        ▼
  UsersController.generate()
        │
        ├── bcrypt.hash('password_<runId>', 10)   ← ONE hash for ALL jobs
        │
        ├── Build 100 jobs (50000 / 500)
        │       Each: { startIndex, chunkSize: 500, runId, passwordHash }
        │
        └── userImportsQueue.addBulk(jobs) ──────► bull:user-imports:wait (100 entries)
        │
        └─► HTTP 202 { jobsDispatched: 100, runId, total: 50000, ... }

                        ↑ returns immediately

                    Bull processes jobs in background:
                    For each job on queue:
                    InsertUsersProcessor.handle(job)
                        ├── Build 500 user objects (name, email, passwordHash)
                        └── UsersService.bulkInsert(users) ───────────────────► insertMany
```

### Flow 4: Real-Time Dashboard Refresh

```
Browser-A           Browser-B           NestJS                Redis
─────────           ─────────           ──────                ─────
Clicks "Refresh"
socket.emit('refresh-dashboard')
        │
        ▼
  DashboardGateway.onRefreshDashboard()
        │
        ▼
  dashboardService.publishRefresh()
        │
        └────────────────────────────────────────► PUBLISH dashboard.summary.refresh

                                            DashboardService.redisSubscriber
                                            receives the message
                                                    │
                                                    ▼
                                            onRefreshCallback() fires
                                                    │
                                                    ├── buildAndCacheQueueSummary()
                                                    │       ├── getJobCounts() (Bull)
                                                    │       ├── LLEN/ZCARD for each queue
                                                    │       └── SETEX dashboard:queue_summary
                                                    │
                                                    ├── usersService.getSummary()
                                                    │       └── MongoDB aggregate queries
                                                    │
                                                    └── server.emit('queue-summary', ...)
                                                        server.emit('users-summary', ...)
                                                                │               │
                                                                ▼               ▼
                                                          Browser-A        Browser-B
                                                          (updates DOM)    (updates DOM)
```

### Flow 5: Email Verification

```
User's Email Client         Browser             NestJS              MongoDB       Redis
──────────────────          ───────             ──────              ───────       ─────
Clicks verification link
GET /email/verify?token=<JWT>
                                    │
                                    ▼
                              DashboardController.verifyEmail(token)
                                    │
                                    ▼
                              DashboardService.verifyEmailToken(token)
                                    │
                                    ├── jwtService.verify(token, secret)
                                    │       Checks: signature, expiry, type='email-verification'
                                    │
                                    ├── UsersService.markEmailVerified(payload.sub)
                                    │       └── findByIdAndUpdate(id, { emailVerifiedAt: now })──► MongoDB
                                    │
                                    └── redisClient.del('dashboard:users_summary') ──────────────► Redis
                                            (bust cache so next dashboard load shows new count)
                                    │
                                    └─► HTTP 200 { message: 'Email verified' }
```

---

## 13. Redis Architecture — Keys, Channels & TTLs

Bull uses Redis internally to store job queues. The app also uses Redis directly for caching and pub/sub.

### Bull Redis Keys (managed by Bull, not by the app)

| Key Pattern | Type | Contents |
|---|---|---|
| `bull:<queue-name>:wait` | List | Jobs waiting to be processed (FIFO) |
| `bull:<queue-name>:active` | Sorted Set | Jobs currently being processed |
| `bull:<queue-name>:completed` | Sorted Set | Finished jobs |
| `bull:<queue-name>:failed` | Sorted Set | Failed jobs with error details |
| `bull:<queue-name>:delayed` | Sorted Set | Jobs scheduled for future execution |
| `bull:<queue-name>:id` | String (counter) | Auto-increment job ID |

The dashboard reads `llen bull:<name>:wait` (pending jobs), `zcard bull:<name>:active` (active jobs), and `zcard bull:<name>:delayed` (delayed jobs) directly for the per-queue breakdown.

### App Redis Keys (managed by DashboardService)

| Key | Type | TTL | Set by | Read by |
|---|---|---|---|---|
| `dashboard:queue_summary` | String (JSON) | 3600s | `buildAndCacheQueueSummary()` | `getQueueSummary()` |
| `dashboard:users_summary` | String (JSON) | 3600s | `cacheUsersSummary()` | `getUsersSummary()` |

### Pub/Sub Channels

| Channel | Publisher | Subscriber | Purpose |
|---|---|---|---|
| `dashboard.summary.refresh` | Any client via `publishRefresh()` | `DashboardService.onModuleInit()` | Trigger a full rebuild of both summaries |
| `dashboard.summary.updated` | `buildAndCacheQueueSummary()` + `cacheUsersSummary()` | (Not subscribed in this app — reserved for future use) | Signal that fresh data was written to cache |

---

## 14. Environment Variables — Full Reference

Create a `.env` file at the project root (copy from `.env.example`).

| Variable | Default | Required | Description |
|---|---|---|---|
| `APP_PORT` | `3000` | No | HTTP port the server listens on |
| `APP_NAME` | `"Redis NestJS Dashboard"` | No | Application display name |
| `NODE_ENV` | `development` | No | `development` or `production` |
| `JWT_SECRET` | `changeme` | **Yes** | Secret key for signing JWT tokens. Change in production |
| `JWT_EXPIRES_IN` | `7d` | No | JWT lifetime. Supports `7d`, `24h`, `3600` (seconds) |
| `MONGODB_URI` | `mongodb://localhost:27017/laravel12_redis` | No | Full MongoDB connection URI |
| `REDIS_HOST` | `127.0.0.1` | No | Redis server hostname |
| `REDIS_PORT` | `6379` | No | Redis server port |
| `REDIS_PASSWORD` | _(empty)_ | No | Redis password (leave empty for local dev) |
| `MAIL_HOST` | `smtp.mailtrap.io` | No | SMTP server host. Use [Mailtrap](https://mailtrap.io) for testing |
| `MAIL_PORT` | `587` | No | SMTP server port |
| `MAIL_USER` | _(empty)_ | No | SMTP username |
| `MAIL_PASS` | _(empty)_ | No | SMTP password |
| `MAIL_FROM` | `noreply@example.com` | No | Sender email address |
| `MAIL_FROM_NAME` | `"Redis NestJS App"` | No | Sender display name |
| `APP_URL` | `http://localhost:3000` | No | Base URL for email verification links |
| `QUEUE_NAMES` | `default,user-imports,email-verifications` | No | Comma-separated queue names to monitor in dashboard |

**Laravel equivalent:**

| NestJS variable | Laravel variable |
|---|---|
| `JWT_SECRET` | `APP_KEY` (used by Sanctum internally) |
| `MONGODB_URI` | `DB_HOST`, `DB_PORT`, `DB_DATABASE`, etc. |
| `REDIS_HOST` | `REDIS_HOST` (same) |
| `REDIS_PORT` | `REDIS_PORT` (same) |
| `MAIL_HOST` | `MAIL_HOST` (same) |
| `APP_URL` | `APP_URL` (same) |

---

## 15. Security Architecture

### Authentication Flow

```
Client                         NestJS
──────                         ──────
1. POST /auth/login            AuthController.login()
   { email, password }              │
                                    ├── bcrypt.compare(password, storedHash)
                                    └── jwtService.sign({ sub: userId, email }, '7d')
                                            └── Returns access_token

2. Subsequent requests         JwtStrategy.validate()
   Authorization: Bearer <token>    │
                                    ├── Extract token from header
                                    ├── Verify signature with JWT_SECRET
                                    ├── Check expiry
                                    └── Set req.user = { userId, email }
```

### JWT Token Structure

The JWT payload contains:
```json
{
  "sub": "6788abc123...",   // MongoDB ObjectId of the user
  "email": "user@example.com",
  "iat": 1700000000,        // Issued at (Unix timestamp)
  "exp": 1700604800         // Expires at (iat + JWT_EXPIRES_IN)
}
```

Email verification tokens have an additional `type` field:
```json
{
  "sub": "6788abc123...",
  "email": "user@example.com",
  "type": "email-verification",
  "iat": 1700000000,
  "exp": 1700086400         // 24 hours
}
```

The `type` field prevents a login token from being used as a verification token.

### Password Hashing

Both registration and bulk generation use `bcrypt` with salt rounds = 10:
```typescript
// Registration (UsersService.create)
const hashed = await bcrypt.hash(dto.password, 10);

// Bulk generation (UsersController.generate) — pre-computed once for all jobs
const passwordHash = await bcrypt.hash(`password_${runId}`, 10);
```

This is identical to Laravel's `Hash::make($password)` which also uses bcrypt with cost = 10 by default.

### Input Validation

Every route that accepts a body uses a DTO + `class-validator`. The global `ValidationPipe` in `main.ts` ensures no unvalidated data reaches a controller:

```typescript
app.useGlobalPipes(new ValidationPipe({
  whitelist: true,              // Strip any field not declared on the DTO
  transform: true,              // Auto-cast types (string "1" → number 1)
  forbidNonWhitelisted: true,   // Return 400 if unknown fields are present
}));
```

This is equivalent to calling `$request->validated()` in every Laravel controller method (which returns only the fields declared in `FormRequest::rules()`).

---

## 16. Running the Application

### Prerequisites

- Node.js ≥ 18
- MongoDB 6+ running on `localhost:27017`
- Redis 6+ running on `localhost:6379`

### Local Setup

```bash
# Install dependencies
npm install

# Copy and edit environment config
cp .env.example .env
# Edit .env: set JWT_SECRET and MAIL_* settings

# Start in development mode (hot reload on file change)
npm run start:dev
```

App is available at:
- Landing page: `http://localhost:3000/`
- Queue Dashboard: `http://localhost:3000/dashboard/queue.html`
- Users Dashboard: `http://localhost:3000/dashboard/users.html`
- Swagger API Docs: `http://localhost:3000/api/docs`

### GitHub Codespaces

The `.devcontainer/` folder sets up a complete environment automatically:
- Installs MongoDB 7 and Redis
- Starts both services
- Runs `npm install`
- Copies `.env.example` → `.env`

Just open the repo in a Codespace, wait for setup, then run `npm run start:dev`.

### Available npm Scripts

| Command | Description |
|---|---|
| `npm run start:dev` | Development mode with hot reload (TypeScript watch) |
| `npm run build` | Compile TypeScript to `dist/` |
| `npm run start:prod` | Run compiled production build from `dist/main.js` |
| `npm run lint` | Run ESLint on all TypeScript files |
| `npm test` | Run unit tests with Jest |

### What You Do NOT Need to Run

Unlike Laravel, there is **no separate worker process**. You do not need to run:
- `php artisan queue:work` — Bull processors start inside the same process as the web server
- `php artisan dashboard:redis-listen` — `DashboardService.onModuleInit()` handles this automatically

---

## 17. Laravel → NestJS Cheat Sheet

### Concepts

| You know this in Laravel | This is the NestJS equivalent | Where in this project |
|---|---|---|
| `bootstrap/app.php` | `src/main.ts` | Application entry point |
| `config/app.php` providers | `AppModule` imports array | `src/app.module.ts` |
| `AppServiceProvider::register()` | `@Module` `providers: [...]` | Every `*.module.ts` |
| `AppServiceProvider::boot()` | `OnModuleInit.onModuleInit()` | `src/dashboard/dashboard.service.ts` |
| Service Container / IoC | NestJS DI container via `@Injectable()` | Every `*.service.ts` |
| `routes/api.php` | `@Controller` + `@Get`/`@Post` decorators | Every `*.controller.ts` |
| `FormRequest` | DTO class + `class-validator` decorators | Every `dto/*.ts` |
| Eloquent `Model` + migration | Mongoose `@Schema` + `SchemaFactory` | `src/users/schemas/user.schema.ts` |
| `$hidden = ['password']` | `@Prop({ select: false })` | `src/users/schemas/user.schema.ts` |
| `$fillable` | Fields declared with `@Prop()` | `src/users/schemas/user.schema.ts` |
| `$casts = ['x' => 'datetime']` | `@Prop({ type: Date })` | `src/users/schemas/user.schema.ts` |
| `$table->timestamps()` | `@Schema({ timestamps: true })` | `src/users/schemas/user.schema.ts` |
| `auth:sanctum` middleware | `@UseGuards(JwtAuthGuard)` | Controllers |
| `auth()->user()` | `req.user` via `@Request() req` | `src/auth/auth.controller.ts` |
| `Hash::make($password)` | `bcrypt.hash(password, 10)` | `src/users/users.service.ts` |
| `Hash::check($plain, $hash)` | `bcrypt.compare(plain, hash)` | `src/auth/auth.service.ts` |
| `$user->createToken('x')` | `jwtService.sign({ sub, email })` | `src/auth/auth.service.ts` |
| `ShouldQueue` job class | `@Processor` + `@Process` class | `src/queue/processors/` |
| `dispatch(new Job(...))` | `queue.add('name', data)` | `src/users/users.controller.ts` |
| `queue.addBulk(...)` | `queue.addBulk(jobs)` | `src/users/users.controller.ts` |
| `php artisan queue:work redis` | Not needed — Bull runs in-process | Automatic |
| `Redis::get/set` | `ioredis` `get/set/setex` | `src/dashboard/dashboard.service.ts` |
| `Redis::publish(channel, msg)` | `redisClient.publish(channel, msg)` | `src/dashboard/dashboard.service.ts` |
| `Redis::subscribe(...)` blocking | `ioredis` event-driven `on('message')` | `src/dashboard/dashboard.service.ts` |
| `Mailable` class | Nodemailer in `MailService` | `src/mail/mail.service.ts` |
| `URL::signedRoute` | JWT with `type: 'email-verification'` | `src/mail/mail.service.ts` |
| Livewire component | `@WebSocketGateway` + static HTML | `src/dashboard/dashboard.gateway.ts` |
| `wire:poll.10s` | `setInterval(() => socket.emit(...), 10000)` | `public/dashboard/*.html` |
| `wire:click="action"` | `button.addEventListener → socket.emit(...)` | `public/dashboard/*.html` |
| Blade template | Static HTML in `public/` | `public/dashboard/*.html` |
| `config()` helper | `ConfigService.get()` | `@nestjs/config` |
| `Log::info('...')` | `new Logger(ClassName).log('...')` | Every `*.service.ts` |
| Swagger (`l5-swagger`) | `@nestjs/swagger` decorators (built-in) | All controllers + DTOs |

### Query Methods

| Laravel Eloquent | Mongoose (NestJS) |
|---|---|
| `User::create($data)` | `new this.userModel(data).save()` |
| `User::where('email', $e)->first()` | `this.userModel.findOne({ email: e }).exec()` |
| `User::find($id)` | `this.userModel.findById(id).exec()` |
| `User::where(...)->update([...])` | `this.userModel.findByIdAndUpdate(id, {...}).exec()` |
| `User::count()` | `this.userModel.countDocuments().exec()` |
| `User::where('x', null)->count()` | `this.userModel.countDocuments({ x: { $ne: null } }).exec()` |
| `User::insert($rows)` | `this.userModel.insertMany(rows, { ordered: false })` |
| `User::paginate($n)` | `this.userModel.find().skip(skip).limit(limit).exec()` |
| `User::latest()->first()` | `this.userModel.findOne().sort({ createdAt: -1 }).exec()` |
| `User::whereIn('id', $ids)->get()` | `this.userModel.find({ _id: { $in: ids } }).exec()` |

### Validation Decorators

| Laravel rule | `class-validator` decorator |
|---|---|
| `required` | `@IsNotEmpty()` |
| `string` | `@IsString()` |
| `email` | `@IsEmail()` |
| `min:8` (string length) | `@MinLength(8)` |
| `integer` | `@IsInt()` |
| `min:1` (numeric) | `@Min(1)` |
| `max:100` (numeric) | `@Max(100)` |
| `nullable` / `sometimes` | `@IsOptional()` |
| Type casting in query strings | `@Type(() => Number)` + `transform: true` on ValidationPipe |
