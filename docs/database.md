# Fastigo Database Design V2

## Overview

Fastigo is designed as a SaaS multi-tenant system.

Fastigo uses **MySQL** as the default production and local development database for the Laravel app inside `/admin`.

One company can have:

* Multiple branches
* Multiple employees
* Multiple customers through bills
* Multiple bills
* Multiple expenses
* Its own categories, items, services, and prices

Company data must be isolated using `company_id` in all company-owned tables.

## Multi-Tenant Rule

Fastigo will use **one shared database** with tenant isolation by `company_id`.

This means:

* Each company record has its own `id`.
* All branch, user, item, bill, and expense records must be connected to `company_id`.
* Queries for company users must always be filtered by `company_id`.
* Platform admins can view all companies.
* Company managers can view only their company data.
* Branch employees can view only their assigned branch data.

---

# 1. subscription_packages

Stores Fastigo subscription plans.

| Field | Type |
| --- | --- |
| id | bigint |
| name | string |
| monthly_price | decimal(10,3) |
| yearly_price | decimal(10,3) |
| max_branches | integer |
| max_employees | integer |
| features | json nullable |
| status | active / inactive |
| created_at | timestamp |
| updated_at | timestamp |

Relationship:

* Subscription Package → Many Companies

---

# 2. companies

Stores company information.

| Field | Type |
| --- | --- |
| id | bigint |
| subscription_package_id | bigint nullable |
| name | string |
| commercial_registration | string nullable |
| contact_person | string nullable |
| phone | string |
| email | string nullable |
| address | text nullable |
| subscription_start | date nullable |
| subscription_end | date nullable |
| status | active / suspended |
| created_at | timestamp |
| updated_at | timestamp |

Notes:

* `status = suspended` means the company cannot create new bills.
* `subscription_package_id` can be nullable during trial or manual setup.

Relationship:

* Company → Many Branches
* Company → Many Users
* Company → Many Categories
* Company → Many Items
* Company → Many Bills
* Company → Many Expenses

---

# 3. branches

Stores company branches.

| Field | Type |
| --- | --- |
| id | bigint |
| company_id | bigint |
| name | string |
| phone | string nullable |
| address | text nullable |
| status | active / inactive |
| created_at | timestamp |
| updated_at | timestamp |

Relationship:

* Company → Many Branches
* Branch → Many Users
* Branch → Many Bills
* Branch → Many Expenses

---

# 4. users

Stores Fastigo platform users, company managers, and branch employees.

| Field | Type |
| --- | --- |
| id | bigint |
| company_id | bigint nullable |
| branch_id | bigint nullable |
| name | string |
| phone | string nullable |
| email | string unique |
| password | string |
| role | platform_admin / company_manager / branch_employee |
| status | active / inactive |
| last_login_at | timestamp nullable |
| created_at | timestamp |
| updated_at | timestamp |

Important rules:

* `platform_admin` has `company_id = null` and `branch_id = null`.
* `company_manager` has `company_id` but may have `branch_id = null`.
* `branch_employee` must have both `company_id` and `branch_id`.

Relationship:

* Company → Many Users
* Branch → Many Users
* User → Many Bills Created
* User → Many Expenses Created

---

# 5. customers

Stores public customers who use the Fastigo customer app.

| Field | Type |
| --- | --- |
| id | bigint |
| phone | string unique |
| name | string nullable |
| created_at | timestamp |
| updated_at | timestamp |

Customer login is based on phone number and OTP.

Important rule:

* Customer phone must be unique because the same customer can track bills from multiple companies using one Fastigo account.

Relationship:

* Customer → Many Bills
* Customer → Many Notifications

---

# 6. categories

Stores company item categories.

Examples:

* Laundry
* Tailoring
* Car Wash
* Food
* Services

| Field | Type |
| --- | --- |
| id | bigint |
| company_id | bigint |
| name | string |
| status | active / inactive |
| created_at | timestamp |
| updated_at | timestamp |

Relationship:

* Company → Many Categories
* Category → Many Items

---

# 7. items

Stores company products and services.

| Field | Type |
| --- | --- |
| id | bigint |
| company_id | bigint |
| category_id | bigint |
| name | string |
| type | service / product |
| price | decimal(10,3) |
| status | active / inactive |
| created_at | timestamp |
| updated_at | timestamp |

Notes:

* `service` is used for trackable work such as laundry, tailoring, and car wash.
* `product` is used for direct sale items without service tracking.

Relationship:

* Company → Many Items
* Category → Many Items
* Item → Many Bill Items

---

# 8. bills

Main bill table.

| Field | Type |
| --- | --- |
| id | bigint |
| company_id | bigint |
| branch_id | bigint |
| customer_id | bigint |
| bill_number | string |
| total_amount | decimal(10,3) |
| paid_amount | decimal(10,3) default 0 |
| remaining_amount | decimal(10,3) |
| payment_status | unpaid / partial / paid |
| status | in_process / ready / delivered / cancelled |
| due_date | date nullable |
| ready_at | timestamp nullable |
| delivered_at | timestamp nullable |
| notes | text nullable |
| created_by | bigint |
| created_at | timestamp |
| updated_at | timestamp |

Important rules:

* `bill_number` should be unique per company or per branch.
* `remaining_amount = total_amount - paid_amount`.
* When status changes, a record must be added to `bill_status_histories`.

Relationship:

