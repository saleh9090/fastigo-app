# Fastigo Requirements

## 1. User Roles

Fastigo will have these main user roles:

- Platform Admin
- Company Manager
- Branch Employee
- Public Customer

## 2. Platform Admin Requirements

The Platform Admin can:

- Login to admin panel
- Manage company details
- Manage subscription packages
- View general statistics
- Manage website content
- Manage application settings
- Suspend or activate companies

The Platform Admin does not create or manage daily business records such as bills, bill items, items/products, item categories, expenses, or expense categories. Those records are managed by Company Managers and Branch Employees in the `fastigo_business` mobile app.

## 3. Business App Requirements (fastigo_business)

The `/mobile/fastigo_business` Flutter app is used by Company Managers and Branch Employees depending on each user's access level.

All daily shop operations are handled in `fastigo_business`, including bills, bill items, items/products, item categories, expenses, and expense categories.

### Company Manager Requirements

The Company Manager can:

- Login to business application
- View dashboard
- Manage company profile
- Manage branches
- Add employees
- Manage employee permissions
- Create bills
- View all bills
- Update bill status
- Manage item categories
- Manage items
- Manage expenses
- Manage expense categories
- View sales reports
- View expense reports
- View net profit
- View charts
- View subscription information
- View and manage branch sales, branch expenses, and combined company reports depending on user level

### Branch Employee Requirements

The Branch Employee can:

- Login to business application
- Create bills
- Add customer mobile number
- Add customer name, optional
- Add bill items
- Update bill status
- Mark bill as In Process
- Mark bill as Ready
- Mark bill as Delivered
- View bills depending on permission

## 4. Public Customer App Requirements (fastigo)

The `/mobile/fastigo` Flutter app is used by public customers.

The Public Customer can:

- Login using mobile number
- Receive WhatsApp OTP
- Verify login
- View all bills connected to his mobile number
- View bill status
- Receive notification when a new bill is created
- Receive notification when bill status changes
- View company name for each bill
- View branch name for each bill
- View bill date and amount
- View customer-facing subscription or membership information when enabled

## 5. Customer Requirements

Each customer should include:

- Company ID
- Mobile number
- Customer name, optional
- Created date
- Updated date

Customers are created automatically when a company creates a bill using a customer mobile number.

## 6. Bill Requirements

Each bill should include:

- Bill number
- Company ID
- Branch ID
- Customer ID
- Customer mobile number
- Customer name, optional
- Bill items
- Total amount
- Paid amount
- Remaining amount
- Payment status
- Payment method
- Bill status
- Created date
- Updated date

## 7. Bill Statuses

The first version will use:

- In Process
- Ready
- Delivered

Future statuses may include:

- Cancelled
- Waiting Payment
- Refunded

## 8. Payment Statuses

Payment status can be:

- Unpaid
- Partially Paid
- Paid

## 9. Payment Methods

Payment method can be:

- Cash
- Card
- Bank Transfer
- Mixed

## 10. Item Requirements

Each item should include:

- Item name
- Category
- Item type: Service or Product
- Price
- Active or inactive status

The item type controls whether the item is treated as a service or a product.

## 11. Bill Item Requirements

Each bill item should include:

- Bill ID
- Item ID
- Item name
- Item type
- Price
- Quantity
- Total

## 12. Expense Requirements

Each expense should include:

- Expense title
- Expense category
- Amount
- Date
- Notes
- Company ID
- Branch ID
- Created by user

## 13. Notification Requirements

The system should send notifications when:

- A company creates a new bill using a customer mobile number
- A company changes bill status to Ready
- A company changes bill status to Delivered

Notification channels:

- Mobile app notification
- WhatsApp message, optional in future

WhatsApp is required for OTP only in the MVP.

## 14. SaaS Requirements

The system should support:

- Multiple companies
- Multiple branches under one company
- Separate data for each company
- Subscription packages
- Subscription expiry
- Company suspension if subscription is inactive
- Role-based permissions

Each subscription package should include:

- Package name
- Monthly price
- Yearly price
- Maximum branches
- Maximum employees
- Active or inactive status

Subscription package management belongs to the Platform Admin in `/admin`. Companies may be assigned to one package, and an inactive company or expired subscription must be blocked from creating new bills through the business API.

## 15. Reports Requirements

The business application should show:

- Total sales
- Total expenses
- Net profit
- Sales by date
- Expenses by date
- Sales by branch
- Expenses by branch
- Sales by employee
- Bills by status
- Top services
- Top products
- Charts for sales and expenses

Report API data must be isolated by company. Company Managers can view all branches for their company. Branch Employees can view only report data for their assigned branch.

## 16. MVP Requirements

The first MVP should include:

- Platform admin panel
- Add and manage companies
- Add and manage branches
- Manage staff access levels in the business app
- Business app login
- Create bills
- Update bill status
- Customer login by mobile number
- WhatsApp OTP verification
- Customer bill list
- Customer bill status tracking
- Basic mobile app notifications
- Basic sales and expense reports
- Basic subscription control
