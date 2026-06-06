# Fastigo API Specification V2

## Overview

Base URL:

/api

Authentication:
- Laravel Sanctum
- Customer Token
- Shop User Token

---

## Customer APIs

### Authentication

POST /api/customer/send-otp
POST /api/customer/verify-otp
POST /api/customer/logout

### Profile

GET /api/customer/profile
PUT /api/customer/profile

### Bills

GET /api/customer/bills
GET /api/customer/bills/{id}

### Notifications

GET /api/customer/notifications
PUT /api/customer/notifications/{id}/read

---

## Shop APIs

### Authentication

POST /api/shop/login
POST /api/shop/logout

### Profile

GET /api/shop/profile

### Dashboard

GET /api/shop/dashboard

---

### Bills

GET /api/shop/bills
GET /api/shop/bills/{id}
POST /api/shop/bills
PUT /api/shop/bills/{id}
DELETE /api/shop/bills/{id}

### Bill Status

POST /api/shop/bills/{id}/status

Statuses:
- in_process
- ready
- delivered

---

### Customers

GET /api/shop/customers
GET /api/shop/customers/{id}

---

### Categories

GET /api/shop/categories
POST /api/shop/categories
PUT /api/shop/categories/{id}
DELETE /api/shop/categories/{id}

---

### Items

GET /api/shop/items
POST /api/shop/items
PUT /api/shop/items/{id}
DELETE /api/shop/items/{id}

---

### Expenses

GET /api/shop/expenses
POST /api/shop/expenses
PUT /api/shop/expenses/{id}
DELETE /api/shop/expenses/{id}

---

### Expense Categories

GET /api/shop/expense-categories
POST /api/shop/expense-categories
PUT /api/shop/expense-categories/{id}
DELETE /api/shop/expense-categories/{id}

---

### Reports

GET /api/shop/reports/sales
GET /api/shop/reports/expenses
GET /api/shop/reports/profit
GET /api/shop/reports/branches

Report endpoints accept optional `start_date` and `end_date` query parameters in `YYYY-MM-DD` format. Company Managers can view all company branches. Branch Employees can view only their assigned branch data.

All authenticated shop API endpoints must enforce company isolation. Branch Employees must be scoped to their assigned branch for bills, expenses, dashboard data, and reports. Company Managers can access all branches inside their company.

---

### Branches

GET /api/shop/branches
POST /api/shop/branches
PUT /api/shop/branches/{id}

---

### Employees

GET /api/shop/employees
POST /api/shop/employees
PUT /api/shop/employees/{id}

---

### Subscription

GET /api/shop/subscription

---

## Admin APIs

### Dashboard

GET /api/admin/dashboard

### Companies

GET /api/admin/companies
POST /api/admin/companies
PUT /api/admin/companies/{id}

### Packages

GET /api/admin/packages
POST /api/admin/packages
PUT /api/admin/packages/{id}

### Subscription Control

The shop API must block bill creation when the authenticated user's company is suspended or its subscription has expired.

---

## Future APIs

- Loyalty Points
- Online Payments
- Reviews
- WhatsApp Templates
- AI Insights
