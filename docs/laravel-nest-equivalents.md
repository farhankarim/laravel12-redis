# Laravel Core Concepts → NestJS Equivalents

This document is a **side-by-side reference** for Laravel developers reading the NestJS codebase. For every core Laravel building block, it explains the NestJS equivalent used in this project, how it works, and where to find it in the source code.

---

## 1. Service Container & Dependency Injection

### Laravel
Laravel's IoC container automatically resolves constructor dependencies when you type-hint a class. Bindings are registered in `AppServiceProvider` or via `$app->singleton(...)`.

```php
// Type-hint in a constructor → Laravel resolves it automatically
class AuthController extends Controller
{
    public function __construct(private UsersService $users) {}
}
```

### NestJS equivalent
NestJS has its own **DI container**. Any class decorated with `@Injectable()` can be injected into any constructor, as long as it is listed in the `providers` array of the module that owns it (or exported from a module that is imported).

```typescript
// @Injectable() registers the class with NestJS's container
@Injectable()
export class AuthService { ... }

// Constructor injection — identical pattern to Laravel
@Controller('auth')
export class AuthController {
    constructor(private readonly authService: AuthService) {}
}
```

**Files in this project:**
- Every `*.service.ts` file has `@Injectable()`.
- Every `*.module.ts` lists providers and exports.

---

## 2. Service Providers & Modules

### Laravel
`AppServiceProvider`, `AuthServiceProvider`, `RouteServiceProvider`, etc. register services, event listeners, routes, and middleware. They are loaded automatically via `config/app.php`.

```php
// app/Providers/AuthServiceProvider.php
class AuthServiceProvider extends ServiceProvider
{
    public function register(): void {
        $this->app->singleton(JWTService::class, fn () => new JWTService(config('jwt.secret')));
    }
}
```

### NestJS equivalent: `@Module`
A **Module** is both the service provider and the route registrar rolled into one. Each feature folder (`auth/`, `users/`, etc.) contains its own module that declares what it provides and what it needs.

```typescript
// src/auth/auth.module.ts
@Module({
    imports: [
        UsersModule,          // = use exports from UsersModule
        PassportModule,
        JwtModule.registerAsync({ ... }), // = singleton registration
    ],
    providers: [AuthService, JwtStrategy],  // = $app->singleton(...)
    controllers: [AuthController],          // = route registration
    exports: [AuthService, JwtModule],      // = other modules can inject these
})
export class AuthModule {}
```

**Key concepts:**
| Laravel | NestJS |
|---|---|
| `AppServiceProvider::register()` | `providers: [...]` in `@Module` |
| `AppServiceProvider::boot()` | `OnModuleInit` lifecycle hook |
| `config/app.php` providers array | `imports: [...]` in root `AppModule` |
| `$app->singleton(Interface, Impl)` | `{ provide: TOKEN, useClass: Impl }` in providers |

**Files in this project:**
- `src/app.module.ts` — root module (= `bootstrap/app.php` + `config/app.php`)
- `src/auth/auth.module.ts`, `src/users/users.module.ts`, etc.

---

## 3. Controllers

### Laravel
```php
// app/Http/Controllers/UserController.php
class UserController extends Controller
{
    public function index(Request $request) {
        return User::paginate(20);
    }

    public function store(CreateUserRequest $request) {
        return User::create($request->validated());
    }
}

// routes/api.php
Route::apiResource('users', UserController::class)->middleware('auth:sanctum');
```

### NestJS equivalent
Routes are defined **directly on the controller class** using decorators. There is no separate routes file.

```typescript
// src/users/users.controller.ts
@ApiTags('users')
@Controller('users')          // prefix = /users
export class UsersController {

    @Get()                    // GET /users
    @UseGuards(JwtAuthGuard)
    async findAll(@Query('page') page = 1, @Query('limit') limit = 20) {
        return this.usersService.findPaginated(+page, +limit);
    }

    @Post()                   // POST /users
    @UseGuards(JwtAuthGuard)
    async create(@Body() dto: CreateUserDto) {
        return this.usersService.create(dto);
    }
}
```

**Key decorator mapping:**
| Laravel | NestJS |
|---|---|
| `Route::get(...)` | `@Get('path')` |
| `Route::post(...)` | `@Post('path')` |
| `$request->input('x')` | `@Body() dto` / `@Query('x') x` |
| `$request->route('id')` | `@Param('id') id` |
| `->middleware('auth:sanctum')` | `@UseGuards(JwtAuthGuard)` |
| `->prefix('api')` | `@Controller('api')` prefix |

