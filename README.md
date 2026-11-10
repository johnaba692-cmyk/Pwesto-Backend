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

Build the React application from the separate `Pwesto Frontend` project before
deploying so its output is available in `public/assets/`.

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
