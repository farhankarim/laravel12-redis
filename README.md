# Redis NestJS Dashboard

A fully fledged **NestJS + MongoDB + Redis** application converted from the original Laravel 12 + Redis project. It preserves all functionality of the original:

- **Bulk user generation** – dispatch thousands of queue jobs that insert users in parallel chunks
- **Email verification** – signed JWT links, queued via Redis for mass sending
- **Real-time dashboards** – live queue statistics and user statistics via WebSockets (Socket.io)
- **Redis pub/sub** – dashboard refresh signals broadcast to all connected clients
- **JWT authentication** – protected API endpoints and dashboard pages
- **Swagger API docs** – interactive at `/api/docs`
- **Horizon (Bull Board)** – visual queue monitoring dashboard at `/horizon`
- **Sanctum (Personal Access Tokens)** – database-backed API tokens as an alternative to short-lived JWTs
- **Passport local strategy** – `passport-local` username/password authentication strategy

---

## Tech Stack

| Layer        | Technology                         |
|--------------|------------------------------------|
| Framework    | [NestJS](https://nestjs.com/) v10  |
| Language     | TypeScript                         |
| Database     | MongoDB 7 (via Mongoose)           |
| Queue        | Redis + [Bull](https://github.com/OptimalBits/bull) |
| Auth         | Passport.js (JWT + Local) + Sanctum PAT |
| Queue UI     | [Bull Board](https://github.com/felixmosh/bull-board) (Horizon equivalent) |
| Email        | Nodemailer                         |
| Real-time    | Socket.io (WebSockets)             |
| API Docs     | Swagger / OpenAPI 3                |

---

## Quick Start (Local)

### Prerequisites

- Node.js ≥ 18
- MongoDB 6+ running on `localhost:27017`
- Redis 6+ running on `localhost:6379`

### Setup

```bash
# 1. Clone the repo and switch to this branch
git clone https://github.com/farhankarim/laravel12-redis.git
cd laravel12-redis
git checkout nodenest        # or copilot/nodenest during PR review

# 2. Install dependencies
npm install

# 3. Configure environment
cp .env.example .env
# Edit .env – set JWT_SECRET and optionally MAIL_* settings

# 4. Start the app (development mode with hot reload)
npm run start:dev
```

The app will be available at **http://localhost:3000**.

---

## Running on GitHub Codespaces

This repository ships with a full **Dev Container** configuration (`.devcontainer/`) that automatically installs and starts MongoDB and Redis when your Codespace launches.

### Steps

1. **Open in Codespaces**
   - On the GitHub repository page, click **Code → Codespaces → Create codespace on nodenest**.

2. **Wait for setup**
   - The `postCreateCommand` script runs automatically:
     - Installs MongoDB 7 and Redis
     - Starts both services
     - Runs `npm install`
     - Copies `.env.example` → `.env`

3. **Edit `.env`** (optional but recommended)
   ```
   JWT_SECRET=your-super-secret-key
   # For emails, fill in MAIL_HOST / MAIL_USER / MAIL_PASS
   # Use Mailtrap (https://mailtrap.io) for free SMTP testing
   ```

4. **Start the application**
   ```bash
   npm run start:dev
   ```

5. **Access the app**
   - Codespaces automatically forwards port **3000**.
   - Click the **Open in Browser** notification, or open the **Ports** tab and click port 3000.
   - The app index is at `/` and the dashboards are at `/dashboard/queue.html` and `/dashboard/users.html`.

---

## API Reference

Interactive Swagger docs are available at **`/api/docs`** once the app is running.

### Authentication (Passport)

| Method | Endpoint            | Description                                         |
|--------|---------------------|-----------------------------------------------------|
| POST   | `/auth/register`    | Register a new user                                 |
| POST   | `/auth/login`       | Login with email/password, returns `access_token`   |
| POST   | `/auth/login/local` | Login via Passport `local` strategy (same payload)  |
| GET    | `/auth/profile`     | Get current user (JWT required)                     |

`POST /auth/login` and `POST /auth/login/local` both accept `{ "email": "...", "password": "..." }` and return the same `{ access_token, user }` response. The `/local` variant goes through the [Passport local strategy](http://www.passportjs.org/packages/passport-local/) middleware pipeline.

### Sanctum – Personal Access Tokens

Long-lived, database-backed tokens as an alternative to short-lived JWTs (inspired by [Laravel Sanctum](https://laravel.com/docs/sanctum)).

| Method | Endpoint            | Auth? | Description                          |
|--------|---------------------|-------|--------------------------------------|
| POST   | `/auth/tokens`      | JWT ✓ | Create a personal access token       |
| GET    | `/auth/tokens`      | JWT ✓ | List tokens for current user         |
| DELETE | `/auth/tokens/:id`  | JWT ✓ | Revoke a token                       |

**Create a token:**
```bash
curl -X POST http://localhost:3000/auth/tokens \
  -H "Authorization: Bearer <jwt>" \
  -H "Content-Type: application/json" \
  -d '{"name": "ci-pipeline", "abilities": ["read"], "expiresAt": "2027-01-01T00:00:00Z"}'
```

Response (plain token shown **once** – store it securely):
```json
{
  "token": "<plain-text-token>",
  "id": "...",
  "name": "ci-pipeline",
  "abilities": ["read"],
  "expiresAt": "2027-01-01T00:00:00.000Z",
  "createdAt": "..."
}
```

### Users

| Method | Endpoint              | Auth? | Description                         |
|--------|-----------------------|-------|-------------------------------------|
| POST   | `/users`              | ✓     | Create a single user                |
| GET    | `/users`              | ✓     | List users (paginated)              |
| GET    | `/users/:id`          | ✓     | Get user by ID                      |
| GET    | `/users/summary`      | ✓     | User statistics (total/verified)    |
| POST   | `/users/generate`     | ✓     | Bulk-generate users via queue       |

**Example – Generate 50 000 users:**
```bash
curl -X POST http://localhost:3000/users/generate \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"total": 50000, "chunkSize": 500}'
```

### Queue

| Method | Endpoint                        | Auth? | Description                           |
|--------|--------------------------------|-------|---------------------------------------|
| GET    | `/queue/stats`                  | ✓     | Bull queue job counts                 |
| POST   | `/queue/email-verifications`    | ✓     | Queue email verification for unverified users |

### Dashboard (API)

| Method | Endpoint                   | Auth? | Description                          |
|--------|---------------------------|-------|--------------------------------------|
| GET    | `/api/dashboard/queue`     | ✓     | Queue statistics (JSON)              |
| GET    | `/api/dashboard/users`     | ✓     | User statistics (JSON)               |
| POST   | `/api/dashboard/refresh`   | ✓     | Trigger pub/sub refresh              |
| GET    | `/email/verify?token=…`    | ✗     | Verify email via signed link         |

---

## WebSocket / Real-time Dashboard

Connect to the Socket.io namespace **`/dashboard`**:

```js
const socket = io('http://localhost:3000/dashboard');

// Request current stats
socket.emit('get-queue-summary');   // → receives 'queue-summary'
socket.emit('get-users-summary');   // → receives 'users-summary'

// Trigger a full Redis pub/sub refresh
socket.emit('refresh-dashboard');   // → broadcasts to all clients
```

Events emitted by server:

| Event            | Payload                                  |
|-----------------|------------------------------------------|
| `queue-summary`  | `{ queues: [...], totals: {...}, cachedAt }` |
| `users-summary`  | `{ total, verified, unverified, latestUser, cachedAt }` |

---

## Horizon – Bull Board Queue Dashboard

[Bull Board](https://github.com/felixmosh/bull-board) provides a real-time visual queue dashboard equivalent to [Laravel Horizon](https://laravel.com/docs/horizon).

**URL:** `http://localhost:3000/horizon`

All Bull queues defined in `QUEUE_NAMES` are automatically registered and displayed with job counts, throughput, and individual job details.

> **Note:** The `/horizon` route is currently open for local development. In production, protect it with middleware (IP allowlist, basic auth, or session guard).

---

## Sanctum – Personal Access Tokens

Inspired by [Laravel Sanctum](https://laravel.com/docs/sanctum), the `SanctumModule` issues long-lived, database-backed personal access tokens as a complement to the short-lived JWT flow.

**How it works:**
1. Obtain a JWT via `POST /auth/login`.
2. Exchange it for a PAT via `POST /auth/tokens` (include `name`, optional `abilities`, optional `expiresAt`).
3. The plain-text token is returned **once** – store it in a secret manager or `.env`.
4. Use the PAT as a `Bearer` token to call any protected endpoint.
5. Revoke it at any time via `DELETE /auth/tokens/:id`.

Tokens are stored hashed (bcrypt) in MongoDB; the hash is never exposed.

---

## Passport Strategies

The application uses [NestJS Passport](https://docs.nestjs.com/security/authentication) with two strategies:

| Strategy   | Guard             | Endpoint             | Description                              |
|------------|-------------------|----------------------|------------------------------------------|
| `jwt`      | `JwtAuthGuard`    | All protected routes | Validates `Authorization: Bearer <JWT>` |
| `local`    | `LocalAuthGuard`  | `POST /auth/login/local` | Validates email + password via `passport-local` |

Both strategies are registered in `AuthModule`. Additional OAuth strategies (Google, GitHub, etc.) can be added by installing the corresponding `passport-*` package and creating a new `Strategy` + `Guard` pair.

---

## Queue Architecture

```
POST /users/generate
        │
        ▼
  user-imports queue (Redis/Bull)
        │
        ├── InsertUsersChunkJob  ×N  ──► MongoDB bulk insert
        │
        └── (repeat for each chunk)

POST /queue/email-verifications
        │
        ▼
  email-verifications queue (Redis/Bull)
        │
        └── SendEmailVerificationChunkJob  ×N  ──► Nodemailer SMTP
```

---

## Project Structure

```
src/
├── main.ts                  # Bootstrap (Bull Board mounted here)
├── app.module.ts            # Root module
├── auth/                    # JWT + Local Passport authentication
│   ├── auth.controller.ts
│   ├── auth.service.ts
│   ├── auth.module.ts
│   ├── jwt.strategy.ts
│   ├── jwt-auth.guard.ts
│   ├── local.strategy.ts    # Passport local strategy
│   ├── local-auth.guard.ts  # Guard for local strategy
│   └── dto/login.dto.ts
├── sanctum/                 # Personal Access Tokens (Sanctum)
│   ├── sanctum.module.ts
│   ├── sanctum.service.ts
│   ├── sanctum.controller.ts
│   ├── schemas/
│   │   └── personal-access-token.schema.ts
│   └── dto/
│       └── create-token.dto.ts
├── users/                   # User management
│   ├── users.controller.ts
│   ├── users.service.ts
│   ├── users.module.ts
│   ├── schemas/user.schema.ts
│   └── dto/
│       ├── create-user.dto.ts
│       └── generate-users.dto.ts
├── queue/                   # Bull queues
│   ├── queue.controller.ts
│   ├── queue.module.ts
│   ├── dto/queue-email-verifications.dto.ts
│   └── processors/
│       ├── insert-users.processor.ts
│       └── send-email-verification.processor.ts
├── dashboard/               # Stats + WebSocket
│   ├── dashboard.controller.ts
│   ├── dashboard.service.ts
│   ├── dashboard.gateway.ts
│   └── dashboard.module.ts
└── mail/                    # Nodemailer
    ├── mail.service.ts
    └── mail.module.ts

public/
├── index.html               # Landing page
└── dashboard/
    ├── login.html           # Login page
    ├── queue.html           # Real-time queue dashboard
    └── users.html           # Real-time users dashboard
```

---

## Environment Variables

| Variable          | Default                                    | Description                         |
|-------------------|--------------------------------------------|-------------------------------------|
| `APP_PORT`        | `3000`                                     | HTTP port                           |
| `NODE_ENV`        | `development`                              | Environment                         |
| `JWT_SECRET`      | `changeme`                                 | **Change this in production!**      |
| `JWT_EXPIRES_IN`  | `7d`                                       | Token lifetime                      |
| `MONGODB_URI`     | `mongodb://localhost:27017/laravel12_redis`| MongoDB connection string           |
| `REDIS_HOST`      | `127.0.0.1`                                | Redis host                          |
| `REDIS_PORT`      | `6379`                                     | Redis port                          |
| `REDIS_PASSWORD`  | _(empty)_                                  | Redis password (leave empty locally)|
| `MAIL_HOST`       | `smtp.mailtrap.io`                         | SMTP host                           |
| `MAIL_PORT`       | `587`                                      | SMTP port                           |
| `MAIL_USER`       | _(empty)_                                  | SMTP username                       |
| `MAIL_PASS`       | _(empty)_                                  | SMTP password                       |
| `MAIL_FROM`       | `noreply@example.com`                      | From address                        |
| `APP_URL`         | `http://localhost:3000`                    | Base URL (for email verification links) |
| `QUEUE_NAMES`     | `default,user-imports,email-verifications` | Comma-separated queue names to monitor |

---

## Available Scripts

| Command              | Description                          |
|---------------------|--------------------------------------|
| `npm run start:dev`  | Start in watch mode (hot reload)     |
| `npm run build`      | Compile TypeScript to `dist/`        |
| `npm run start:prod` | Run compiled production build        |
| `npm run test`       | Run unit tests (Jest)                |
| `npm run lint`       | Run ESLint                           |

---

## License

MIT
