# Fastigo App

Fastigo is a SaaS platform for companies to manage bills, statuses, expenses, and customer tracking by mobile number.

## Repository Structure

- `admin/` - Laravel API and Filament admin panel for the Fastigo platform owner.
- `mobile/fastigo_business/` - Flutter app for companies, shops, owners, managers, and employees.
- `mobile/fastigo/` - Flutter app for public customers to track bills, notifications, and subscriptions.
- `docs/` - Product vision, architecture, requirements, database plan, API plan, screens, and roadmap.

## Admin Database

The Laravel application in `admin/` is configured to use MySQL by default.

Default local database settings:

- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=fastigo`
- `DB_USERNAME=root`

## Documentation Rule

When the product scenario, folder structure, app responsibility, or build order changes, update the files in `docs/` in the same change.