---

## 4. Form Requests & Validation

### Laravel
```php
// app/Http/Requests/CreateUserRequest.php
class CreateUserRequest extends FormRequest
{
    public function rules(): array {
        return ['name' => 'required|string', 'email' => 'required|email', 'password' => 'required|min:8'];
    }
}

// Controller
public function store(CreateUserRequest $request) { ... } // auto-validated
```

### NestJS equivalent: DTO + `class-validator` + `ValidationPipe`
```typescript
// src/users/dto/create-user.dto.ts
export class CreateUserDto {
    @IsString() @IsNotEmpty()  name: string;
    @IsEmail()                 email: string;
    @IsString() @MinLength(8)  password: string;
}

// Controller
async create(@Body() dto: CreateUserDto) { ... } // auto-validated by ValidationPipe
```

The global `ValidationPipe` (registered in `main.ts`) intercepts every request, validates the body against the DTO, and throws a `400 Bad Request` (with field-level errors) if validation fails — exactly like a `FormRequest`.

**Decorator mapping:**
| Laravel validation rule | `class-validator` decorator |
|---|---|
| `required` | `@IsNotEmpty()` |
| `string` | `@IsString()` |
| `email` | `@IsEmail()` |
| `min:8` (string) | `@MinLength(8)` |
| `integer` | `@IsInt()` |
| `min:1` (number) | `@Min(1)` |
| `max:100` (number) | `@Max(100)` |
| `nullable` / `sometimes` | `@IsOptional()` |

---

## 5. Eloquent ORM → Mongoose ODM

### Laravel (Eloquent)
```php
// app/Models/User.php
class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden   = ['password'];
    protected $casts    = ['email_verified_at' => 'datetime'];

    public static function boot() {
        parent::boot();
        static::creating(fn ($u) => $u->email = strtolower($u->email));
    }
}
```

### NestJS equivalent: Mongoose Schema + `@nestjs/mongoose`
```typescript
// src/users/schemas/user.schema.ts
@Schema({ timestamps: true })   // adds createdAt + updatedAt automatically
export class User {
    @Prop({ required: true })
    name: string;

    @Prop({ required: true, unique: true, lowercase: true, trim: true })
    email: string;       // lowercase: true = equivalent to the boot() hook above

    @Prop({ required: true, select: false })
    password: string;    // select: false = equivalent to $hidden = ['password']

    @Prop({ type: Date, default: null })
    emailVerifiedAt: Date | null;
}
export const UserSchema = SchemaFactory.createForClass(User);
```

**Key concept mapping:**
| Eloquent | Mongoose / Mongoose in NestJS |
|---|---|
| `$fillable` | `@Prop({ required: true })` marks required fields |
| `$hidden = ['password']` | `@Prop({ select: false })` |
| `$casts = ['x' => 'datetime']` | `@Prop({ type: Date })` |
| `protected $timestamps = true` | `@Schema({ timestamps: true })` |
| `unique:users` migration | `@Prop({ unique: true })` |
| `->save()` | `new this.model(data).save()` |
| `->update([...])` | `model.findByIdAndUpdate(id, data)` |
| `->delete()` | `model.findByIdAndDelete(id)` |
| `->where('x', y)->get()` | `model.find({ x: y }).exec()` |
| `->insert([...])` | `model.insertMany([...])` |

---

## 6. Authentication & Guards

### Laravel (Sanctum/Passport + Middleware)
```php
// auth guard = middleware on routes
Route::middleware('auth:sanctum')->get('/profile', ...);

// Reading the authenticated user
auth()->user()
$request->user()
```

### NestJS equivalent: Passport + Guards + JWT Strategy
NestJS uses **Passport.js** (the standard Node.js auth library) wrapped in `@nestjs/passport`.

