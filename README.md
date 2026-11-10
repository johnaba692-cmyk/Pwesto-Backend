# Pwesto Backend

LavaLust PHP backend for Pwesto. This folder is the backend deployment root.

## Requirements

- PHP 8.x with `curl`, `PDO_SQLite`, `PDO_MySQL`, and OpenSSL enabled
- Composer 2
- Node.js is only needed if rebuilding the separate React frontend

## Local setup

1. Copy `.env.example` to `.env` and set the backend configuration. Keep `.env`
   private and out of source control.
2. From this folder, install PHP dependencies with `composer install`.
3. Generate an application key with `php lava key:generate` if `APP_KEY` is
   blank. Keep the same key between deployments.
4. Start the development server with `php lava serve`.

The backend runs at `http://127.0.0.1:3000/`. The root page is the LavaLust
Framework landing page, the Pwesto application is at `/pwesto`, and APIs are
served under `/api/`.

Pwesto uses SQLite by default. To use local Laragon MySQL, create a database
and set `PWESTO_DB_DRIVER=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`,
`DB_NAME`, `DB_USER`, and `DB_PASSWORD` in `.env`. Loopback MySQL connections
do not require a TLS CA file; remote MySQL connections require verified TLS.

Build the React application from the separate `Pwesto Frontend` project before
deploying so its output is available in `public/assets/`.

### Render SQLite storage

For a Render deployment using SQLite, attach a persistent disk to the web
service at `/var/data`. The Docker entrypoint uses `/var/data/pwesto.sqlite`
on Render by default. You can also set these environment variables explicitly:

```text
PWESTO_DB_DRIVER=sqlite
PWESTO_DB_PATH=/var/data/pwesto.sqlite
```

The Docker entrypoint passes the database path to PHP, prepares the mounted
directory for Apache's `www-data` user, and stops startup if Render is using
SQLite without a disk mounted at `/var/data` or if the database path is outside
that mount. This prevents the app from silently writing to Render's temporary
container filesystem. Attach the disk before deploying this change. Preserve or
back up any existing database before changing its path; a new empty disk does
not restore data that was already lost. Do not set `DB_HOST=localhost` on
Render; localhost there refers to the Render container, not your computer.

The backend does not restore sample stall listings. The schema migration retires
the four extra sample stalls, preserving their rows and order history. It
configures the existing `bukid-meats` and `fresh-harvest` records as `Meat 001`
and `Fruit 001`, using the vendor emails in `PWESTO_MEAT_VENDOR_EMAIL` and
`PWESTO_FRUIT_VENDOR_EMAIL`. Set those variables to the existing vendor accounts
before deploying. Public stall and product listings require an approved vendor
account and at least one active, in-stock, non-sample product. Restore real
product data only from a trusted production backup; do not invent products or
copy user credentials from a local database.

### Gmail email delivery

Set `PWESTO_MAIL_USERNAME` to the Gmail account, `PWESTO_MAIL_PASSWORD` to its
Google App Password (not the normal account password), and `PWESTO_APP_URL` to
the public Pwesto URL. `PWESTO_MAIL_FROM` is optional and defaults to the Gmail
username. For Render, configure these in the backend service's Environment
settings and redeploy. A reset request deliberately uses a generic response for
unknown accounts and rate-limited requests; check Render logs for
`Pwesto password reset not sent` to distinguish those cases without exposing
email addresses.

### Google sign-in on Render

In Google Cloud Console, open the OAuth 2.0 client used by this app (type
**Web application**) and add the origins where the sign-in page is opened to
**Authorized JavaScript origins**. For this deployment, add:

```text
https://pwesto-backend.onrender.com
https://pwesto-frontend.onrender.com
http://127.0.0.1:3000
http://localhost:3000
http://127.0.0.1:5173
```

Set `GOOGLE_CLIENT_ID` on the Render backend to that same Web application
client ID, save the OAuth client, and redeploy the backend if its environment
variable changed. The `origin_mismatch` error is enforced by Google and cannot
be fixed by changing the sign-in code alone.

## Docker

Build this image from this folder after building the frontend:

```powershell
docker build -t pwesto-backend .
docker run --env-file .env -p 8080:80 `
  -v pwesto-runtime:/var/www/html/runtime `
  -v pwesto-uploads:/var/www/html/public/uploads pwesto-backend
```

Keep the runtime and upload volumes persistent. Configure production credentials
as environment variables; never put secrets in frontend `VITE_` variables.
