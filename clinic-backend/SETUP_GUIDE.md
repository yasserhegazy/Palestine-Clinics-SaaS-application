# Palestine Clinics SaaS - Backend Setup Guide

This guide will help you set up and test the Palestine Clinics SaaS backend API for frontend development.

## Prerequisites

Before starting, ensure you have the following installed on your system:
- **PHP 8.1 or higher**
- **Composer** (PHP dependency manager)
- **MySQL** or **MariaDB**
- **Git**

## Setup Instructions

### 1. Clone the Project

```bash
git clone https://github.com/yasserhegazy/Palestine-Clinics-SaaS-application
```

### 2. Navigate to Backend Directory

```bash
cd Palestine-Clinics-SaaS-application/clinic-backend
```

### 3. Install PHP Dependencies

If you don't have Composer installed, download it from [getcomposer.org](https://getcomposer.org/), then run:

```bash
composer install
```

### 4. Environment Configuration

Copy the environment example file and configure it:

```bash
# Windows (PowerShell/CMD)
copy .env.example .env

# macOS/Linux
cp .env.example .env
```

### 5. Configure Database

Edit the `.env` file and update the database configuration:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=palestine_clinics_saas
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 6. Generate Application Key

```bash
php artisan key:generate
```


### 7. Run Database Migrations

```bash
php artisan migrate
```

### 9. Seed Test Data (Optional but Recommended)

Create test users for all roles:

```bash
php artisan db:seed
```

### 10. Start the Development Server

```bash
php artisan serve
```

