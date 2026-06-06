# Fastigo Roadmap

## Phase 0: Project Setup

Goal: Prepare the project structure and documentation.

Tasks:

* Create GitHub repository
* Create project folders
* Create documentation files
* Define vision
* Define requirements
* Define database structure
* Define screens
* Define MVP scope

Status: In Progress

---

## Phase 1: Admin/API Foundation

Goal: Build the main Laravel API and Filament admin panel in `/admin`.

Technology:

* Laravel
* MySQL
* Laravel Sanctum
* Filament Admin Panel

Tasks:

* Install Laravel
* Configure database
* Create authentication system
* Create companies table
* Create branches table
* Create users table
* Create customers table
* Create bills table
* Create bill_items table
* Create categories table
* Create items table
* Create expenses table
* Create notifications table
* Create subscription_packages table
* Create otp_verifications table
* Build API routes
* Build admin panel resources

Deliverable:

* Working API from `/admin`
* Working Filament admin panel from `/admin`

---

## Phase 2: Admin Panel

Goal: Create the Fastigo platform owner dashboard.

Tasks:

* Admin login
* Dashboard statistics
* Manage companies
* Manage branches
* Manage shop users
* Manage subscription packages
* Manage subscription dates
* Activate and suspend companies
* Manage website content
* Manage system settings

Deliverable:

* Platform admin can manage Fastigo SaaS clients

---

## Phase 3: fastigo_business Mobile Application

Goal: Build the Flutter mobile app for companies and shops in `/mobile/fastigo_business`.

Tasks:

* Business user login
* Dashboard
* Create bill
* Add customer mobile number
* Add bill items
* Update bill status
* Manage items
* Manage categories
* Manage expenses
* Manage expense categories
* View sales report
* View expense report
* View net profit
* View charts

Deliverable:

* Company/shop users can manage bills, expenses, reports, and subscription information

---

## Phase 4: fastigo Customer Mobile Application

Goal: Build the public Flutter customer app in `/mobile/fastigo`.

Tasks:

* Login by mobile number
* WhatsApp OTP verification
* View customer bills
* View bill status
* View bill details
* Receive notifications
* View notification history
* View customer-facing subscription or membership information
* Profile and logout

Deliverable:

* Customer can track all bills linked to his mobile number

---

## Phase 5: Notifications

Goal: Notify customers when bill events happen.

Events:

* New bill created
* Bill status changed to Ready
* Bill status changed to Delivered

Channels:

* In-app notification
* Push notification
* WhatsApp notification in future

Deliverable:

* Customer receives bill updates

---

## Phase 6: Reports and Charts

Goal: Give shops useful business insights.

Reports:

* Daily sales
* Monthly sales
* Daily expenses
* Monthly expenses
* Net profit
* Sales by branch
* Expenses by branch

Charts:

* Sales chart
* Expenses chart
* Profit chart

Deliverable:

* Shop owner can understand business performance

---

## Phase 7: Branch Management

Goal: Support companies with multiple branches.

Tasks:

* Connect branches to one company
* Allow users to access specific branches
* Show branch-based reports
* Show combined company reports

Deliverable:

* One company can manage multiple branches

---

## Phase 8: Website

Goal: Create the public marketing website.

Pages:

* Home
* Features
* Pricing
* Contact
* Support

Admin Control:

* Edit homepage content
* Edit features
* Edit pricing
* Edit contact details

Deliverable:

* Public website managed from admin panel

---

## Phase 9: Future Features

Possible future features:

* Online payments
* Customer loyalty points
* Customer reviews
* PDF invoices
* WhatsApp message templates
* Advanced analytics
* Subscription billing automation
* Accounting system integration
* Multi-language support
* White-label shop app

---

## MVP Scope

The first MVP should include:

* Laravel API and Filament admin panel in `/admin`
* `fastigo_business` company/shop app
* `fastigo` customer/public app
* Company management
* Branch management
* Bill creation
* Bill status tracking
* Customer login with mobile number
* WhatsApp OTP
* Basic notifications
* Basic sales and expense reports

---

## Recommended Build Order

1. Admin database and API
2. Filament admin panel
3. `fastigo_business` app
4. `fastigo` app
5. Notifications
6. Reports and charts
7. Website
