# Fastigo Architecture

## Repository Layout

- `/admin` - Laravel API, Filament admin panel, database migrations, seeders, and Laravel tests.
- `/mobile/fastigo_business` - Flutter business app for companies and shops.
- `/mobile/fastigo` - Flutter public customer app for bill tracking, notifications, and subscriptions.
- `/docs` - Product and engineering documentation.

## Admin and API

- Framework: Laravel
- Admin panel: Laravel Filament
- API authentication: Laravel Sanctum
- Database: MySQL 8
- Server target: Ubuntu 24 LTS, Nginx, PHP 8.4

The `/admin` app owns the platform admin panel and the API used by both mobile apps.

## Mobile Apps

- `fastigo_business`: company/shop operations app for login, dashboard, bill creation, status updates, products/services, expenses, reports, branch data, and subscriptions.
- `fastigo`: public customer app for mobile login, OTP verification, bill tracking, notification history, and viewing subscription/customer-facing status.

## Notifications and OTP

- In-app notifications: stored by Laravel in the database.
- Push notifications: Firebase Cloud Messaging (FCM), planned.
- OTP: WhatsApp Business API, planned; local/dev mode may expose OTP for testing.

## Future Storage

- Amazon S3 or DigitalOcean Spaces for uploaded files and generated invoice PDFs.