```typescript
// src/auth/jwt.strategy.ts  — Validates the token, sets req.user
@Injectable()
export class JwtStrategy extends PassportStrategy(Strategy) {
    constructor(config: ConfigService) {
        super({
            jwtFromRequest: ExtractJwt.fromAuthHeaderAsBearerToken(),
            secretOrKey: config.get('JWT_SECRET'),
        });
    }
    // Whatever is returned here becomes req.user
    async validate(payload: { sub: string; email: string }) {
        return { userId: payload.sub, email: payload.email };
    }
}

// src/auth/jwt-auth.guard.ts  — Apply to a route like middleware
@Injectable()
export class JwtAuthGuard extends AuthGuard('jwt') {}

// Controller usage
@UseGuards(JwtAuthGuard)
@Get('profile')
async profile(@Request() req: any) {
    const user = req.user; // set by JwtStrategy.validate()
}
```

**Concept mapping:**
| Laravel | NestJS |
|---|---|
| `auth:sanctum` middleware | `@UseGuards(JwtAuthGuard)` |
| `auth()->user()` | `req.user` (populated by `JwtStrategy.validate()`) |
| `$user->createToken(...)` | `jwtService.sign({ sub, email })` |
| `Hash::make($password)` | `bcrypt.hash(password, 10)` |
| `Hash::check($plain, $hash)` | `bcrypt.compare(plain, hash)` |

---

## 7. Queues & Jobs

### Laravel (Redis Queue)
```php
// Defining a job
class InsertUsersChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue;

    public function __construct(private array $data) {}

    public function handle(UsersService $service): void {
        $service->bulkInsert($this->data);
    }
}

// Dispatching
InsertUsersChunkJob::dispatch($data)->onQueue('user-imports');

// Running workers (separate terminal)
php artisan queue:work redis --queue=user-imports
```

### NestJS equivalent: Bull + `@Processor` / `@Process`
```typescript
// src/queue/processors/insert-users.processor.ts

@Processor('user-imports')        // = ->onQueue('user-imports')
export class InsertUsersProcessor {
    constructor(private readonly usersService: UsersService) {}

    @Process('insert-users-chunk') // = job name
    async handle(job: Job<InsertUsersChunkJobData>): Promise<void> {
        const { startIndex, chunkSize, runId, passwordHash } = job.data;
        // ... build users array ...
        await this.usersService.bulkInsert(users);
    }
}

// Dispatching from a controller
@InjectQueue('user-imports') private queue: Queue

await this.queue.addBulk(jobs); // = dispatch() × N
```

**Key differences:**
- Bull workers run **inside the same process** as the web server. No `php artisan queue:work` command needed.
- Job data is a plain JavaScript object (`job.data`) instead of a PHP class instance.
- `@InjectQueue('queue-name')` is a constructor decorator that injects the named Bull queue, equivalent to `dispatch()->onQueue('name')`.

---

## 8. Redis: Cache & Pub/Sub

### Laravel
```php
// Facade-based access
Redis::get('dashboard:queue_summary');
Redis::set('dashboard:queue_summary', json_encode($summary));
Redis::setex('key', 3600, $value);         // with TTL
Redis::publish('dashboard.summary.refresh', json_encode($payload));

// Subscription (blocking call — needs separate process)
Redis::subscribe(['dashboard.summary.refresh'], function ($message) {
    // handle message
});
```

### NestJS equivalent: `ioredis` client
```typescript
// src/dashboard/dashboard.service.ts
import Redis from 'ioredis';

// Two separate clients: one for commands, one for subscriptions
// (Redis does not allow commands while in subscribe mode)
this.redisClient     = new Redis(redisOpts);  // get/set/publish
this.redisSubscriber = new Redis(redisOpts);  // subscribe only

// Get/set with TTL (= Redis::setex)
await this.redisClient.get('dashboard:queue_summary');
await this.redisClient.setex('dashboard:queue_summary', 3600, JSON.stringify(summary));

// Publish (= Redis::publish)
await this.redisClient.publish('dashboard.summary.refresh', JSON.stringify({ ts: Date.now() }));

// Subscribe (= Redis::subscribe — but non-blocking, event-driven)
this.redisSubscriber.subscribe('dashboard.summary.refresh');
this.redisSubscriber.on('message', async (channel, message) => {
    if (channel === 'dashboard.summary.refresh') {
        await this.onRefreshCallback();
    }
});
```

**Key difference:** Laravel's `Redis::subscribe` is a **blocking** call that ties up a process. Node's `ioredis` subscribe is **event-driven** (non-blocking), so it runs inside the main process without blocking other requests.

---

## 9. Mail

