# Fastigo Agent Instructions

## Project
Fastigo is a SaaS platform with:
- Laravel API and Filament admin panel in `/admin`
- Flutter customer/public mobile app in `/mobile/fastigo`
- Flutter business mobile app in `/mobile/fastigo_business`
- MySQL database

## Rules
- Do not change project structure without asking.
- Follow the documentation inside /docs.
- Start admin/API work before mobile work.
- Use Laravel conventions.
- Use Filament for admin panel.
- Use clear migrations, models, relationships, and resources.
- Never delete existing files unless clearly instructed.
- After every task, explain what files were changed.

## Current Build Order
1. Company
2. Branch
3. User roles
4. Customer
5. Category
6. Item
7. Bill
8. Bill items
9. Expenses
10. Notifications

## Important
Fastigo has two mobile apps:
- `fastigo` for customers and public users.
- `fastigo_business` for companies, Company Managers, and Branch Employees.

Admin panel is only for the Fastigo platform owner and lives in `/admin`.

Every scenario or structure change must be saved in `/docs` before pushing.
