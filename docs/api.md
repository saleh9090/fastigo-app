# Fastigo API Plan

The Laravel API lives in `/admin` and serves both Flutter mobile apps:

- `/mobile/fastigo_business` uses the shop/company APIs.
- `/mobile/fastigo` uses the customer/public APIs.

## Customer APIs
POST /api/customer/send-otp
POST /api/customer/verify-otp
GET /api/customer/profile
GET /api/customer/bills
GET /api/customer/bills/{id}
GET /api/customer/notifications
GET /api/customer/subscriptions

## Shop APIs
POST /api/shop/login
GET /api/shop/profile
GET /api/shop/dashboard
GET /api/shop/bills
POST /api/shop/bills
PUT /api/shop/bills/{id}
POST /api/shop/bills/{id}/status
GET /api/shop/products
GET /api/shop/product-categories
GET /api/shop/customers
GET /api/shop/expenses
POST /api/shop/expenses
GET /api/shop/expense-categories
GET /api/shop/subscription

## Bill Status Flow
in_process → ready → delivered
