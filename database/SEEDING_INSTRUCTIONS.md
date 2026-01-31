# Database Seeding Instructions

This guide explains how to populate your database with demo data for testing the Multi-Vendor Rental Platform.

## Prerequisites

1. Database must be set up and migrations must be run
2. PHP CLI must be available
3. Composer dependencies must be installed

## Running the Seeder

### Option 1: Command Line (Recommended)

Open your terminal/command prompt and run:

```bash
php database/seed-demo-data.php
```

### Option 2: Via Browser

Navigate to:
```
http://localhost:8081/Multi-Vendor-Rental-System/database/seed-demo-data.php
```

## What Gets Created

The seeder creates:

- **5 Vendor Accounts** with complete business profiles:
  - Premium House Rentals (Real Estate)
  - SoundWave Audio Rentals (Music Systems)
  - DriveAway Car Rentals (Vehicles)
  - FurnishPro Rentals (Furniture)
  - TechRent Computer Solutions (Computers)

- **23 Products** across 5 categories with:
  - Detailed descriptions
  - High-quality images from Unsplash
  - Proper categorization

- **2 Customer Accounts** for testing:
  - john_doe
  - jane_smith

- **5 Product Categories**:
  - Real Estate
  - Electronics
  - Vehicles
  - Furniture
  - Computers

## After Seeding

1. Check the generated file: `database/DEMO_CREDENTIALS.md`
2. This file contains all login credentials for vendors and customers
3. All accounts use the password: **password123**

## Login URLs

- **Main Login**: http://localhost:8081/Multi-Vendor-Rental-System/public/login.php
- **Registration**: http://localhost:8081/Multi-Vendor-Rental-System/public/register.php

## Quick Test Accounts

### Vendor Accounts
- **Username**: houserentals | **Password**: password123
- **Username**: soundwave | **Password**: password123
- **Username**: driveaway | **Password**: password123
- **Username**: furnishpro | **Password**: password123
- **Username**: techrent | **Password**: password123

### Customer Accounts
- **Username**: john_doe | **Password**: password123
- **Username**: jane_smith | **Password**: password123

## Troubleshooting

### Error: "Connection failed"
- Check your database configuration in `config/database.php`
- Ensure MySQL/MariaDB is running
- Verify database credentials

### Error: "Table doesn't exist"
- Run migrations first: `php migrate.php`
- Check if all tables are created in phpMyAdmin

### Error: "Class not found"
- Run: `composer install`
- Ensure autoloader is working

### Duplicate Entry Errors
- The seeder can only be run once
- To re-seed, either:
  1. Drop and recreate the database
  2. Delete existing records manually
  3. Modify the seeder to check for existing records

## Re-seeding

To seed again with fresh data:

1. **Drop all data** (via phpMyAdmin or SQL):
   ```sql
   TRUNCATE TABLE products;
   TRUNCATE TABLE vendors;
   TRUNCATE TABLE users;
   TRUNCATE TABLE categories;
   ```

2. **Run seeder again**:
   ```bash
   php database/seed-demo-data.php
   ```

## Notes

- Images are hosted on Unsplash CDN (external links)
- All data is for demonstration purposes only
- Passwords are intentionally simple for testing
- In production, use strong passwords and secure storage

## Support

If you encounter issues:
1. Check the error message in terminal
2. Verify database connection
3. Ensure all migrations are run
4. Check PHP error logs
