# React Admin Frontend

A full-featured admin panel built with [React Admin](https://marmelab.com/react-admin/) + [Vite](https://vitejs.dev/) for the **Redis NestJS Dashboard** backend.

## Features

- **Login** – authenticates against `POST /auth/login` and stores the JWT in `localStorage`
- **Users** – read-only paginated DataGrid listing all users
- **Transactions** – full CRUD DataGrid (list, show, create, edit, delete) with type/status/payment filters
- **Dashboard** – live stats cards showing total users, verified users, and Bull queue job counts

## Tech Stack

| | |
|---|---|
| Framework | [React Admin v5](https://marmelab.com/react-admin/) |
| Build tool | [Vite](https://vitejs.dev/) |
| Language | TypeScript |
| UI library | MUI (Material UI, bundled with React Admin) |
| Auth | Custom `authProvider` using JWT (`localStorage`) |
| Data | Custom `dataProvider` talking to the NestJS REST API |

## Development

The frontend dev server (port `5173`) proxies API requests to the NestJS backend (port `3000`), so you need both running:

```bash
# Terminal 1 — start NestJS backend
cd /path/to/laravel12-redis
npm run start:dev

# Terminal 2 — start Vite dev server
cd frontend
npm install
npm run dev
# → http://localhost:5173
```

Log in with any account registered via `POST /auth/register` or `POST /auth/login`.

## Production Build

From the **repo root**:

```bash
# Build admin panel only (outputs to public/admin/)
npm run build:admin

# Build everything (admin + NestJS TypeScript)
npm run build:all
```

The built admin panel is served by NestJS as static files at `http://localhost:3000/admin/`.

## Project Structure

```
frontend/
├── index.html
├── package.json
├── vite.config.ts           # Proxies /auth, /users, /transactions, etc. → localhost:3000
├── tsconfig.json
└── src/
    ├── main.tsx             # React entry point
    ├── App.tsx              # <Admin> with Resources
    ├── providers/
    │   ├── authProvider.ts  # login(), logout(), checkAuth(), getIdentity()
    │   └── dataProvider.ts  # getList(), getOne(), create(), update(), delete()
    └── resources/
        ├── users.tsx        # <UserList> DataGrid
        ├── transactions.tsx # <TransactionList/Show/Create/Edit>
        └── dashboard.tsx    # Stats cards (users + queue)
```

## API Mapping

The custom `dataProvider` maps React Admin calls to the NestJS REST API:

| React Admin call | NestJS endpoint |
|---|---|
| `getList('users', ...)` | `GET /users?page=1&limit=20` |
| `getOne('users', { id })` | `GET /users/:id` |
| `getList('transactions', ...)` | `GET /transactions?page=1&limit=20&...filters` |
| `getOne('transactions', { id })` | `GET /transactions/:id` |
| `create('transactions', { data })` | `POST /transactions` |
| `update('transactions', { id, data })` | `PUT /transactions/:id` |
| `delete('transactions', { id })` | `DELETE /transactions/:id` |

MongoDB `_id` fields are normalised to `id` so React Admin's DataGrid works correctly.
