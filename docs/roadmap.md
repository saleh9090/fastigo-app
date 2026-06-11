# Fastigo Roadmap V2

## Phase 0 — Planning & Documentation
- Project Structure
- AGENTS.md
- Database Design
- API Standards
- User Roles Definition
- Screen Flow
- MVP Definition

---

## Phase 1 — SaaS Core Foundation
### Database
- subscription_packages
- companies
- company_subscriptions
- branches
- users
- customers
- categories
- items
- bills
- bill_items
- bill_status_histories
- expense_categories
- expenses
- notifications
- otp_verifications

### Authentication
- Platform Admin (Email + Password)
- Company Users (Mobile + Password)
- Customer (Mobile + WhatsApp OTP)

### Deliverable
- Database Complete
- Authentication Complete
- Multi-Tenant Ready

---

## Phase 2 — Platform Admin Panel
### Modules
- Dashboard
- Companies
- Subscription Packages
- System Settings

The platform admin panel must not include management screens for bills, bill items, services/products, product or item categories, expenses, or expense categories. Those modules belong to `fastigo_business`.

---

## Phase 3 — Company Management
### Features
- Manage Branches
- Manage Users
- View Company Reports

Company operational management is exposed through the Laravel API and the `fastigo_business` mobile app, not through Filament admin resources.

---

## Phase 4 — Fastigo Business App
### Features
- Create Bill
- Add Customer
- Add Services
- Add Products
- Print Receipt
- Update Status
- Manage Expenses
- Reports

---

## Phase 5 — Customer App
### Features
- Mobile Login
- WhatsApp OTP
- View Bills
- Bill Tracking
- Notifications
- Profile

---

## Phase 6 — Notifications Engine
- In-App Notifications
- Push Notifications
- WhatsApp Notifications

---

## Phase 7 — Reports & Analytics
- Sales Reports
- Expense Reports
- Profit Reports
- Branch Reports
- Customer Reports

---

## Phase 8 — Website
- Home
- Features
- Pricing
- Contact
- FAQ

---

## Phase 9 — Commercial Launch
- Security Audit
- Load Testing
- Backup Strategy
- Monitoring
- Error Tracking

---

## Phase 10 — Future Expansion
- Loyalty Points
- Online Payments
- Accounting Integration
- White Label
- AI Features
