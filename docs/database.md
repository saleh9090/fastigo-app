# Fastigo Database Design

## Overview

The database is designed as a SaaS multi-tenant system.

Fastigo uses MySQL as the default production and local development database for the Laravel app in `/admin`.

One company can have:

* Multiple branches
* Multiple employees
* Multiple bills
* Multiple expenses

Each company data must be isolated from other companies.

---

# companies

Stores company information.

| Field                   | Type               |
| ----------------------- | ------------------ |
| id                      | bigint             |
| name                    | string             |
| commercial_registration | string             |
| contact_person          | string             |
| phone                   | string             |
| email                   | string             |
| address                 | text               |
| subscription_package_id | bigint             |
| subscription_start      | date               |
| subscription_end        | date               |
| status                  | active / suspended |
| created_at              | timestamp          |

---

# branches

Stores company branches.

| Field      | Type      |
| ---------- | --------- |
| id         | bigint    |
| company_id | bigint    |
| name       | string    |
| phone      | string    |
| address    | text      |
| created_at | timestamp |

Relationship:

Company → Many Branches

---

# users

Stores shop users and employees.

| Field      | Type                       |
| ---------- | -------------------------- |
| id         | bigint                     |
| company_id | bigint                     |
| branch_id  | bigint                     |
| name       | string                     |
| phone      | string                     |
| email      | string                     |
| password   | string                     |
| role       | owner / manager / employee |
| status     | active / inactive          |

Relationship:

Company → Many Users

---

# customers

Stores public customers.

| Field      | Type            |
| ---------- | --------------- |
| id         | bigint          |
| phone      | string          |
| name       | string nullable |
| created_at | timestamp       |

Customer login is based on phone number and OTP.

---

# categories

Stores bill item categories.

Examples:

* Laundry
* Tailoring
* Food
* Services

| Field      | Type   |
| ---------- | ------ |
| id         | bigint |
| company_id | bigint |
| name       | string |

---

# items

Stores products and services.

| Field       | Type              |
| ----------- | ----------------- |
| id          | bigint            |
| company_id  | bigint            |
| category_id | bigint            |
| name        | string            |
| price       | decimal           |
| status      | active / inactive |

---

# bills

Main bill table.

| Field            | Type                           |
| ---------------- | ------------------------------ |
| id               | bigint                         |
| company_id       | bigint                         |
| branch_id        | bigint                         |
| customer_id      | bigint                         |
| bill_number      | string                         |
| total_amount     | decimal                        |
| paid_amount      | decimal                        |
| remaining_amount | decimal                        |
| payment_status   | unpaid / partial / paid        |
| status           | in_process / ready / delivered |
| created_by       | user_id                        |
| created_at       | timestamp                      |

Relationship:

Customer → Many Bills

---

# bill_items

Stores bill details.

| Field      | Type    |
| ---------- | ------- |
| id         | bigint  |
| bill_id    | bigint  |
| item_id    | bigint  |
| quantity   | decimal |
| unit_price | decimal |
| total      | decimal |

Relationship:

Bill → Many Items

---

# expense_categories

| Field      | Type   |
| ---------- | ------ |
| id         | bigint |
| company_id | bigint |
| name       | string |

Examples:

* Rent
* Salary
* Utilities
* Maintenance

---

# expenses

| Field        | Type    |
| ------------ | ------- |
| id           | bigint  |
| company_id   | bigint  |
| branch_id    | bigint  |
| category_id  | bigint  |
| title        | string  |
| amount       | decimal |
| notes        | text    |
| expense_date | date    |
| created_by   | user_id |

---

# notifications

Stores customer notifications.

| Field       | Type      |
| ----------- | --------- |
| id          | bigint    |
| customer_id | bigint    |
| bill_id     | bigint    |
| title       | string    |
| message     | text      |
| is_read     | boolean   |
| created_at  | timestamp |

Examples:

* New bill created
* Order ready
* Order delivered

---

# subscription_packages

| Field         | Type    |
| ------------- | ------- |
| id            | bigint  |
| name          | string  |
| monthly_price | decimal |
| yearly_price  | decimal |
| max_branches  | integer |
| max_users     | integer |
| features      | json    |

---

# otp_verifications

Stores WhatsApp OTP verification requests.

| Field       | Type               |
| ----------- | ------------------ |
| id          | bigint             |
| phone       | string             |
| otp_code    | string             |
| expires_at  | timestamp          |
| verified_at | timestamp nullable |

---

# Future Tables

Planned for future versions:

* loyalty_points
* customer_reviews
* invoices_pdf
* payments
* integrations
* whatsapp_templates
* audit_logs
