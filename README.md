# Task Management System

A simple web app for creating, assigning, and tracking tasks — with separate views for Admins and regular Users.

This guide is written so that even if you've never coded before, you can get the app running on your own computer.

## What You'll Need First

Before starting, make sure these programs are installed on your computer:

1. **XAMPP** (includes PHP) — [Download here](https://www.apachefriends.org/)
2. **Composer** (installs the app's PHP building blocks) — [Download here](https://getcomposer.org/download/)
3. **Node.js** (installs the app's frontend building blocks) — [Download here](https://nodejs.org/) (choose the LTS version)

If you already see this project inside your `xampp/htdocs` folder, XAMPP is already set up correctly.

## One-Time Setup

Open a terminal (Command Prompt, PowerShell, or Git Bash) in this project folder and run each command below, one at a time.

### 1. Install the app's building blocks

```
composer install
npm install
```

This downloads everything the app needs to run. It may take a few minutes — that's normal.

### 2. Create your settings file

```
copy .env.example .env
```

This creates a `.env` file, which holds the app's configuration (like its name and database settings). You don't need to edit anything in it to get started.

### 3. Generate a security key

```
php artisan key:generate
```

This gives your app a unique secret key used to keep data secure.

### 4. Set up the database

This app uses a lightweight built-in database (SQLite), so there's nothing extra to install.

```
php artisan migrate --seed
```

This creates all the necessary tables and fills them with some starter data, including sample tasks and user accounts.

> **Note:** After seeding, an admin account exists. Check `database/seeders/UserSeeder.php` if you need the exact login email/password used in your setup.

## Running the App

Every time you want to start the app, run:

```
composer run dev
```

This single command starts everything the app needs (the web server, the frontend, and background tasks) at the same time.

Once it's running, open your web browser and go to:

```
http://localhost:8000
```

You should now see the Task Management System login page.

To stop the app, go back to the terminal window and press `Ctrl + C`.

## Logging In

- **Admins** can view, create, edit, and delete every task in the system.
- **Regular Users** can view, create, edit, and delete only their own tasks.

Use the seeded admin account (see note above) to log in as an Admin, or check `database/seeders/UserSeeder.php` and `database/factories` for other sample accounts created during setup.

## Common Issues

**"I don't see my changes on the page"**
Try running `npm run build`, or make sure `composer run dev` is still running in your terminal.

**"Something looks broken after pulling new changes"**
Re-run the setup commands from Step 1 and Step 4 above — new features sometimes add new building blocks or database changes.

**"Port already in use" error**
Something else on your computer is already using that address. Close other running servers, or restart your computer, then try again.

## Running Tests (Optional, for Developers)

If you'd like to verify the app is working correctly behind the scenes:

```
php artisan test --compact
```
