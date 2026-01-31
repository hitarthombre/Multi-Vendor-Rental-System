# Database Schema and Migration System

This directory contains the database schema and migration system for the Multi-Vendor Rental Platform.

## Overview

The database uses MySQL and is designed to run in the XAMPP environment. The schema supports:

- Multi-vendor rental management
- Time-based inventory locking
- Payment processing and verification
- Order lifecycle management
- Document management
- Audit logging
- Notifications

## Database Structure

### Core Entities

1. **Users & Authentication**
   - `users` - User accounts with role-based access
   - `vendors` - Vendor business information and branding

2. **Product Catalog**
   - `categories` - Product categorization
   - `products` - Rental products
   - `attributes` - Product attributes (color, size, etc.)
   - `attribute_values` - Possible values for attributes
   - `variants` - Product variants based on attributes
   - `pricing` - Time-based pricing configuration

3. **Shopping & Orders**
   - `carts` - Customer shopping carts
   - `cart_items` - Items in carts
   - `rental_periods` - Time period definitions
   - `orders` - Rental orders
   - `order_items` - Items in orders

4. **Payments & Financials**
   - `payments` - Payment records with Razorpay integration
   - `invoices` - Immutable financial records
   - `invoice_line_items` - Invoice line items
   - `refunds` - Refund processing

5. **Inventory Management**
   - `inventory_locks` - Time-based inventory reservations

6. **Documents & Verification**
   - `documents` - Uploaded verification documents

7. **System**
   - `audit_logs` - Audit trail for sensitive actions
   - `notifications` - Email notification queue

## Setup Instructions

### Prerequisites

- XAMPP installed with Apache and MySQL running
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Initial Setup

1. **Start XAMPP Services**
   ```bash
   # Start Apache on port 8081
   # Start MySQL
   ```

2. **Create Database**
   ```bash
   php setup-database.php
   ```
   
   This script will:
   - Connect to MySQL
   - Create the `rental_platform` database if it doesn't exist
   - Set proper charset and collation

3. **Run Migrations**
   ```bash
   php migrate.php
   ```
   
   This will execute all pending migrations and create the schema.

4. **Verify Setup**
   ```bash
   php migrate.php status
   ```
   
   This shows the status of all migrations.

### Accessing the Database

- **phpMyAdmin**: http://localhost:8081/phpmyadmin
- **Database Name**: rental_platform
- **Username**: root
- **Password**: (empty by default in XAMPP)

## Migration System

### How It Works

The migration system tracks which migrations have been executed using a `migrations` table. Each migration file is named with a timestamp prefix to ensure proper ordering.

### Migration File Format

Migration files are SQL files with the naming convention:
```
YYYY_MM_DD_HHMMSS_description.sql
```

Example: `2024_01_01_000001_create_initial_schema.sql`

### Commands

**Run all pending migrations:**
```bash
php migrate.php
```

**Check migration status:**
```bash
php migrate.php status
```

### Creating New Migrations

1. Create a new SQL file in `database/migrations/` with the proper naming format
2. Write your SQL statements (CREATE TABLE, ALTER TABLE, etc.)
3. Run `php migrate.php` to execute the migration

### Migration Best Practices

- Each migration should be atomic and reversible
- Use transactions for complex migrations
- Test migrations on a copy of production data
- Never modify executed migrations
- Create new migrations for schema changes

## Schema Design Principles

### UUID Primary Keys

All tables use CHAR(36) for UUID primary keys to ensure:
- Global uniqueness across distributed systems
- No sequential ID guessing
- Better security

### Timestamps

All tables include:
- `created_at` - Record creation timestamp
- `updated_at` - Last modification timestamp (where applicable)

### Indexes

Strategic indexes are placed on:
- Foreign keys for join performance
- Frequently queried columns (status, dates)
- Unique constraints (email, username, order numbers)

### Foreign Keys

All foreign keys use:
- `ON DELETE CASCADE` for dependent records
- `ON DELETE SET NULL` for optional references
- Proper referential integrity

### JSON Columns

JSON columns are used for:
- Product images (array of paths)
- Variant attribute values (flexible key-value pairs)
- Audit log old/new values

### Enums

Enums are used for:
- User roles
- Order statuses
- Payment statuses
- Duration units
- Item types

This ensures data integrity at the database level.

## Data Integrity Rules

### Vendor Isolation

- Products belong to exactly one vendor
- Orders are vendor-specific
- Invoices are vendor-specific
- Vendors can only access their own data

### Order Lifecycle

Orders follow a strict lifecycle:
1. Payment_Successful (after payment verification)
2. Pending_Vendor_Approval OR Auto_Approved (based on verification requirement)
3. Active_Rental (after approval)
4. Completed OR Rejected OR Refunded

### Inventory Locking

- Locks created when orders are created
- Locks prevent overlapping rentals
- Locks released on completion, rejection, or refund

### Invoice Immutability

- Invoices are finalized after creation
- Finalized invoices cannot be modified
- Refunds create separate records

## Backup and Maintenance

### Backup Recommendations

```bash
# Backup database
mysqldump -u root rental_platform > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore database
mysql -u root rental_platform < backup_file.sql
```

### Maintenance Tasks

- Regular backups (daily recommended)
- Monitor table sizes and indexes
- Archive old completed orders periodically
- Clean up old notifications
- Review audit logs for security

## Troubleshooting

### Connection Issues

If you get connection errors:
1. Verify MySQL is running in XAMPP
2. Check database credentials in `config/database.php`
3. Ensure database exists: `php setup-database.php`

### Migration Errors

If migrations fail:
1. Check the error message for SQL syntax issues
2. Verify database permissions
3. Check for existing tables with same names
4. Review migration file for errors

### Performance Issues

If queries are slow:
1. Check for missing indexes
2. Analyze slow query log
3. Use EXPLAIN on slow queries
4. Consider adding composite indexes

## Security Considerations

- Never commit database credentials to version control
- Use environment variables for sensitive configuration
- Regularly update MySQL and PHP
- Enable MySQL query logging for audit purposes
- Restrict database user permissions in production
- Use SSL for database connections in production

## Next Steps

After setting up the database:

1. Implement authentication module (Task 2)
2. Create repository classes for data access
3. Implement business logic modules
4. Add property-based tests for data integrity
5. Set up API endpoints

## Support

For issues or questions:
- Review the requirements.md and design.md in `.kiro/specs/multi-vendor-rental-platform/`
- Check the tasks.md for implementation guidance
- Verify XAMPP configuration
