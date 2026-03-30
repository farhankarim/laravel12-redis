# What Changed: Laravel 12 → NestJS Conversion

This document walks through every change that was made when converting this project from a **Laravel 12 + Livewire + MySQL** application to a **NestJS + Mongoose + Redis/Bull** application. It is written for a Laravel developer who is familiar with the original codebase.

---

## 1. Language & Runtime

| Before (Laravel 12) | After (NestJS) |
|---|---|
| PHP 8.3 | TypeScript 5 / Node.js 18+ |
| `composer.json` / `composer install` | `package.json` / `npm install` |
| `php artisan serve` | `npm run start:dev` |
| `php artisan` CLI | `npx nest` CLI |

The entire application logic was rewritten in **TypeScript** instead of PHP. This means:

- Class-based decorators (`@Controller`, `@Injectable`, etc.) replace PHP attributes and annotations.
- Strict typing is enforced by the TypeScript compiler (`tsconfig.json`) rather than PHP's type system.
- The compiled output lives in `dist/` (TypeScript → JavaScript) and is run with `node dist/main.js`.

---

## 2. Framework Bootstrapping

### Laravel (`public/index.php` → `bootstrap/app.php`)
```php
// bootstrap/app.php
$app = new Illuminate\Foundation\Application(dirname(__DIR__));
$app->singleton(Illuminate\Contracts\Http\Kernel::class, App\Http\Kernel::class);
```

### NestJS (`src/main.ts`)
```typescript
// src/main.ts
const app = await NestFactory.create<NestExpressApplication>(AppModule);
await app.listen(3000);
```

