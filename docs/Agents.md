# Fastigo Agent Instructions

## Project

Fastigo is a SaaS platform with:

- Laravel API and Filament admin panel in `/admin`
- Landing website in `/landing`
- Flutter customer/public mobile app in `/mobile/fastigo`
- Flutter business mobile app in `/mobile/fastigo_business`
- Relational database (currently MySQL)

## Rules

- Do not change project structure without asking.
- Follow the documentation inside `/docs`.
- Start admin/API work before mobile work.
- Use Laravel conventions.
- Use Filament for admin panel.
- Use clear migrations, models, relationships, and resources.
- Never delete existing files unless clearly instructed.
- After every task, explain what files were changed.
- Any database, API, workflow, permission, or business logic change must be documented in `/docs` before commit or push.

## Data Isolation

- All business data must be isolated by Company.
- No company may access another company's data.

## Permissions

- Company Managers can access all branches belonging to their company.
- Branch Employees can only access data assigned to their branch.

## Business Rules

- Bills belong to a Company, Branch, and Customer.
- Items support:
  - Service
  - Product

## Current Build Order

1. Company
2. User Roles
3. Branch
4. Customer
5. Category
6. Item
7. Bill
8. Bill Items
9. Expense
10. Notification
11. Subscription
12. Reports

## Important

Fastigo has two mobile apps:

- `fastigo` for customers and public users.
- `fastigo_business` for companies, Company Managers, and Branch Employees.

Admin panel is only for the Fastigo platform owner and lives in `/admin`.
