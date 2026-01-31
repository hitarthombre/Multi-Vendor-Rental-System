# Multi-Vendor Rental Platform - Setup Guide

This guide will help you set up the Multi-Vendor Rental Platform on XAMPP.

## Prerequisites

- XAMPP installed with Apache and MySQL
- PHP 7.4 or higher
- Composer (optional, for dependency management)

## Quick Start

Follow these steps to get the platform running:

### Step 1: Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** (ensure it's running on port 8081)
3. Start **MySQL**

### Step 2: Create Database

Run the database setup script:

```bash
php setup-database.php
```

This will:
- Connect to MySQL
- Create the `rental_platform` database
- Set proper charset and collation

Expected output:
```
Connected to MySQL server.
Database 'rental_platform' created or already exists.
Selected database 'rental_platform'.

Database setup complete!
You can now run migrations using: php migrate.php
```

### Step 3: Run Migrations

Execute the database migrations to create all tables:

```bash
php migrate.php
```

Expected output:
```
Running database migrations...
--------------------------------------------------------------------------------
Executed migration: 2024_01_01_000001_create_initial_schema.sql

Successfully executed 1 migration(s).
--------------------------------------------------------------------------------
Migration complete!
```

### Step 4: Verify Setup

Test the database connection:

```bash
php test-connection.php
```

This will display:
- Database configuration
- Connection status
- MySQL version
- List of created tables

### Step 5: Check Migration Status

View the status of all migrations:

```bash
php migrate.php status
```

Expected output:
```
Migration Status:
--------------------------------------------------------------------------------
[✓] Executed  2024_01_01_000001_create_initial_schema.sql
--------------------------------------------------------------------------------
```

## Directory Structure

```
rental-platform/
├── config/
│   └── database.php          # Database configuration
├── database/
│   ├── migrations/           # SQL migration files
│   └── README.md            # Database documentation
├── public/
│   ├── .htaccess            # Apache configuration
│   └── index.php            # Application entry point
├── src/
│   └── Database/
│       ├── Connection.php   # Database connection manager
│       └── Migration.php    # Migration system
├── composer.json            # PHP dependencies
├── migrate.php              # Migration runner
├── setup-database.php       # Database setup script
├── test-connection.php      # Connection test script
└── SETUP.md                 # This file
```

## Configuration

### Database Configuration

Edit `config/database.php` if you need to change database settings:

```php
return [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'rental_platform',
    'username' => 'root',
    'password' => '',  // Default XAMPP password is empty
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
```

### Apache Configuration

The platform is configured to run on port 8081. If you need to change this:

1. Edit XAMPP Apache configuration
2. Update the port in your virtual host configuration
3. Restart Apache

## Accessing the Platform

### Application
- **URL**: http://localhost:8081/
- **Entry Point**: `public/index.php`

### Database Management
- **phpMyAdmin**: http://localhost:8081/phpmyadmin
- **Database**: rental_platform
- **Username**: root
- **Password**: (empty)

## Database Schema

The initial migration creates the following tables:

### Core Tables
- `users` - User accounts (Customer, Vendor, Administrator)
- `vendors` - Vendor business information
- `categories` - Product categories
- `products` - Rental products
- `attributes` - Product attributes
- `attribute_values` - Attribute values
- `variants` - Product variants
- `pricing` - Time-based pricing

### Transaction Tables
- `carts` - Shopping carts
- `cart_items` - Cart items
- `rental_periods` - Rental time periods
- `payments` - Payment records (Razorpay)
- `orders` - Rental orders
- `order_items` - Order items
- `inventory_locks` - Time-based inventory locks

### Financial Tables
- `invoices` - Immutable invoices
- `invoice_line_items` - Invoice line items
- `refunds` - Refund records

### Supporting Tables
- `documents` - Verification documents
- `audit_logs` - Audit trail
- `notifications` - Email notifications
- `migrations` - Migration tracking

## Troubleshooting

### Connection Failed

**Error**: `Connection failed: SQLSTATE[HY000] [1049] Unknown database`

**Solution**:
```bash
php setup-database.php
```

### MySQL Not Running

**Error**: `Connection failed: SQLSTATE[HY000] [2002] No connection could be made`

**Solution**:
1. Open XAMPP Control Panel
2. Start MySQL service
3. Retry the connection

### Port Already in Use

**Error**: Apache won't start on port 8081

**Solution**:
1. Check what's using port 8081: `netstat -ano | findstr :8081`
2. Either stop that service or change Apache port in XAMPP config
3. Restart Apache

### Permission Denied

**Error**: `Access denied for user 'root'@'localhost'`

**Solution**:
1. Check MySQL credentials in `config/database.php`
2. Default XAMPP password is empty
3. If you changed the password, update the config

### Migration Already Executed

**Error**: `Table 'users' already exists`

**Solution**:
This is normal if migrations have already run. Check status:
```bash
php migrate.php status
```

## Next Steps

After successful setup:

1. **Verify Installation**
   ```bash
   php test-connection.php
   ```

2. **Review Database Schema**
   - Open phpMyAdmin: http://localhost:8081/phpmyadmin
   - Select `rental_platform` database
   - Review table structure

3. **Start Development**
   - Implement authentication module (Task 2)
   - Create repository classes
   - Build API endpoints

4. **Run Tests** (when implemented)
   ```bash
   composer test
   ```

## Development Workflow

### Adding New Migrations

1. Create a new migration file:
   ```
   database/migrations/YYYY_MM_DD_HHMMSS_description.sql
   ```

2. Write SQL statements

3. Run migration:
   ```bash
   php migrate.php
   ```

### Database Backup

Create a backup before major changes:

```bash
# Windows (XAMPP)
cd C:\xampp\mysql\bin
mysqldump -u root rental_platform > backup.sql

# Restore
mysql -u root rental_platform < backup.sql
```

### Reset Database

To start fresh:

1. Drop database in phpMyAdmin or:
   ```sql
   DROP DATABASE rental_platform;
   ```

2. Run setup again:
   ```bash
   php setup-database.php
   php migrate.php
   ```

## Environment Configuration

For production deployment, consider:

1. **Use Environment Variables**
   - Store credentials in `.env` file
   - Never commit credentials to version control

2. **Enable Error Logging**
   - Disable `display_errors` in production
   - Log errors to file

3. **Secure Database**
   - Use strong passwords
   - Create dedicated database user with limited permissions
   - Enable SSL for database connections

4. **Optimize Performance**
   - Enable query caching
   - Add indexes for frequently queried columns
   - Use connection pooling

## Support

For issues or questions:

- Review `database/README.md` for detailed database documentation
- Check `.kiro/specs/multi-vendor-rental-platform/` for requirements and design
- Verify XAMPP services are running
- Check PHP error logs in XAMPP

## Security Notes

⚠️ **Important Security Considerations**:

1. Default XAMPP configuration is for development only
2. Change default MySQL root password in production
3. Create dedicated database user with minimal permissions
4. Never expose phpMyAdmin to public internet
5. Use HTTPS in production
6. Enable firewall rules to restrict database access

## License

Multi-Vendor Rental Platform - Internal Project
