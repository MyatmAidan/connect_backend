| Method | Route | Auth |
|--------|-------|------|
| GET | `/up` | No |
| POST | `/api/v1/telegram/webhook` | No |
| POST | `/api/v1/mobile/auth/register` | No |
| POST | `/api/v1/mobile/auth/login` | No |
| POST | `/api/v1/admin/auth/login` | No |
| GET | `/api/v1/mobile/auth/me` | Bearer |
| POST | `/api/v1/mobile/auth/logout` | Bearer |
| GET | `/api/v1/mobile/profile/me` | Bearer |
| POST | `/api/v1/mobile/profile` | Bearer |
| PUT | `/api/v1/mobile/profile/me` | Bearer |
| DELETE | `/api/v1/mobile/profile/me` | Bearer |
| GET | `/api/v1/mobile/developers` | Bearer |
| GET | `/api/v1/mobile/developers/{developerProfile}` | Bearer |
| GET | `/api/v1/mobile/skills` | Bearer |
| POST | `/api/v1/mobile/connection-requests` | Bearer |
| GET | `/api/v1/mobile/connection-requests/received` | Bearer |
| GET | `/api/v1/mobile/connection-requests/sent` | Bearer |
| POST | `/api/v1/mobile/connection-requests/{connectionRequest}/accept` | Bearer |
| POST | `/api/v1/mobile/connection-requests/{connectionRequest}/reject` | Bearer |
| POST | `/api/v1/mobile/connection-requests/{connectionRequest}/cancel` | Bearer |
| GET | `/api/v1/mobile/connections` | Bearer |
| GET | `/api/v1/mobile/connections/{connection}` | Bearer |
| DELETE | `/api/v1/mobile/connections/{connection}` | Bearer |
| GET | `/api/v1/mobile/conversations` | Bearer |
| GET | `/api/v1/mobile/conversations/{conversation}` | Bearer |
| GET | `/api/v1/mobile/conversations/{conversation}/messages` | Bearer |
| POST | `/api/v1/mobile/conversations/{conversation}/messages` | Bearer |
| POST | `/api/v1/mobile/conversations/{conversation}/read` | Bearer |
| GET | `/api/v1/mobile/meetings` | Bearer |
| POST | `/api/v1/mobile/meetings` | Bearer |
| GET | `/api/v1/mobile/meetings/{meeting}` | Bearer |
| PUT | `/api/v1/mobile/meetings/{meeting}` | Bearer |
| POST | `/api/v1/mobile/meetings/{meeting}/cancel` | Bearer |
| POST | `/api/v1/mobile/meetings/{meeting}/complete` | Bearer |
| POST | `/api/v1/mobile/telegram/link-token` | Bearer |
| POST | `/api/v1/mobile/telegram/test` | Bearer |
| PUT | `/api/v1/mobile/telegram/settings` | Bearer |
| DELETE | `/api/v1/mobile/telegram/disconnect` | Bearer |
| GET | `/api/v1/mobile/notifications` | Bearer |
| POST | `/api/v1/mobile/notifications/{notification}/read` | Bearer |
| POST | `/api/v1/mobile/notifications/read-all` | Bearer |
| POST | `/api/v1/mobile/reports` | Bearer |
| POST | `/api/v1/mobile/users/{user}/block` | Bearer |
| DELETE | `/api/v1/mobile/users/{user}/block` | Bearer |
| GET | `/api/v1/mobile/blocked-users` | Bearer |
| GET | `/api/v1/admin/auth/me` | Bearer + Admin |
| POST | `/api/v1/admin/auth/logout` | Bearer + Admin |
| GET | `/api/v1/admin/dashboard/stats` | Bearer + Admin |
| GET | `/api/v1/admin/dashboard/user-growth` | Bearer + Admin |
| GET | `/api/v1/admin/dashboard/activity` | Bearer + Admin |
| GET | `/api/v1/admin/users` | Bearer + Admin |
| GET | `/api/v1/admin/users/{user}` | Bearer + Admin |
| PUT | `/api/v1/admin/users/{user}` | Bearer + Admin |
| POST | `/api/v1/admin/users/{user}/ban` | Bearer + Admin |
| POST | `/api/v1/admin/users/{user}/unban` | Bearer + Admin |
| DELETE | `/api/v1/admin/users/{user}` | Bearer + Admin |
| GET | `/api/v1/admin/developer-profiles` | Bearer + Admin |
| GET | `/api/v1/admin/developer-profiles/{developerProfile}` | Bearer + Admin |
| PUT | `/api/v1/admin/developer-profiles/{developerProfile}` | Bearer + Admin |
| DELETE | `/api/v1/admin/developer-profiles/{developerProfile}` | Bearer + Admin |
| GET | `/api/v1/admin/skills` | Bearer + Admin |
| POST | `/api/v1/admin/skills` | Bearer + Admin |
| PUT | `/api/v1/admin/skills/{skill}` | Bearer + Admin |
| DELETE | `/api/v1/admin/skills/{skill}` | Bearer + Admin |
| GET | `/api/v1/admin/connection-requests` | Bearer + Admin |
| GET | `/api/v1/admin/connections` | Bearer + Admin |
| GET | `/api/v1/admin/connections/{connection}` | Bearer + Admin |
| DELETE | `/api/v1/admin/connections/{connection}` | Bearer + Admin |
| GET | `/api/v1/admin/meetings` | Bearer + Admin |
| GET | `/api/v1/admin/meetings/{meeting}` | Bearer + Admin |
| DELETE | `/api/v1/admin/meetings/{meeting}` | Bearer + Admin |
| GET | `/api/v1/admin/reports` | Bearer + Admin |
| GET | `/api/v1/admin/reports/{report}` | Bearer + Admin |
| POST | `/api/v1/admin/reports/{report}/review` | Bearer + Admin |
| POST | `/api/v1/admin/reports/{report}/resolve` | Bearer + Admin |
| POST | `/api/v1/admin/reports/{report}/reject` | Bearer + Admin |
| GET | `/api/v1/admin/notifications` | Bearer + Admin |
| POST | `/api/v1/admin/notifications/broadcast` | Bearer + Admin |
| GET | `/api/v1/admin/telegram/stats` | Bearer + Admin |
| GET | `/api/v1/admin/telegram/logs` | Bearer + Admin |
| GET | `/api/v1/admin/logs` | Bearer + Admin |
| GET | `/api/v1/admin/logs/{adminLog}` | Bearer + Admin |
