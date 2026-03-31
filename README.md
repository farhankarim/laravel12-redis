# Redis NestJS Dashboard

A fully fledged **NestJS + MongoDB + Redis** application converted from the original Laravel 12 + Redis project. It preserves all functionality of the original and adds new features:

- **Bulk user generation** – dispatch thousands of queue jobs that insert users in parallel chunks
- **Email verification** – signed JWT links, queued via Redis for mass sending
- **Real-time dashboards** – live queue statistics and user statistics via WebSockets (Socket.io)
- **Redis pub/sub** – dashboard refresh signals broadcast to all connected clients
- **JWT authentication** – protected API endpoints and dashboard pages
- **Swagger API docs** – interactive at `/api/docs`
- **Transactions CRUD** – full create/read/update/delete for financial transactions
- **Repository pattern** – `IBaseRepository<T>` abstraction with concrete Mongoose implementations for all modules
- **React Admin panel** – full-featured admin UI served at `/admin/` (Users + Transactions DataGrids, Dashboard stats)
- **Module scaffolding CLI** – `npm run generate:module <Name>` scaffolds a complete repository-pattern module in seconds

---

## Tech Stack

| Layer        | Technology                         |
|--------------|------------------------------------|
| Framework    | [NestJS](https://nestjs.com/) v10  |
| Language     | TypeScript                         |
| Database     | MongoDB 7 (via Mongoose)           |
| Queue        | Redis + [Bull](https://github.com/OptimalBits/bull) |
| Auth         | Passport.js + JWT                  |
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
# 1. Clone the repo
git clone https://github.com/farhankarim/laravel12-redis.git
cd laravel12-redis

# 2. Install dependencies
npm install

# 3. Configure environment
cp .env.example .env
# Edit .env – set JWT_SECRET and optionally MAIL_* settings

# 4. Start the app (development mode with hot reload)
npm run start:dev
```

The app will be available at **http://localhost:3000**.

| URL | Description |
|-----|-------------|
| `http://localhost:3000/` | Landing page |
| `http://localhost:3000/admin/` | React Admin panel |
| `http://localhost:3000/dashboard/queue.html` | Real-time queue dashboard |
| `http://localhost:3000/dashboard/users.html` | Real-time users dashboard |
| `http://localhost:3000/api/docs` | Swagger API docs |

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
   - Landing page: `/`
   - React Admin panel: `/admin/`
   - Real-time dashboards: `/dashboard/queue.html` and `/dashboard/users.html`.
   - Swagger docs: `/api/docs`

---

## API Reference

Interactive Swagger docs are available at **`/api/docs`** once the app is running.

### Authentication

| Method | Endpoint         | Description                  |
|--------|-----------------|------------------------------|
| POST   | `/auth/register` | Register a new user          |
| POST   | `/auth/login`    | Login, returns `access_token`|
| GET    | `/auth/profile`  | Get current user (JWT)       |

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

### Transactions

| Method | Endpoint                | Auth? | Description                                      |
|--------|-------------------------|-------|--------------------------------------------------|
| POST   | `/transactions`         | ✓     | Create a transaction                             |
| GET    | `/transactions`         | ✓     | List transactions (paginated, filterable)        |
| GET    | `/transactions/:id`     | ✓     | Get transaction by ID                            |
| PUT    | `/transactions/:id`     | ✓     | Update a transaction                             |
| DELETE | `/transactions/:id`     | ✓     | Delete a transaction                             |

**Transaction filters** (query params on `GET /transactions`): `page`, `limit`, `participantId`, `type` (`loan taken` | `donation` | `loan returned`), `paymentStatus`, `status`.

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
├── main.ts                  # Bootstrap (CORS + static assets + Swagger)
├── app.module.ts            # Root module
├── common/
│   └── interfaces/
│       └── base-repository.interface.ts  # IBaseRepository<T> contract
├── auth/                    # JWT authentication
│   ├── auth.controller.ts
│   ├── auth.service.ts
│   ├── auth.module.ts
│   ├── jwt.strategy.ts
│   ├── jwt-auth.guard.ts
│   └── dto/login.dto.ts
├── users/                   # User management
│   ├── users.controller.ts
│   ├── users.service.ts
│   ├── users.module.ts
│   ├── schemas/user.schema.ts
│   ├── repositories/
│   │   ├── user.repository.interface.ts
│   │   └── user.repository.ts
│   └── dto/
│       ├── create-user.dto.ts
│       └── generate-users.dto.ts
├── transactions/            # Financial transactions CRUD
│   ├── transactions.controller.ts
│   ├── transactions.service.ts
│   ├── transactions.module.ts
│   ├── schemas/transaction.schema.ts
│   ├── repositories/
│   │   ├── transaction.repository.interface.ts
│   │   └── transaction.repository.ts
│   └── dto/
│       ├── create-transaction.dto.ts
│       └── update-transaction.dto.ts
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
├── admin/                   # Built React Admin panel (output of `npm run build:admin`)
│   ├── index.html
│   └── assets/
└── dashboard/
    ├── login.html           # Login page
    ├── queue.html           # Real-time queue dashboard
    └── users.html           # Real-time users dashboard

frontend/                    # React Admin panel source (Vite + React Admin)
├── src/
│   ├── App.tsx              # Admin with Users + Transactions resources
│   ├── providers/
│   │   ├── authProvider.ts  # JWT auth provider
│   │   └── dataProvider.ts  # Custom data provider for NestJS API
│   └── resources/
│       ├── users.tsx        # Users DataGrid (list)
│       ├── transactions.tsx # Transactions DataGrid (list/show/create/edit)
│       └── dashboard.tsx    # Dashboard stats screen
├── package.json
└── vite.config.ts

scripts/
└── generate-module.ts       # CLI: npm run generate:module <ModuleName>
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

## Repository Pattern

All database modules follow an **interface-first repository pattern**:

```
IBaseRepository<T>          (src/common/interfaces/base-repository.interface.ts)
        │
        ├── IUserRepository          (src/users/repositories/user.repository.interface.ts)
        │       └── UserRepository   (concrete Mongoose implementation)
        │
        └── ITransactionRepository   (src/transactions/repositories/transaction.repository.interface.ts)
                └── TransactionRepository   (concrete Mongoose implementation)
```

Services receive the repository via dependency injection using a token constant (e.g. `USER_REPOSITORY`), so the concrete implementation can be swapped without changing service code.

---

## React Admin Panel

A full-featured admin panel built with [React Admin](https://marmelab.com/react-admin/) + Vite is available at **`/admin/`**.

**Features:**
- Login with the same JWT credentials as the API
- **Users** – paginated DataGrid with search/filter
- **Transactions** – full CRUD DataGrid (list, show, create, edit, delete)
- **Dashboard** – live stats cards (total users, verified, pending queue jobs)

**Development workflow:**
```bash
# Start NestJS backend
npm run start:dev

# In a separate terminal, start the Vite dev server (proxies API to localhost:3000)
cd frontend
npm install
npm run dev
# → http://localhost:5173
```

**Production build:**
```bash
npm run build:admin   # outputs to public/admin/
npm run build         # compile NestJS
# or
npm run build:all     # both in sequence
```

---



| Command                               | Description                                         |
|--------------------------------------|-----------------------------------------------------|
| `npm run start:dev`                   | Start in watch mode (hot reload)                    |
| `npm run build`                       | Compile TypeScript to `dist/`                       |
| `npm run build:admin`                 | Build the React Admin panel to `public/admin/`      |
| `npm run build:all`                   | Build admin panel then compile NestJS               |
| `npm run start:prod`                  | Run compiled production build                       |
| `npm run test`                        | Run unit tests (Jest)                               |
| `npm run lint`                        | Run ESLint                                          |
| `npm run generate:module <Name>`      | Scaffold a new repository-pattern module            |

### Scaffolding a new module

```bash
npm run generate:module Payment
```

Generates all boilerplate under `src/payments/`:
- `schemas/payment.schema.ts`
- `repositories/payment.repository.interface.ts` + `payment.repository.ts`
- `dto/create-payment.dto.ts` + `update-payment.dto.ts`
- `payments.service.ts`
- `payments.controller.ts`
- `payments.module.ts`

Then register it in `src/app.module.ts`:
```typescript
import { PaymentsModule } from './payments/payments.module';

@Module({ imports: [..., PaymentsModule] })
export class AppModule {}
```

---

## License

MIT
