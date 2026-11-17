# Pwesto Backend

LavaLust PHP backend for Pwesto, a local public-market web app. It serves the
JSON API used by the React frontend and the server-rendered page shell. This
folder is the backend deployment root.

## Requirements

- PHP 8.x with `curl`, `pdo_mysql`, `fileinfo` and OpenSSL enabled
- MySQL 8 (local Laragon MySQL or Aiven MySQL)
- Composer 2

## Project structure

The project follows the standard LavaLust layout:

```text
app/
  config/       config.php, database.php, api.php, autoload.php, routes.php,
                pwesto.php (library settings read with config_item())
  controllers/  one controller per resource (Auth, Catalog, Products, Orders,
                Vendor_orders, Stalls, Favorites, Appointments, Messages,
                Notifications, Profile, Admin, Payments, Assistant, Welcome, Migrate)
  helpers/      pwesto_helper.php (utc_now, utc_today, random_id)
  kernel/       MY_Controller.php - base API controller, auto-loaded by LavaLust
  libraries/    Cloudinary_storage, Mailer, Xendit, Google_identity, Gemini,
                Upload_validator, Order_notifier
  migrations/   004_create_pwesto_tables.php
  models/       one model per table (UserModel, VendorModel, ProductModel,
                OrderModel, OrderItemModel, FavoriteModel, AppointmentModel,
                ConversationModel, MessageModel, NotificationModel,
                PasswordResetModel, AuthAttemptModel)
  views/        page shell and error pages
console/        LavaLust CLI (php lava ...)
public/         web root (index.php, built React assets, uploads)
runtime/        sessions, cache and logs (not in Git)
scheme/         LavaLust framework core
```

How the framework is used:

- **Database**: `app/config/database.php` reads the `DB_*` values from `.env`;
  controllers connect with `$this->call->database()` (in `MY_Controller`).
- **Models**: extend LavaLust `Model` and use the Query Builder
  (`$this->db->table()->where()->get_all()`, `insert()`, `update()`,
  `delete()`, `increment()`, transactions) with bound parameters only.
- **Loading**: models, libraries and helpers are loaded with
  `$this->call->model()`, `$this->call->library()` and the autoload config;
  application code never uses `require`/`include`.
- **API**: requests and responses go through the LavaLust Api library
  (`$this->api->body()`, `get_query_params()`, `respond()`, `rate_limit()`).
- **Configuration**: library settings live in `app/config/pwesto.php`
  (autoloaded through `$autoload['configs']`) and are read with `config_item()`.
- **Composer**: Cloudinary and PHPMailer are loaded through
  `$config['composer_autoload'] = TRUE` in `app/config/config.php`.

## Local setup

1. Copy `.env.example` to `.env` and fill in the values. Keep `.env` private.
2. Install PHP dependencies: `composer install`.
3. Generate an application key with `php lava key:generate` if `APP_KEY` is blank.
4. Start the server: `php lava serve` (http://127.0.0.1:3000/).

The root page is the LavaLust landing page, the Pwesto application is at
`/pwesto`, and the API is served under `/api/`.

### Database schema

The schema is the LavaLust migration `app/migrations/004_create_pwesto_tables.php`
(every table uses `CREATE TABLE IF NOT EXISTS`). To create it on a new database:

1. Set `$config['migration_enabled'] = TRUE;` in `app/config/migration.php`.
2. With `APP_ENV=development`, open http://127.0.0.1:3000/migrate from the same machine.
3. Set `migration_enabled` back to `FALSE`.

The `/migrate` route returns 404 unless all three conditions hold
(development environment, loopback address, migrations enabled).

### Remote MySQL (Aiven)

Remote MySQL connections require verified TLS. The image includes the Aiven CA
certificate at `app/config/aiven-ca.pem`; alternatively provide a Render Secret
File and set `PWESTO_DB_SSL_CA` to its `/etc/secrets/` path. Update the bundled
certificate if Aiven rotates its CA. Do not set `DB_HOST=localhost` on Render;
localhost there refers to the Render container.

### Gmail email delivery

Set `PWESTO_MAIL_USERNAME` to the Gmail account, `PWESTO_MAIL_PASSWORD` to its
Google App Password (not the normal account password), and `PWESTO_APP_URL` to
the public Pwesto URL. A reset request deliberately gives a generic response for
unknown accounts and rate-limited requests; check the logs for
`Pwesto password reset not sent` to distinguish those cases.

### Google sign-in

Set `GOOGLE_CLIENT_ID` to the OAuth 2.0 **Web application** client ID and add
every origin where the sign-in page opens to **Authorized JavaScript origins**:

```text
https://pwesto-backend-2.onrender.com
https://pwesto-frontend.onrender.com
http://127.0.0.1:3000
http://localhost:3000
http://127.0.0.1:5173
```

## Security

- Prepared statements only (Query Builder bindings); no SQL string concatenation.
- Passwords hashed with `password_hash()`; session ID regenerated after login
  (`after_successful_login()`) and on logout.
- CSRF token required on every state-changing request (`X-CSRF-Token` header).
- Brute-force protection: per-account lockout (8 failures / 15 minutes) plus the
  Api library's per-IP `rate_limit()` on sign-in, registration and password reset.
- Ownership checks on every vendor, product, order and conversation query.
- Uploads: real MIME type checked with `finfo`, size limits, allow-lists and
  random file names; private documents are served only through the admin controller.
- Security headers from the LavaLust Security_headers class; set
  `APP_ENV=production` in production so error details are not shown.

## Docker

Build after building the frontend (`npm run build:backend` in `Pwesto Frontend`):

```powershell
docker build -t pwesto-backend .
docker run --env-file .env -p 8080:80 `
  -v pwesto-runtime:/var/www/html/runtime `
  -v pwesto-uploads:/var/www/html/public/uploads pwesto-backend
```

Keep the runtime and upload volumes persistent. Configure production credentials
as environment variables; never put secrets in frontend `VITE_` variables.
