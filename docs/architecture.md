# Fastigo Architecture V2

## Repository Layout

fastigo-app/

├── admin/
│   ├── app
│   ├── database
│   ├── routes
│   ├── tests
│   └── ...
│
├── mobile/
│   ├── fastigo_business/
│   └── fastigo/
│
├── landing/
│   └── Fastigo Marketing Website
│
├── docs/
│   ├── ROADMAP.md
│   ├── DATABASE.md
│   ├── ARCHITECTURE.md
│   ├── API.md
│   ├── SCREENS.md
│   ├── ROLES.md
│   └── AGENTS.md
│
└── README.md

---

## Backend Stack

- PHP 8.4
- Laravel 12
- Filament 4
- Laravel Sanctum
- MySQL 8
- Redis (Future)
- Queue Workers
- Firebase Cloud Messaging (FCM)
- WhatsApp Business API

---

## User Roles

### Platform Admin
- Manage entire Fastigo platform
- Manage subscriptions
- Manage companies
- Manage system settings

### Company Manager
- Manage all branches
- Manage employees
- View company-wide reports

### Branch Employee
- Create bills
- Update bill status
- Manage expenses for assigned branch

### Customer
- Login with mobile number
- Track bills
- Receive notifications

---

## Mobile Applications

### fastigo_business

Features:
- Login
- Dashboard
- Create Bills
- Update Bill Status
- Manage Services
- Manage Products
- Manage Expenses
- Reports
- Subscription Status

### fastigo

Features:
- Login by Mobile Number
- WhatsApp OTP
- Bill Tracking
- Notifications
- Profile

---

## Infrastructure

### Production Environment

- Ubuntu 24.04 LTS
- Nginx
- PHP-FPM
- MySQL 8
- SSL
- Supervisor
- Queue Workers

### Storage

MVP:
- Local Storage

Future:
- Amazon S3
- DigitalOcean Spaces

---

## Notifications

- Database Notifications
- Firebase Push Notifications
- WhatsApp Notifications

---

## Caching

- Redis

---

## Queue System

- Laravel Queue
- Supervisor

---

## Monitoring

Development:
- Laravel Telescope

Production:
- Sentry

---

## Backup Strategy

- Daily Database Backup
- Weekly Full Backup

---

## CI/CD

- GitHub
- GitHub Actions
- VPS Deployment Pipeline

---

## Current Development Order

1. Database Design
2. Laravel Installation
3. Sanctum Authentication
4. Migrations
5. Models
6. Relationships
7. Filament Resources
8. API Development
9. fastigo_business App
10. fastigo Customer App
11. Notifications
12. Reports
13. Landing Website
