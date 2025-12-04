# Task 4 PHP - User Management System

## User Management App (Symfony 6/7)
This is my project for user management. It use Symfony 6/7 and have authentication, blocking and deleting users features.

Requirements
PHP version 8.1 or more

Composer

MySQL or PostgreSQL

## How to Install
1. Install Dependencies First you need to install packages with composer:

Bash

composer install
## 2. Database Configuration Open .env file and change DATABASE_URL with your database info.

Фрагмент кода

DATABASE_URL="mysql://root:password@127.0.0.1:3306/task4php?serverVersion=8.0.32&charset=utf8mb4"
## 3. Create Database and Tables Run this commands to make database work:

Bash

php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
Note: The migration will create unique index for email automatically.

## 4. Start the Server To run application use this:

Bash

php -S localhost:8000 -t public
Or if you use Symfony CLI:

Bash

symfony server:start
Features
Auth: Can Login and Registration.

Security:

Blocked users can not login to system.

If user is blocked or deleted, he is logged out immediately in next request.

Email is unique in database (cannot duplicate).

User Management:

List of users with sorting.

Bulk actions: Can Block, Unblock, Delete many users at same time.

Show status and time.