### Laravel (Mailable)
```php
// Defining a mailable
class VerificationEmail extends Mailable
{
    public function envelope() { return new Envelope(subject: 'Verify your email'); }
    public function content()  { return new Content(view: 'emails.verification'); }
}

// Using URL::signedRoute for verification links
$url = URL::signedRoute('email.verify', ['id' => $user->id, 'hash' => sha1($user->email)]);

// Sending
Mail::to($user->email)->send(new VerificationEmail($user, $url));
```

### NestJS equivalent: Nodemailer
```typescript
// src/mail/mail.service.ts

// Verification link = signed JWT (no database token needed)
generateVerificationUrl(userId: string, email: string): string {
    const token = this.jwtService.sign(
        { sub: userId, email, type: 'email-verification' },
        { expiresIn: '24h' },
    );
    return `${appUrl}/email/verify?token=${token}`;
}

// Sending via Nodemailer (= Mail::to()->send())
await this.transporter.sendMail({
    from: `"App Name" <noreply@example.com>`,
    to: email,
    subject: 'Verify your email address',
    html: `<a href="${verificationUrl}">Verify Email Address</a>`,
});
```

**Key difference:** Laravel's `URL::signedRoute` stores a hash in the URL and validates it against the user's current email. Here, the verification link contains a **self-contained JWT** with a 24-hour expiry. No database table of tokens is needed — the token itself carries the proof.

---

## 10. Configuration

### Laravel
```php
// .env
DB_HOST=127.0.0.1
JWT_SECRET=secret

// config/database.php
'host' => env('DB_HOST', '127.0.0.1'),

// Usage anywhere
config('database.connections.mysql.host')
env('JWT_SECRET')
```

### NestJS equivalent: `@nestjs/config` + `ConfigService`
```typescript
// src/app.module.ts
ConfigModule.forRoot({ isGlobal: true }),  // reads .env automatically

// Injection in any service
@Injectable()
export class AuthService {
    constructor(private config: ConfigService) {}

    getSecret() {
        return this.config.get<string>('JWT_SECRET', 'changeme');
    }
}
```

`ConfigModule.forRoot({ isGlobal: true })` is equivalent to making `config()` globally available in Laravel. The `.env` file format is identical.

---

## 11. Lifecycle Hooks

### Laravel
```php
// AppServiceProvider::boot() — runs after all providers are registered
public function boot(): void {
    // Register observers, validators, etc.
}

// Long-running subscriber command
Artisan::command('dashboard:redis-listen', function () {
    Redis::subscribe([...], fn ($msg) => ...);
});
```

### NestJS equivalent: `OnModuleInit` / `OnModuleDestroy`
```typescript
// src/dashboard/dashboard.service.ts
@Injectable()
export class DashboardService implements OnModuleInit, OnModuleDestroy {

    onModuleInit() {
        // Runs when the module is fully initialised — like AppServiceProvider::boot()
        // Starts Redis pub/sub subscription
        this.redisSubscriber = new Redis(opts);
        this.redisSubscriber.subscribe(this.REFRESH_CHANNEL);
        this.redisSubscriber.on('message', async (channel, msg) => { ... });
    }

    onModuleDestroy() {
        // Runs when the process is shutting down — like PHP destructor
        this.redisClient?.disconnect();
        this.redisSubscriber?.disconnect();
    }
}
```

This replaces the `php artisan dashboard:redis-listen` command. The subscription starts automatically with the app.

---

## 12. Swagger / API Documentation

### Laravel (third-party package, e.g. `l5-swagger`)
```php
// @OA annotations on controllers
/**
 * @OA\Post(path="/auth/login", tags={"auth"}, ...)
 */
```

### NestJS equivalent: `@nestjs/swagger` (built-in, first-party)
```typescript
// src/auth/auth.controller.ts
@ApiTags('auth')            // = tags group in Swagger UI
@Controller('auth')
export class AuthController {

    @Post('register')
    @ApiOperation({ summary: 'Register a new user' })
    @ApiResponse({ status: 201, description: 'Account created' })
    async register(@Body() dto: CreateUserDto) { ... }
}
```

The Swagger UI is available at `/api/docs` once the app is running. DTOs with `@ApiProperty` decorators are automatically reflected in the Swagger schema. `@ApiBearerAuth()` adds the JWT lock icon to protected routes.

---

## 13. WebSockets / Real-time

