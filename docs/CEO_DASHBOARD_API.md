# Staff Portal — Integration API

A read-only JSON API for external dashboards (e.g. the CEO Dashboard). Built on
Laravel Sanctum bearer tokens.

## 1. Base URL

```
https://staff.bespokegardenroomsballycastle.co.uk/api
```

Every path below is relative to this base.

Quick reachability check (no auth required):

```
GET https://staff.bespokegardenroomsballycastle.co.uk/api/health
→  { "status": "ok", "time": "2026-06-08T12:00:00+00:00" }
```

## 2. Authentication

Bearer token in the `Authorization` header. Always send `Accept: application/json`.

```
Authorization: Bearer 12|aBcDeF...your-token...
Accept: application/json
```

### Minting a token

On the server (or via Railway shell):

```bash
php artisan api:token "CEO Dashboard"
# or pick the owning user explicitly:
php artisan api:token "CEO Dashboard" --email=admin@bcf.com
```

The plaintext token is printed **once** — copy it immediately and store it as a
secret in the CEO Dashboard's config. Tokens don't expire unless revoked.

### Revoking

Delete the row in `personal_access_tokens`, or via tinker:
`$user->tokens()->where('name', 'CEO Dashboard')->delete();`

A `401 Unauthenticated` response means the token is missing, wrong, or revoked.

## 3. Endpoints

All endpoints are `GET`. List endpoints are paginated (Laravel format:
`{ data: [...], links: {...}, meta: {...} }`); summary endpoints return a flat object.

| Method | Path | Description |
|--------|------|-------------|
| GET | `/health` | Liveness check (no auth) |
| GET | `/me` | Identity + roles of the token holder |
| GET | `/dashboard` | **Consolidated snapshot — best single call for a dashboard** |
| GET | `/staff` | Paginated staff list |
| GET | `/staff/summary` | Headcount roll-up |
| GET | `/attendance` | Paginated time entries |
| GET | `/attendance/summary` | Live clocked-in count + hours today/this week |
| GET | `/payroll/runs` | Paginated payroll runs |
| GET | `/payroll/summary` | Payroll totals (gross/net/hours) |
| GET | `/jobs` | Paginated jobs (work orders) |
| GET | `/jobs/summary` | Jobs + projects grouped by status/business |

### Common query params

- **Pagination**: `per_page` (default 50, max 200), `page`.
- **Date filters** (`/attendance`, `/payroll/runs`, `/jobs`): `from=YYYY-MM-DD`, `to=YYYY-MM-DD`.
- **Status filters**: `status=` — attendance: `pending|approved|rejected`; jobs: `scheduled|in_progress|completed`; payroll: `draft|approved`.
- **Single staff member**: `user_id=<uuid>` on `/attendance` and `/payroll/runs`; `role=` and `search=` on `/staff`.

### Sample: `GET /api/dashboard`

```json
{
  "generated_at": "2026-06-08T12:00:00+00:00",
  "staff":      { "total": 42, "active": 38, "inactive": 4 },
  "attendance": { "clocked_in_now": 12, "hours_today": 64.5, "hours_this_week": 980.25, "pending_approvals": 3 },
  "jobs":       { "today": 7, "in_progress": 4, "scheduled": 11 },
  "projects":   { "active": 9, "planning": 2, "on_hold": 1, "completed": 24 },
  "approvals":  { "pending_leave": 2, "pending_overtime": 1 },
  "payroll":    { "pending_runs": 5, "unpaid_gross": 12450.00, "last_approved_gross": 88200.00 }
}
```

### Sample: `GET /api/staff?active=1&per_page=2`

```json
{
  "data": [
    {
      "id": "9b1c...",
      "employee_id": "STAFF001",
      "name": "Jane Doe",
      "email": "jane@bcf.com",
      "is_active": true,
      "hire_date": "2024-03-01",
      "hourly_rate": 18.5,
      "contracted_hours": 40,
      "roles": ["manager"],
      "avatar_url": "https://..."
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta":  { "current_page": 1, "per_page": 2, "total": 38 }
}
```

## 4. cURL test

```bash
curl -H "Authorization: Bearer <token>" \
     -H "Accept: application/json" \
     https://staff.bespokegardenroomsballycastle.co.uk/api/dashboard
```

## Notes / limits

- **Read-only.** No write/POST endpoints are exposed.
- IDs are UUIDs (strings).
- Times are ISO-8601 with timezone; dates are `YYYY-MM-DD`.
- There is no per-staff "business" field — business breakdowns come from
  **projects** (`/jobs/summary` → `projects_by_business`).
