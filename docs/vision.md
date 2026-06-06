# Fastigo Vision

## Project Name

Fastigo

## Domain

fastigo.app

## Project Idea

Fastigo is a SaaS platform for managing bills, payments, order statuses, and customer tracking for shops and small businesses.

The main idea is to connect shops with their customers through the customer’s mobile number. When a shop creates a bill using the customer’s phone number, the customer can see the bill status through the public mobile application.

## Main System Components

Fastigo will consist of:

1. Admin/API application for Fastigo platform owner in `/admin`
2. `fastigo` mobile application for public customers
3. `fastigo_business` mobile application for companies and shops
4. Website for public information and marketing

## 1. Admin/API Application

The admin/API application will be built using Laravel and Filament in `/admin`.

This application is used by the Fastigo platform owner to manage the SaaS system and provide API endpoints for both Flutter mobile apps.

The admin can manage:

* Company details
* Subscription details
* Website content
* Application settings
* General reports

The admin panel is not used to create or manage operational shop records. Bills, bill items, items/products, item categories, expenses, and expense categories are managed from the `fastigo_business` mobile app according to the company user's access level.

The system should support SaaS structure, where each shop or company has its own account and its own separated data.

## 2. fastigo Mobile Application

This Flutter application lives in `/mobile/fastigo` and is for public customers.

Customers can log in using their mobile number. The login confirmation will be done using OTP through WhatsApp.

The customer mobile number will be the main connection between the customer and all bills created by shops.

### fastigo App Features

* Login by mobile number
* OTP verification through WhatsApp
* Receive notification when any shop adds the customer mobile number to a bill
* Receive notification when the bill status changes
* View all bills connected to the customer mobile number
* View the status of each bill
* View bill details and payment status
* View customer-facing subscription or membership information when available

### Bill Statuses

The first version will use these statuses:

* In Process
* Ready
* Delivered

Example:

A laundry shop receives clothes from a customer and creates a bill using the customer’s mobile number.

The customer receives a notification that a new bill has been created.

The bill status starts as:

* In Process

When the shop updates the bill status to:

* Ready

The customer receives another notification.

When the order is completed, the final status becomes:

* Delivered

## 3. fastigo_business Mobile Application

This Flutter application lives in `/mobile/fastigo_business` and is for companies and shops subscribed to Fastigo.

The company or shop can use this application to manage daily operations.
It will be used by Company Managers and Branch Employees depending on each user's access level.

### fastigo_business App Features

* Create bills
* Add customer mobile number to each bill
* Add bill items
* Manage item categories
* Manage payments
* Update bill status
* Manage expenses
* Manage expense categories
* View sales reports
* View expense reports
* View net profit
* View charts for sales and expenses
* View company subscription information
* Separate branch sales
* Separate branch expenses
* Combined company reports
* User permissions by branch

Company Managers can view and manage all branches allowed for their company account.
Branch Employees can access only the branches and actions allowed by their user level.

## 4. Website

Fastigo will also have a public website.

The website will be used for:

* Introducing Fastigo
* Showing features
* Showing subscription packages
* Contact form
* Marketing content
* Customer support information

Website content should be manageable from the admin panel.

## MVP Goal

The goal of the first version is to build a simple working platform with:

* Admin panel for managing shops and subscriptions
* `fastigo` app with mobile login and bill tracking
* `fastigo_business` app for creating bills and updating statuses
* Basic notifications
* Basic sales and expense reports
