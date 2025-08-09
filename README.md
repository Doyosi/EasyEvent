
> Simple, extensible event manager for Laravel 12 with Web/Panel routes, API, helpers, Vite wiring, and an installer CLI.

## ✨ Features

- Migration with practical fields (type, title, description, starts/ends, all\_day, location, status, meta)
- Model + handy scopes (published, today, thisMonth, upcoming, past, between)
- Web + Panel controllers & routes (macros to mount anywhere)
- JSON API (GET list + show) with flexible query params
- Helpers + Facade for quick access in Blade/Controllers
- Vite installer that scaffolds JS widget and patches `vite.config.js`
- Safe uninstall, restore Vite inputs from manifest, and automatic backups

## 🧩 Requirements

- PHP **8.2+**
- Laravel **12.x**

## 📦 Installation

Add via Composer (packagist or path repo):

```bash
composer require doyosi/easy-event
```

Or local dev (example):

```json
{
  "repositories": [ { "type": "path", "url": "packages/doyosi/easy-event" } ]
}
```

```bash
composer require doyosi/easy-event:"*@dev"
```

Install assets + DB:

```bash
php artisan doyosi:event --install
# optional: also wire Vite and run dev
php artisan doyosi:event --install --vite --run-npm=dev
```

### Seed sample data
```bash
php artisan db:seed --class="Doyosi\EasyEvent\Database\Seeders\EasyEventSeeder"
# or during install:
php artisan doyosi:event --install --seed
```


Publish manually (optional):

```bash
php artisan vendor:publish --provider="Doyosi\EasyEvent\EasyEventServiceProvider" --tag=easy-event-config
php artisan vendor:publish --provider="Doyosi\EasyEvent\EasyEventServiceProvider" --tag=easy-event-migrations
php artisan migrate
```

## ⚙️ Configuration

`config/easy-event.php`:

```php
return [
    'table' => 'easy_events',
    'routes' => [
        'web' => [ 'enabled' => true, 'prefix' => 'events', 'name' => 'easy-events.', 'middleware' => ['web'] ],
        'panel'=> [ 'enabled' => true, 'prefix' => 'panel/easy-events', 'name' => 'panel.easy-events.', 'middleware' => ['web','auth'] ],
        'api'  => [
            'enabled' => true,
            'prefix'  => 'api/easy-events', // final URL will be /api/easy-events
            'name'    => 'easy-events.api.',
            'middleware' => ['api'], // add 'auth:sanctum' if needed
            'paginate_default' => 0,
            'per_page' => 15,
            'max_limit' => 100,
        ],
    ],
    'pagination' => 15,
    'date_format' => 'Y-m-d H:i',
    'status' => ['draft', 'published', 'archived'],
    'types'  => ['meeting','holiday','webinar','workshop','custom'],
];
```

## 🗄️ Database

Migration creates table (default `easy_events`) with columns:

- `id` (PK)
- `event_id` (nullable string, external id)
- `type` (string, indexed)
- `title` (string)
- `description` (text, nullable)
- `starts_at` (datetime, indexed)
- `ends_at` (datetime, nullable, indexed)
- `all_day` (boolean)
- `location` (string, nullable)
- `status` (string: draft|published|archived)
- `meta` (json, nullable)
- timestamps + composite index on `starts_at, ends_at`

## 🧠 Model & Scopes

`Doyosi\EasyEvent\Models\Event`

```php
Event::published();
Event::type('webinar');
Event::between('2025-08-01', '2025-08-31');
Event::upcoming();
Event::past();
Event::today();
Event::thisMonth();
```

## 🧭 Routes

Auto-loaded if enabled in config. You can also mount explicitly via macros:

```php
// routes/web.php
Route::easyEvents();        // public views
Route::easyEventsPanel();   // panel CRUD
Route::easyEventsApi();     // JSON API
```

- Web: `GET /events`, `GET /events/{event}`
- Panel: `GET /panel/easy-events`, create/store/edit/update/destroy
- API: `GET /api/easy-events`, `GET /api/easy-events/{event}`

## 🔧 Helpers & Facade

Helpers:

```php
easy_events_today($limit = null);
easy_events_month($limit = null);
easy_events_recent($limit = 5);
easy_events_upcoming($limit = 10);
```