**Key differences:**
- NestJS has no separate `Kernel` class; the whole app is bootstrapped in `main.ts`.
- Static HTML files are served with `app.useStaticAssets()` (using Express under the hood) — replacing Laravel's public disk / Blade layouts.
- A global `ValidationPipe` is registered here (equivalent to Laravel's global middleware in `Kernel.php`).
- Swagger (`/api/docs`) is configured here — there is no equivalent in a vanilla Laravel project.

---

## 3. Application Structure

### Laravel
```
app/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Services/
├── Livewire/
├── Mail/
└── Jobs/
routes/
  web.php
  api.php
resources/views/
```

### NestJS
```
src/
├── main.ts              # bootstrap
├── app.module.ts        # root module (= bootstrap/app.php)
├── auth/                # feature module
├── users/               # feature module
├── queue/               # feature module
├── dashboard/           # feature module
└── mail/                # feature module
public/
└── dashboard/           # static HTML (replaces Blade views)
```

**Key difference:** NestJS uses **feature modules** (`auth/`, `users/`, etc.). Each module bundles its own controller, service, and any providers together. Laravel does the same logically (by convention), but NestJS enforces it structurally with the `@Module` decorator.

---

## 4. Database: MySQL → MongoDB

### Laravel (Eloquent + MySQL)
```php
// app/Models/User.php
class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password'];
    protected $casts    = ['email_verified_at' => 'datetime'];
}

// Migration
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamps();
});
```

### NestJS (Mongoose + MongoDB)
```typescript
// src/users/schemas/user.schema.ts
@Schema({ timestamps: true })
export class User {
    @Prop({ required: true }) name: string;
    @Prop({ required: true, unique: true, lowercase: true }) email: string;
    @Prop({ required: true, select: false }) password: string;
    @Prop({ type: Date, default: null }) emailVerifiedAt: Date | null;
}
export const UserSchema = SchemaFactory.createForClass(User);
```

**What changed:**
- No SQL migrations — MongoDB is schema-less. The `@Schema` / `@Prop` decorators define the shape.
- `$table->timestamps()` becomes `{ timestamps: true }` on the `@Schema` decorator — Mongoose adds `createdAt` and `updatedAt` automatically.
- `$table->id()` auto-increment integer primary key → MongoDB's auto-generated `_id` (ObjectId).
- `select: false` on the `password` prop mirrors `$hidden = ['password']` in Eloquent.
- `findOne`, `findById`, `insertMany` are Mongoose equivalents of Eloquent's `where`, `find`, `insert`.

---

## 5. ORM / Database Queries

| Laravel (Eloquent) | NestJS (Mongoose) |
|---|---|
| `User::create($data)` | `new this.userModel(data).save()` |
| `User::where('email', $e)->first()` | `this.userModel.findOne({ email: e }).exec()` |
| `User::find($id)` | `this.userModel.findById(id).exec()` |
| `User::where(...)->update([...])` | `this.userModel.findByIdAndUpdate(id, {...}).exec()` |
| `User::count()` | `this.userModel.countDocuments().exec()` |
| `User::insert($rows)` | `this.userModel.insertMany(rows, { ordered: false })` |
| `User::paginate($perPage)` | `this.userModel.find().skip(skip).limit(limit).exec()` |

---

## 6. Dependency Injection (DI)

### Laravel (constructor injection or `app()->make()`)
```php
class AuthController extends Controller
{
    public function __construct(
        private UsersService $users,
        private MailService  $mail,
    ) {}
}
```

### NestJS (constructor injection via `@Injectable`)
```typescript
@Controller('auth')
export class AuthController {
    constructor(
        private readonly authService: AuthService,
        private readonly usersService: UsersService,
        private readonly mailService: MailService,
    ) {}
}
```

Both frameworks use constructor injection. NestJS's DI container resolves services automatically when a class is decorated with `@Injectable()` and registered as a `provider` inside a `@Module`. This is the direct equivalent of Laravel's service container / `AppServiceProvider` bindings.

---

## 7. Routing

### Laravel (`routes/api.php`)
```php
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
    Route::get('profile',   [AuthController::class, 'profile'])->middleware('auth:api');
});
```

### NestJS (Controller decorators)
```typescript
@Controller('auth')
export class AuthController {
    @Post('register')  async register() { ... }
    @Post('login')     async login()    { ... }
    @Get('profile')
    @UseGuards(JwtAuthGuard)
    async profile() { ... }
}
```

There is **no separate routes file**. Each controller class *is* the route definition. The `@Controller('auth')` prefix and `@Get`, `@Post` method decorators together build the URL (`POST /auth/register`, etc.). This is roughly equivalent to Laravel's resourceful controllers, but explicit for each route.

---

## 8. Request Validation

### Laravel (Form Requests)
```php
// app/Http/Requests/CreateUserRequest.php
class CreateUserRequest extends FormRequest
{
    public function rules(): array {
        return [
            'name'     => 'required|string',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8',
        ];
    }
}
```

### NestJS (DTO + class-validator)
```typescript
// src/users/dto/create-user.dto.ts
export class CreateUserDto {
    @IsString() @IsNotEmpty()  name: string;
    @IsEmail()                 email: string;
    @IsString() @MinLength(8)  password: string;
}
```

- Laravel's `FormRequest` classes → NestJS **DTO** (Data Transfer Object) classes.
- Rules are expressed as PHP arrays in Laravel; NestJS uses **decorators** from the `class-validator` library.
- The global `ValidationPipe` (configured in `main.ts`) automatically validates every incoming request body against the DTO — equivalent to Laravel automatically running `FormRequest` validation before the controller method fires.
- `whitelist: true` strips any extra properties not declared on the DTO (like `$fillable` in Eloquent).
- `forbidNonWhitelisted: true` throws a 422-equivalent error if unexpected fields are sent.

---

## 9. Authentication

### Laravel (Sanctum / Passport)
```php
// config/auth.php
'guards' => ['api' => ['driver' => 'sanctum']],

// Middleware
Route::middleware('auth:sanctum')->get('/profile', ...);

// Token generation
$token = $user->createToken('api-token')->plainTextToken;
```

### NestJS (Passport + JWT)
```typescript
// src/auth/jwt.strategy.ts  — validates incoming JWT
@Injectable()
export class JwtStrategy extends PassportStrategy(Strategy) {
    constructor(config: ConfigService) {
        super({
            jwtFromRequest: ExtractJwt.fromAuthHeaderAsBearerToken(),
            secretOrKey: config.get('JWT_SECRET'),
        });
    }
    async validate(payload) {
        return { userId: payload.sub, email: payload.email };
    }
}

// src/auth/jwt-auth.guard.ts  — protects routes
@Injectable()
export class JwtAuthGuard extends AuthGuard('jwt') {}

// Usage on a route
@UseGuards(JwtAuthGuard)
@Get('profile')
async profile(@Request() req) { ... }
```

**What changed:**
- Laravel Sanctum/Passport → **Passport.js + `@nestjs/passport`** (same Passport library name but Node version).
- `auth:sanctum` middleware → `@UseGuards(JwtAuthGuard)` decorator.
- `$user->createToken(...)` → `jwtService.sign({ sub, email })`.
- Token is passed in the `Authorization: Bearer <token>` header (same as Laravel API tokens).
- Password hashing uses `bcrypt` in both frameworks.

---

## 10. Queues

### Laravel (Redis Queue + Jobs)
```php
// Dispatch a job
dispatch(new InsertUsersChunkJob($data))->onQueue('user-imports');

// app/Jobs/InsertUsersChunkJob.php
class InsertUsersChunkJob implements ShouldQueue
{
    public function handle(UsersService $users): void { ... }
}

// Run the worker
php artisan queue:work redis --queue=user-imports
```

### NestJS (Bull + Redis)
```typescript
// Dispatch jobs
await this.userImportsQueue.addBulk(jobs);

// src/queue/processors/insert-users.processor.ts
@Processor('user-imports')
export class InsertUsersProcessor {
    @Process('insert-users-chunk')
    async handle(job: Job<InsertUsersChunkJobData>): Promise<void> { ... }
}
```

**What changed:**
- Laravel's built-in queue system → **Bull** (`@nestjs/bull` package) backed by Redis.
- `dispatch(new Job(...))` → `queue.add('job-name', data)` or `queue.addBulk(jobs)`.
- `class Job implements ShouldQueue { public function handle() }` → `@Process('job-name') async handle(job: Job<Data>)` inside a `@Processor('queue-name')` class.
- `php artisan queue:work` is **not needed** — Bull processors run **inside the same Node process** as the web server. There is no separate worker command to start.
- Job data is typed via TypeScript interfaces (`InsertUsersChunkJobData`) instead of PHP constructor properties.
- Two queues are registered: `user-imports` and `email-verifications` (same names as the original).

---

## 11. Real-time Dashboard: Livewire → Socket.io + Static HTML

This is the **biggest architectural change** in the project.

### Laravel (Livewire + Server-Side Rendering)
```php
// app/Livewire/QueueSummaryDashboard.php
class QueueSummaryDashboard extends Component
{
    public array $summary = [];
    public function mount(RedisDashboardSummaryService $s) { $this->summary = $s->getQueueSummary(); }
    public function render() { return view('livewire.queue-summary-dashboard'); }
}
```
```blade
{{-- resources/views/livewire/queue-summary-dashboard.blade.php --}}
<section wire:poll.10s="loadSummary">
    <p>Pending: {{ $summary['totals']['pending'] }}</p>
    <button wire:click="refreshSummary">Refresh</button>
</section>
```

### NestJS (WebSocket Gateway + Static HTML)
```typescript
// src/dashboard/dashboard.gateway.ts
@WebSocketGateway({ cors: { origin: '*' }, namespace: '/dashboard' })
export class DashboardGateway {
    @WebSocketServer() server: Server;

    @SubscribeMessage('get-queue-summary')
    async onGetQueueSummary(client: Socket) {
        const summary = await this.dashboardService.getQueueSummary();
        client.emit('queue-summary', summary);
    }
}
```
```html
<!-- public/dashboard/queue.html -->
<script>
    const socket = io('/dashboard');
    socket.emit('get-queue-summary');
    socket.on('queue-summary', (data) => {
        document.getElementById('pending').textContent = data.totals.pending;
    });
</script>
```

**What changed:**
- Livewire server-side components → NestJS **WebSocket Gateway** (Socket.io).
- Blade templates → **plain static HTML files** served from the `public/` folder.
- `wire:poll` → the browser-side Socket.io client makes explicit requests to the gateway.
- `wire:click="refreshSummary"` → `socket.emit('refresh-dashboard')`.
- The server still uses Redis pub/sub (`REFRESH_CHANNEL`) to broadcast updates to all connected clients when a refresh is triggered — preserving the original pub/sub architecture.
- The Redis pub/sub subscriber now lives in `DashboardService.onModuleInit()` instead of a separate Artisan `dashboard:redis-listen` command.

---

## 12. Mail

### Laravel (Mailable + SMTP)
```php
// app/Mail/VerificationEmail.php
class VerificationEmail extends Mailable implements ShouldQueue
{
    public function envelope() { return new Envelope(subject: 'Verify your email'); }
    public function content() { return new Content(view: 'emails.verification'); }
}

// Dispatch
Mail::to($user->email)->queue(new VerificationEmail($user));
```

### NestJS (Nodemailer)
```typescript
// src/mail/mail.service.ts
await this.transporter.sendMail({
    from: `"${fromName}" <${fromAddress}>`,
    to: email,
    subject: 'Verify your email address',
    html: `<a href="${verificationUrl}">Verify Email Address</a>`,
});
```

**What changed:**
- Laravel `Mailable` classes → **Nodemailer** transporter directly in `MailService`.
- Blade email templates → inline HTML strings inside `MailService`.
- `Mail::to($user)->queue(...)` (automatic queuing) → the mail service is called directly from the queue processor (`SendEmailVerificationProcessor`), so queuing is handled by Bull.
- Verification links are signed JWTs (`type: 'email-verification'`) rather than Laravel's `URL::signedRoute` / `email.verify` flow. This keeps the implementation self-contained without needing database-stored tokens.

---

## 13. Configuration / Environment Variables

### Laravel (`.env` + `config/` files)
```php
// config/database.php
'mysql' => ['host' => env('DB_HOST', '127.0.0.1'), ...],

// Usage
config('database.connections.mysql.host')
env('APP_KEY')
```

### NestJS (`ConfigModule` + `ConfigService`)
```typescript
// app.module.ts
ConfigModule.forRoot({ isGlobal: true }),

// Usage in any service
constructor(private config: ConfigService) {}
const secret = this.config.get<string>('JWT_SECRET', 'changeme');
```

**What changed:**
- Laravel's `config/` directory and `env()` helper → NestJS `@nestjs/config` module.
- `ConfigModule.forRoot({ isGlobal: true })` makes the config available everywhere without re-importing — equivalent to `config()` being globally available in Laravel.
- Environment variable names are the same or very similar (see `README.md` for the full list).

---

## 14. Middleware

### Laravel (`app/Http/Middleware/Authenticate.php`)
```php
Route::middleware('auth:sanctum')->group(function () { ... });
```

### NestJS (Guards)
```typescript
@UseGuards(JwtAuthGuard)  // on a single route
// or
@Controller('queue')
@UseGuards(JwtAuthGuard)  // on the whole controller
export class QueueController { ... }
```

NestJS does not use "middleware" for authentication — it uses **Guards**. A Guard is a class decorated with `@Injectable()` that implements `CanActivate`. It runs before the route handler, just like Laravel middleware. The `JwtAuthGuard` extends `AuthGuard('jwt')` from Passport and is the NestJS equivalent of `auth:sanctum`.

---

## 15. Artisan Commands → Lifecycle Hooks

### Laravel
```php
// Long-running subscriber command
Artisan::command('dashboard:redis-listen', function () {
    Redis::subscribe([...], function ($msg) { ... });
});
```

### NestJS (`OnModuleInit`)
```typescript
@Injectable()
export class DashboardService implements OnModuleInit, OnModuleDestroy {
    onModuleInit() {
        this.redisSubscriber.subscribe(this.REFRESH_CHANNEL, ...);
        this.redisSubscriber.on('message', async (channel, msg) => { ... });
    }
    onModuleDestroy() {
        this.redisClient?.disconnect();
        this.redisSubscriber?.disconnect();
    }
}
```

The `dashboard:redis-listen` Artisan command (which had to be run in a separate terminal) is replaced by NestJS's `OnModuleInit` lifecycle hook. The Redis pub/sub subscriber starts automatically when the module initialises and shuts down cleanly when the process exits.

---

## 16. Swagger / API Documentation

- **Laravel:** No built-in Swagger. Would require `l5-swagger` or similar.
- **NestJS:** `@nestjs/swagger` is built in. `@ApiTags`, `@ApiOperation`, `@ApiResponse`, `@ApiBearerAuth` decorators on controllers/DTOs auto-generate the OpenAPI spec. Available at `/api/docs`.

---

## 17. File-by-File Mapping

| Laravel File / Concept | NestJS Equivalent |
|---|---|
| `bootstrap/app.php` | `src/main.ts` |
| `app/Http/Kernel.php` | `src/main.ts` (global pipes & middleware) |
| `routes/api.php` | Controller `@Get`/`@Post` decorators |
| `app/Models/User.php` | `src/users/schemas/user.schema.ts` |
| `database/migrations/` | Mongoose `@Schema` / `@Prop` decorators |
| `app/Http/Controllers/AuthController.php` | `src/auth/auth.controller.ts` |
| `app/Http/Controllers/UserController.php` | `src/users/users.controller.ts` |
| `app/Http/Requests/CreateUserRequest.php` | `src/users/dto/create-user.dto.ts` |
| `app/Services/AuthService.php` | `src/auth/auth.service.ts` |
| `app/Services/UsersService.php` | `src/users/users.service.ts` |
| `app/Services/RedisDashboardSummaryService.php` | `src/dashboard/dashboard.service.ts` |
| `app/Jobs/InsertUsersChunkJob.php` | `src/queue/processors/insert-users.processor.ts` |
| `app/Jobs/SendEmailVerificationChunkJob.php` | `src/queue/processors/send-email-verification.processor.ts` |
| `app/Mail/VerificationEmail.php` | `src/mail/mail.service.ts` |
| `app/Livewire/QueueSummaryDashboard.php` | `src/dashboard/dashboard.gateway.ts` + `public/dashboard/queue.html` |
| `app/Livewire/UsersSummaryDashboard.php` | `src/dashboard/dashboard.gateway.ts` + `public/dashboard/users.html` |
| `resources/views/livewire/*.blade.php` | `public/dashboard/*.html` (static HTML) |
| `config/auth.php` | `src/auth/auth.module.ts` (JWT config) |
| `config/queue.php` | `src/queue/queue.module.ts` (Bull config) |
| `.env` | `.env` (same file, same variable names) |
| `php artisan queue:work` | No separate command — Bull workers run in-process |
| `php artisan dashboard:redis-listen` | `DashboardService.onModuleInit()` (runs automatically) |
