# Task 4 PHP - User Management System

This is a Symfony 6/7 application for user management with authentication, blocking, and deletion capabilities.

## Requirements

*   PHP 8.1 or higher
*   Composer
*   MySQL or PostgreSQL

## Setup Instructions

1.  **Install Dependencies:**
    ```bash
    composer install
    ```

2.  **Configure Database:**
    Edit the `.env` file and set your `DATABASE_URL`.
    ```dotenv
    DATABASE_URL="mysql://root:password@127.0.0.1:3306/task4php?serverVersion=8.0.32&charset=utf8mb4"
    ```

3.  **Create Database and Migrations:**
    ```bash
    php bin/console doctrine:database:create
    php bin/console make:migration
    php bin/console doctrine:migrations:migrate
    ```
    *Note: The migration will automatically create the UNIQUE INDEX on the email column as defined in the User entity.*

4.  **Run the Application:**
    ```bash
    php -S localhost:8000 -t public
    ```
    Or use the Symfony CLI:
    ```bash
    symfony server:start
    ```

## Features

*   **Authentication:** Login and Registration.
*   **Security:**
    *   Blocked users cannot login.
    *   Blocked/Deleted users are immediately logged out on their next request.
    *   Unique email constraint enforced at the database level.
*   **User Management:**
    *   List users with sorting.
    *   Bulk actions: Block, Unblock, Delete.
    *   Status badges and relative timestamps.

## Deployment

To deploy to a provider like Render or Heroku:
1.  Ensure `APP_ENV=prod` in your environment variables.
2.  Run `composer install --no-dev --optimize-autoloader`.
3.  Run migrations on the production database.
