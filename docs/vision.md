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

1. Admin Panel for Fastigo platform owner
2. Public Mobile Application for customers
3. Shop Mobile Application for businesses
4. Website for public information and marketing

## 1. Admin Panel

The admin panel will be built using Laravel.

This panel is used by the Fastigo platform owner to manage the SaaS system.

The admin can manage:

* Shops / companies
* Commercial registration number
* Responsible person details
* Contact details
* Business type
* Subscription package
* Subscription start date
* Subscription end date
* Subscription status
* Branches
* Website content
* Application settings
* General reports

The system should support SaaS structure, where each shop or company has its own account and its own separated data.

## 2. Public Mobile Application

This application is for public customers.

Customers can log in using their mobile number. The login confirmation will be done using OTP through WhatsApp.

The customer mobile number will be the main connection between the customer and all bills created by shops.

### Public App Features

* Login by mobile number
* OTP verification through WhatsApp
* Receive notification when any shop adds the customer mobile number to a bill
* Receive notification when the bill status changes
* View all bills connected to the customer mobile number
* View the status of each bill

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

## 3. Shop Mobile Application

This application is for shops and businesses subscribed to Fastigo.

The shop can use this application to manage daily operations.

### Shop App Features

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

## 4. Branch Management

In the future, Fastigo should support companies with more than one branch.

If the admin connects multiple branches to the same company, the company can view and manage all branches under one account.

Branch support can include:

* Separate branch sales
* Separate branch expenses
* Combined company reports
* User permissions by branch

## 5. Website

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
* Public customer app with mobile login and bill tracking
* Shop app for creating bills and updating statuses
* Basic notifications
* Basic sales and expense reports
