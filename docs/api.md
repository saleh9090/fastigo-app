# Fastigo API Plan

## Customer APIs
POST /api/customer/send-otp
POST /api/customer/verify-otp
GET /api/customer/profile
GET /api/customer/bills
GET /api/customer/bills/{id}
GET /api/customer/notifications

## Shop APIs
POST /api/shop/login
GET /api/shop/dashboard
GET /api/shop/bills
POST /api/shop/bills
PUT /api/shop/bills/{id}
POST /api/shop/bills/{id}/status
GET /api/shop/products
GET /api/shop/customers
GET /api/shop/expenses

## Bill Status Flow
in_process → ready → delivered