### Laravel (Livewire + polling, or Laravel Echo + Pusher)
```blade
{{-- Livewire polling --}}
<section wire:poll.10s="loadSummary"> ... </section>

{{-- or Laravel Echo + Pusher for true WebSockets --}}
Echo.channel('dashboard').listen('SummaryUpdated', (e) => { ... });
```

### NestJS equivalent: `@WebSocketGateway` + Socket.io
```typescript
// src/dashboard/dashboard.gateway.ts
@WebSocketGateway({ cors: { origin: '*' }, namespace: '/dashboard' })
export class DashboardGateway {

    @WebSocketServer() server: Server;  // = broadcast to all

    @SubscribeMessage('get-queue-summary')   // = Echo.listen(...)
    async onGetQueueSummary(client: Socket) {
        const summary = await this.dashboardService.getQueueSummary();
        client.emit('queue-summary', summary);  // = event to this client only
    }
}
```

**Browser side (in `public/dashboard/queue.html`):**
```javascript
const socket = io('/dashboard');          // connect to the namespace
socket.emit('get-queue-summary');         // request data
socket.on('queue-summary', (data) => {   // receive data
    // update DOM
});
```

---

## 14. Logging

### Laravel
```php
Log::info('Inserted chunk');
Log::error("Failed to send email: {$e->getMessage()}");
```

### NestJS equivalent: built-in `Logger`
```typescript
private readonly logger = new Logger(InsertUsersProcessor.name);

this.logger.log('Inserted chunk [1–500] for run abc123');
this.logger.error(`Failed to send email to user@example.com`);
```

`Logger` is a first-party NestJS class. It writes structured, colour-coded output to stdout with the class name as a prefix — similar to Laravel's default `daily` log channel.

---

## Quick Reference Summary

| Laravel Concept | NestJS Equivalent | File in this project |
|---|---|---|
| `AppServiceProvider` | `AppModule` (`@Module`) | `src/app.module.ts` |
| Service Container / DI | `@Injectable()` + `providers: []` | Every `*.service.ts` |
| `routes/api.php` | `@Controller` + `@Get`/`@Post` decorators | Every `*.controller.ts` |
| `FormRequest` | DTO + `class-validator` decorators | Every `dto/*.ts` |
| Eloquent Model | Mongoose `@Schema` + `SchemaFactory` | `src/users/schemas/user.schema.ts` |
| Eloquent queries | Mongoose `Model<T>` methods | `src/users/users.service.ts` |
| `auth:sanctum` middleware | `JwtAuthGuard` (`@UseGuards`) | `src/auth/jwt-auth.guard.ts` |
| `auth()->user()` | `req.user` (set by `JwtStrategy`) | `src/auth/jwt.strategy.ts` |
| `Hash::make()` / `Hash::check()` | `bcrypt.hash()` / `bcrypt.compare()` | `src/auth/auth.service.ts` |
| `ShouldQueue` Job | `@Processor` + `@Process` class | `src/queue/processors/` |
| `dispatch(new Job(...))` | `queue.add('name', data)` | `src/users/users.controller.ts` |
| `php artisan queue:work` | Bull processor runs in-process | Automatic |
| `Redis::get/set/publish` | `ioredis` client | `src/dashboard/dashboard.service.ts` |
| `Redis::subscribe` (blocking) | `ioredis` `on('message')` (event-driven) | `src/dashboard/dashboard.service.ts` |
| `Mailable` + `Mail::send()` | `MailService` + Nodemailer | `src/mail/mail.service.ts` |
| `URL::signedRoute` (email verify) | JWT with `type: 'email-verification'` | `src/mail/mail.service.ts` |
| `AppServiceProvider::boot()` | `OnModuleInit.onModuleInit()` | `src/dashboard/dashboard.service.ts` |
| `php artisan dashboard:redis-listen` | `onModuleInit()` (auto-starts) | `src/dashboard/dashboard.service.ts` |
| Livewire component | `@WebSocketGateway` + static HTML | `src/dashboard/dashboard.gateway.ts` |
| Blade template | Static HTML in `public/` | `public/dashboard/*.html` |
| `config()` helper | `ConfigService.get()` | `@nestjs/config` |
| `Log::info()` | `new Logger(name).log()` | Every `*.service.ts` |
| Swagger (l5-swagger) | `@nestjs/swagger` (built-in) | `src/main.ts` + all controllers |
