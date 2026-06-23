# CONNECT API

Laravel 13 backend for the CONNECT developer collaboration platform — mobile app APIs, admin dashboard APIs, real-time chat (Laravel Reverb), Sanctum auth, ULID primary keys, and Service–Repository–Provider architecture.

## Stack

- **Laravel 13** + **Sanctum** (API tokens)
- **Laravel Reverb** (WebSocket / real-time chat)
- **ULID** IDs on all domain tables
- **SQLite** by default (swap to MySQL/PostgreSQL in `.env`)
- **Pagination** on all list endpoints (`?per_page=15`)

## Quick start

```bash
cd /Users/htetmyatthu/Desktop/Hackathon/api

# Install dependencies (requires network)
composer install
composer require laravel/reverb   # if not in lock file yet
php artisan reverb:install

cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

### Default seeded accounts

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `admin@connect.test` | `password` |
| Developer | `dev@connect.test` | `password` |

### Run servers

```bash
# API
php artisan serve

# Queue (broadcasts / notifications)
php artisan queue:work

# Reverb WebSocket
php artisan reverb:start
```

## API base URL

All routes are under **`/api/v1`**:

| Area | Prefix |
|------|--------|
| Mobile | `/api/v1/mobile/...` |
| Admin | `/api/v1/admin/...` |
| Telegram webhook | `/api/v1/telegram/webhook` |

### Auth

- Mobile: `POST /api/v1/mobile/auth/register`, `POST /api/v1/mobile/auth/login`
- Admin: `POST /api/v1/admin/auth/login` (requires `admin` or `super_admin` role)
- Protected routes: `Authorization: Bearer {token}`

### Pagination response shape

```json
{
  "success": true,
  "data": [],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 72 },
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
}
```

## Roles (MVP on `users.role`)

- `user` — mobile app
- `admin` — dashboard
- `super_admin` — full admin access

## Real-time chat (Reverb)

1. Set in `.env`:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=connect
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

2. Subscribe to private channel: `conversation.{conversationUlid}`
3. Listen for event: `message.sent`

Broadcast auth uses `routes/channels.php` — only conversation participants may subscribe.

## Architecture

```
Controller → Service → RepositoryInterface → Eloquent Repository → Model
                ↑
     RepositoryServiceProvider (bindings)
```

### Key directories

```
app/
├── Enums/
├── Events/              # MessageSent (ShouldBroadcast)
├── Http/Controllers/Api/V1/{Mobile,Admin,Telegram}
├── Http/Middleware/       # EnsureUserIsAdmin, SecurityHeaders
├── Http/Requests/Api/V1/
├── Http/Resources/Api/V1/
├── Models/              # HasUlids
├── Providers/RepositoryServiceProvider.php
├── Repositories/{Contracts,Eloquent}
├── Services/
└── Support/ApiResponse.php
```

### Admin CRUD (full)

- Users, developer profiles, skills, reports, notifications broadcast, admin logs

### Mobile (read + actions; no admin CRUD)

- Skills: read-only
- Developer discovery, connections, chat, meetings, telegram, reports, blocks

## Database tables

`users`, `developer_profiles`, `skills`, `developer_skills`, `connection_requests`, `connections`, `conversations`, `messages`, `meetings`, `telegram_link_tokens`, `notification_logs`, `reports`, `blocked_users`, `admin_logs`

## Telegram

- Mobile: link token, settings, disconnect
- Webhook: `POST /api/v1/telegram/webhook` with header `X-Telegram-Bot-Api-Secret-Token`
- Env: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET`

## Admin dashboard routes (frontend)

Suggested pages: `/admin/login`, `/admin/dashboard`, `/admin/users`, `/admin/profiles`, `/admin/skills`, `/admin/connections`, `/admin/meetings`, `/admin/reports`, `/admin/notifications`, `/admin/telegram`, `/admin/logs`, `/admin/settings`

## Mobile screens (Ionic React)

`/login`, `/register`, `/developers`, `/chat/:conversationId`, `/meetings`, `/settings/telegram`, etc. — see project spec.

## License

MIT
# connect_api
# connect_backend
