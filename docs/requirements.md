# Fastigo Requirements

## 1. User Roles

Fastigo will have these main user roles:

1. Platform Admin
2. Shop Owner
3. Shop Employee
4. Public Customer

---

## 2. Platform Admin Requirements

The Platform Admin can:

* Login to admin panel
* Add, edit, disable shops
* Manage shop/company details
* Manage commercial registration number
* Manage responsible person details
* Manage contact details
* Manage subscription packages
* Set subscription start date
* Set subscription end date
* Activate or suspend a shop
* Manage branches
* View general statistics
* Manage website content
* Manage application settings

---

## 3. Shop Owner Requirements

The Shop Owner can:

* Login to shop application
* View dashboard
* Manage shop profile
* Manage branches
* Add employees
* Manage employee permissions
* Create bills
* View all bills
* Update bill status
* Manage item categories
* Manage items
* Manage expenses
* Manage expense categories
* View sales reports
* View expense reports
* View net profit
* View charts

---

## 4. Shop Employee Requirements

The Shop Employee can:

* Login to shop application
* Create bills
* Add customer mobile number
* Add bill items
* Update bill status
* Mark bill as In Process
* Mark bill as Ready
* Mark bill as Delivered
* View bills depending on permission

---

## 5. Public Customer Requirements

The Public Customer can:

* Login using mobile number
* Receive WhatsApp OTP
* Verify login
* View all bills connected to his mobile number
* View bill status
* Receive notification when a new bill is created
* Receive notification when bill status changes
* View shop name for each bill
* View bill date and amount

---

## 6. Bill Requirements

Each bill should include:

* Bill number
* Shop ID
* Branch ID
* Customer mobile number
* Customer name, optional
* Bill items
* Total amount
* Paid amount
* Remaining amount
* Payment status
* Bill status
* Created date
* Updated date

---

## 7. Bill Statuses

The first version will use:

* In Process
* Ready
* Delivered

Future statuses may include:

* Cancelled
* Waiting Payment
* Refunded

---

## 8. Payment Statuses

Payment status can be:

* Unpaid
* Partially Paid
* Paid

---

## 9. Item Requirements

Each item should include:

* Item name
* Category
* Price
* Quantity
* Total
* Active or inactive status

---

## 10. Expense Requirements

Each expense should include:

* Expense title
* Expense category
* Amount
* Date
* Notes
* Branch ID
* Created by user

---

## 11. Notification Requirements

The system should send notifications when:

* A shop creates a new bill using a customer mobile number
* A shop changes bill status to Ready
* A shop changes bill status to Delivered

Notification channels:

* Mobile app notification
* WhatsApp message, optional in future

---

## 12. SaaS Requirements

The system should support:

* Multiple shops
* Multiple branches under one company
* Separate data for each shop
* Subscription packages
* Subscription expiry
* Shop suspension if subscription is inactive
* Role-based permissions

---

## 13. Reports Requirements

The shop application should show:

* Total sales
* Total expenses
* Net profit
* Sales by date
* Expenses by date
* Sales by branch
* Expenses by branch
* Charts for sales and expenses

---

## 14. MVP Requirements

The first MVP should include:

* Platform admin panel
* Add and manage shops
* Add and manage branches
* Shop login
* Create bills
* Update bill status
* Customer login by mobile number
* WhatsApp OTP verification
* Customer bill list
* Customer bill status tracking
* Basic notifications
* Basic sales and expense reports