* Company → Many Bills
* Branch → Many Bills
* Customer → Many Bills
* Bill → Many Bill Items
* Bill → Many Status History Records
* User → Many Bills Created

---

# 9. bill_items

Stores bill details.

| Field | Type |
| --- | --- |
| id | bigint |
| bill_id | bigint |
| item_id | bigint nullable |
| item_name | string |
| item_type | service / product |
| quantity | decimal(10,2) |
| unit_price | decimal(10,3) |
| total | decimal(10,3) |
| notes | text nullable |
| created_at | timestamp |
| updated_at | timestamp |

Important rule:

* `item_name`, `item_type`, and `unit_price` are stored as snapshots so old bills remain correct if the item price or name changes later.

Relationship:

* Bill → Many Bill Items
* Item → Many Bill Items

---

# 10. bill_status_histories

Stores every status change for a bill.

| Field | Type |
| --- | --- |
| id | bigint |
| bill_id | bigint |
| old_status | string nullable |
| new_status | string |
| changed_by | bigint nullable |
| notes | text nullable |
| created_at | timestamp |

Examples:

* in_process → ready
* ready → delivered
* in_process → cancelled

Relationship:

* Bill → Many Status History Records
* User → Many Status Changes

---

# 11. expense_categories

Stores company expense categories.

Examples:

* Rent
* Salary
* Utilities
* Maintenance
* Cleaning Materials
* Fuel

| Field | Type |
| --- | --- |
| id | bigint |
| company_id | bigint |
| name | string |
| status | active / inactive |
| created_at | timestamp |
| updated_at | timestamp |

Relationship:

* Company → Many Expense Categories
* Expense Category → Many Expenses

---

# 12. expenses

Stores company and branch expenses.

| Field | Type |
| --- | --- |
| id | bigint |
| company_id | bigint |
| branch_id | bigint nullable |
| category_id | bigint |
| title | string |
| amount | decimal(10,3) |
| notes | text nullable |
| expense_date | date |
| created_by | bigint |
| created_at | timestamp |
| updated_at | timestamp |

Notes:

* `branch_id` is nullable because some expenses belong to the whole company, not one branch.

Relationship:

* Company → Many Expenses
* Branch → Many Expenses
* Expense Category → Many Expenses
* User → Many Expenses Created

---

# 13. notifications

Stores customer notifications.

| Field | Type |
| --- | --- |
| id | bigint |
| customer_id | bigint |
| bill_id | bigint nullable |
| title | string |
| message | text |
| type | new_bill / order_ready / order_delivered / general |
| is_read | boolean default false |
| read_at | timestamp nullable |
| created_at | timestamp |
| updated_at | timestamp |

Examples:

* New bill created
* Order ready
* Order delivered

Relationship:

* Customer → Many Notifications
* Bill → Many Notifications

---

# 14. otp_verifications

Stores WhatsApp OTP verification requests.

| Field | Type |
| --- | --- |
| id | bigint |
| phone | string |
| otp_code | string |
| purpose | login / register / password_reset |
| attempts | integer default 0 |
| ip_address | string nullable |
| expires_at | timestamp |
| verified_at | timestamp nullable |
| created_at | timestamp |
| updated_at | timestamp |

Important rules:

* OTP should expire quickly, for example after 5 minutes.
* OTP attempts should be limited.
* Verified OTP records can be kept for audit, or deleted later using a scheduled cleanup command.

---

# Recommended Laravel Build Order

1. subscription_packages
2. companies
3. branches
4. users
5. customers
6. categories
7. items
8. bills
9. bill_items
10. bill_status_histories
11. expense_categories
12. expenses
13. notifications
14. otp_verifications

---

# Required Indexes and Constraints

## Unique indexes

| Table | Unique Rule |
| --- | --- |
| customers | phone unique |
| users | email unique |
| companies | commercial_registration nullable unique, optional |
| bills | company_id + bill_number unique |
| categories | company_id + name unique |
| expense_categories | company_id + name unique |

## Common indexes

| Table | Indexes |
| --- | --- |
| branches | company_id |
| users | company_id, branch_id, role, status |
| categories | company_id |
| items | company_id, category_id, status |
| bills | company_id, branch_id, customer_id, status, payment_status, created_at |
| bill_items | bill_id, item_id |
| expenses | company_id, branch_id, category_id, expense_date |
| notifications | customer_id, bill_id, is_read |
| otp_verifications | phone, expires_at |

---

# Future Tables

Planned for future versions:

* payments
* invoice_pdfs
* loyalty_points
* customer_reviews
* whatsapp_templates
* integrations
* audit_logs
* company_settings
* branch_settings
* payment_methods
* inventory_movements

---

# Notes for Fastigo Apps

## Fastigo Customer App

Uses:

* customers
* bills
* bill_items
* notifications
* otp_verifications

Main purpose:

* Customer login by phone and OTP
* Track bills from all companies using Fastigo
* Receive notifications when order is ready or delivered

## Fastigo Business App

Uses:

* users
* companies
* branches
* customers
* categories
* items
* bills
* bill_items
* expenses
* notifications

Main purpose:

* Company manager manages branches, items, bills, expenses, and reports
* Branch employee creates bills, updates status, records expenses, and prints receipts

## Fastigo Admin Panel

Uses all tables.

Main purpose:

* Platform owner manages companies
* Platform owner manages subscriptions
* Platform owner monitors system activity