Facade:

```php
use EasyEvent; // alias provided
EasyEvent::upcoming(5);
```

## 🧰 CLI — Installer

```bash
php artisan doyosi:event \
  [--install] [--vite] [--run-npm=dev|build] \
  [--uninstall] [--purge] [--drop-table] \
  [--restore-vite[=path]] [--backup] [--force]
```

**Options**

- `--install` publish config & migrations, run `migrate`
- `--vite` scaffold `resources/js/easy-event.js` + `modules/EasyEventWidget.js` and patch `vite.config.js` (idempotent)
- `--run-npm=dev|build` run npm script
- `--uninstall` remove Vite entry & JS scaffolds (preserves user-modified files by renaming to `.bak`)
- `--purge` (with `--uninstall`) delete published config & migration files
- `--drop-table` (with `--uninstall`) drop events table
- `--restore-vite[=path]` rewrite `input: []` from manifest (default `doyosi.vite.json`)
- `--backup` create timestamped `vite.config.js.*.bak` before any edit
- `--force` skip confirmations

**Examples**

```bash
# typical install with vite and dev server
php artisan doyosi:event --install --vite --run-npm=dev

# clean uninstall but keep DB & config
php artisan doyosi:event --uninstall

# full purge and drop table (CI/non-interactive)
php artisan doyosi:event --uninstall --purge --drop-table --force

# regenerate vite inputs from manifest (creates template if missing)
php artisan doyosi:event --restore-vite --backup
```

### Vite manifest

`doyosi.vite.json` (created if missing when `--restore-vite` is used):

```json
{
  "input": [
    "resources/css/app.css",
    "resources/js/app.js",
    "resources/js/easy-event.js",
    "resources/js/web.js",
    "resources/js/panel.js"
  ]
}
```

## 🧩 JS Widget (Native ESM)

The installer creates:

- `resources/js/modules/EasyEventWidget.js`
- `resources/js/easy-event.js`

Blade usage:

```blade
@vite('resources/js/easy-event.js')
<div data-easy-event data-endpoint="/api/easy-events" data-limit="8"></div>
```

Widget auto-inits on `[data-easy-event]` and fetches JSON.

## 🌐 API Overview

Two endpoints (read-only):

- `GET /api/easy-events` — list
- `GET /api/easy-events/{event}` — single

**Query params for list**

- `limit` (int) — return N items (no pagination)
- `paginate` (0|1) — enable pagination (default from config)
- `per_page` (int) — with `paginate=1`
- `scope` (today|month|upcoming|past)
- `type` (string) — filter by event type
- `status` (string) — one of config `status`
- `from`, `to` (date/datetime) — explicit range (overrides `scope`)

**Response shapes**

- Default & `limit` → **array of Event** (no wrapper)
- `paginate=1` → `{ data: Event[], links: {...}, meta: {...} }` (paginator uses data wrapper)

**Event JSON fields**

```json
{
  "id": 1,
  "event_id": null,
  "type": "webinar",
  "title": "Intro to EasyEvent",
  "description": "...",
  "starts_at": "2025-08-15T10:00:00+03:00",
  "ends_at": "2025-08-15T11:00:00+03:00",
  "starts_at_formatted": "2025-08-15 10:00",
  "ends_at_formatted": "2025-08-15 11:00",
  "all_day": false,
  "location": "Online",
  "status": "published",
  "meta": {}
}
```

**cURL examples**

```bash
# list default
curl -s http://localhost/api/easy-events | jq .

# upcoming limited
curl -s "http://localhost/api/easy-events?scope=upcoming&limit=5" | jq .

# paginated
curl -s "http://localhost/api/easy-events?paginate=1&per_page=10" | jq .

# single
curl -s http://localhost/api/easy-events/1 | jq .
```

**Auth** By default uses `['api']` middleware. Add `auth:sanctum` (or any) in `config/easy-event.php` → `routes.api.middleware`.

## 🧪 Testing (suggested)

- Factory for `Event`
- Scope unit tests (today/thisMonth/upcoming/past/between)
- API feature tests (list filters, pagination, single show, 404 for draft)

## 🪪 License

MIT — see LICENSE.

## 🤝 Contributing

PRs welcome. Please follow PSR-12 and include tests.