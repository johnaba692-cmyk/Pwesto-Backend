<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| Pwesto application and third-party library settings
| -------------------------------------------------------------------
| Loaded automatically through $autoload['configs'] in autoload.php and
| read with config_item('<key>'). Secrets come from the .env file so no
| credential is hardcoded in source control.
*/

// Public base URL used in password reset links (https, or http on localhost).
$config['pwesto_app_url'] = trim((string) (getenv('PWESTO_APP_URL') ?: ''));

// Comma-separated list of administrator email addresses.
$config['pwesto_admin_emails'] = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) (getenv('PWESTO_ADMIN_EMAILS') ?: ''))
)));

// Monthly stall rent in whole pesos.
$config['pwesto_monthly_rent'] = getenv('PWESTO_MONTHLY_RENT_PHP') ?: '7000';

// Supported public markets and their map coordinates.
$config['pwesto_markets'] = [
    'Calapan City Public Market' => ['lat' => 13.4118, 'lng' => 121.1809],
];

// Google Identity Services (sign in with Google).
$config['google_client_id'] = trim((string) (getenv('GOOGLE_CLIENT_ID') ?: ''));

// Gemini API used by the shopping assistant.
$config['gemini'] = [
    'api_key' => trim((string) (getenv('GEMINI_API_KEY') ?: '')),
    'models'  => ['gemini-3.8-flash', 'gemini-3.6-flash', 'gemini-3.5-flash'],
];

// Gmail SMTP used by the Mailer library (PHPMailer).
$config['mail'] = [
    'host'      => trim((string) (getenv('PWESTO_MAIL_HOST') ?: 'smtp.gmail.com')),
    'port'      => trim((string) (getenv('PWESTO_MAIL_PORT') ?: '587')),
    'username'  => trim((string) (getenv('PWESTO_MAIL_USERNAME') ?: '')),
    'password'  => (string) (getenv('PWESTO_MAIL_PASSWORD') ?: ''),
    'from'      => trim((string) (getenv('PWESTO_MAIL_FROM') ?: '')),
    'from_name' => trim((string) (getenv('PWESTO_MAIL_FROM_NAME') ?: 'Pwesto')),
];

// Cloudinary storage for product images and private vendor documents.
$config['cloudinary'] = [
    'cloud_name' => trim((string) (getenv('CLOUDINARY_CLOUD_NAME') ?: '')),
    'api_key'    => trim((string) (getenv('CLOUDINARY_API_KEY') ?: '')),
    'api_secret' => trim((string) (getenv('CLOUDINARY_API_SECRET') ?: '')),
];

// Xendit GCash checkout for product orders and stall rent.
$config['xendit'] = [
    'mode'          => getenv('XENDIT_MODE') ?: 'test',
    'secret_key'    => trim((string) (getenv('XENDIT_SECRET_KEY') ?: '')),
    'webhook_token' => trim((string) (getenv('XENDIT_WEBHOOK_TOKEN') ?: '')),
    'base_url'      => 'https://api.xendit.co',
];
