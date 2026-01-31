# Database Schema Reference

Quick reference for the Multi-Vendor Rental Platform database schema.

## Schema Statistics

- **Total Tables**: 22
- **Foreign Key Constraints**: 35
- **Indexes**: 45 (excluding primary keys)
- **Database**: rental_platform
- **Charset**: utf8mb4
- **Collation**: utf8mb4_unicode_ci

## Table Overview

### Authentication & Users

#### users
Primary user accounts table supporting three roles: Customer, Vendor, Administrator.

**Key Columns**:
- `id` (CHAR(36), PK) - UUID primary key
- `username` (VARCHAR(255), UNIQUE) - Unique username
- `email` (VARCHAR(255), UNIQUE) - Unique email address
- `password_hash` (VARCHAR(255)) - Bcrypt password hash
- `role` (ENUM) - Customer, Vendor, or Administrator

**Indexes**: username, email, role

#### vendors
Vendor business information and branding.

**Key Columns**:
- `id` (CHAR(36), PK)
- `user_id` (CHAR(36), FK → users.id, UNIQUE)
- `business_name` (VARCHAR(255))
- `legal_name` (VARCHAR(255))
- `tax_id` (VARCHAR(100))
- `logo` (VARCHAR(500)) - File path
- `brand_color` (VARCHAR(7)) - Hex color code
- `status` (ENUM) - Active, Suspended, Pending

**Indexes**: user_id, status

### Product Catalog

#### categories
Hierarchical product categories.

**Key Columns**:
- `id` (CHAR(36), PK)
- `name` (VARCHAR(255))
- `parent_id` (CHAR(36), FK → categories.id, nullable)

#### products
Rental products owned by vendors.

**Key Columns**:
- `id` (CHAR(36), PK)
- `vendor_id` (CHAR(36), FK → vendors.id)
- `name` (VARCHAR(255))
- `description` (TEXT)
- `category_id` (CHAR(36), FK → categories.id)
- `images` (JSON) - Array of image paths
- `verification_required` (BOOLEAN) - Manual approval flag
- `status` (ENUM) - Active, Inactive, Deleted

**Indexes**: vendor_id, category_id, status, verification_required

#### attributes
Product attribute definitions (e.g., Color, Size).

**Key Columns**:
- `id` (CHAR(36), PK)
- `name` (VARCHAR(255))
- `type` (ENUM) - Select, Text, Number

#### attribute_values
Possible values for attributes.

**Key Columns**:
- `id` (CHAR(36), PK)
- `attribute_id` (CHAR(36), FK → attributes.id)
- `value` (VARCHAR(255))

#### variants
Product variants based on attribute combinations.

**Key Columns**:
- `id` (CHAR(36), PK)
- `product_id` (CHAR(36), FK → products.id)
- `sku` (VARCHAR(255), UNIQUE)
- `attribute_values` (JSON) - Map of attribute_id → attribute_value_id
- `quantity` (INT)

**Indexes**: product_id, sku

#### pricing
Time-based pricing configuration.

**Key Columns**:
- `id` (CHAR(36), PK)
- `product_id` (CHAR(36), FK → products.id)
- `variant_id` (CHAR(36), FK → variants.id, nullable)
- `duration_unit` (ENUM) - Hourly, Daily, Weekly, Monthly
- `price_per_unit` (DECIMAL(10,2))
- `minimum_duration` (INT)

**Indexes**: product_id, variant_id, duration_unit

### Shopping & Cart

#### carts
Customer shopping carts.

**Key Columns**:
- `id` (CHAR(36), PK)
- `customer_id` (CHAR(36), FK → users.id)

#### cart_items
Items in shopping carts.

**Key Columns**:
- `id` (CHAR(36), PK)
- `cart_id` (CHAR(36), FK → carts.id)
- `product_id` (CHAR(36), FK → products.id)
- `variant_id` (CHAR(36), FK → variants.id, nullable)
- `rental_period_id` (CHAR(36), FK → rental_periods.id)
- `quantity` (INT)
- `tentative_price` (DECIMAL(10,2))

#### rental_periods
Time period definitions for rentals.

**Key Columns**:
- `id` (CHAR(36), PK)
- `start_datetime` (DATETIME)
- `end_datetime` (DATETIME)
- `duration_value` (INT)
- `duration_unit` (ENUM) - Hourly, Daily, Weekly, Monthly

**Indexes**: start_datetime, end_datetime

### Payments & Orders

#### payments
Payment records with Razorpay integration.

**Key Columns**:
- `id` (CHAR(36), PK)
- `customer_id` (CHAR(36), FK → users.id)
- `razorpay_payment_id` (VARCHAR(255))
- `razorpay_order_id` (VARCHAR(255))
- `razorpay_signature` (VARCHAR(500))
- `amount` (DECIMAL(10,2))
- `currency` (VARCHAR(3))
- `status` (ENUM) - Pending, Verified, Failed
- `verified_at` (TIMESTAMP, nullable)

**Indexes**: customer_id, razorpay_payment_id, razorpay_order_id, status

#### orders
Rental orders (vendor-specific).

**Key Columns**:
- `id` (CHAR(36), PK)
- `order_number` (VARCHAR(50), UNIQUE)
- `customer_id` (CHAR(36), FK → users.id)
- `vendor_id` (CHAR(36), FK → vendors.id)
- `payment_id` (CHAR(36), FK → payments.id)
- `status` (ENUM) - Payment_Successful, Pending_Vendor_Approval, Auto_Approved, Active_Rental, Completed, Rejected, Refunded
- `total_amount` (DECIMAL(10,2))
- `deposit_amount` (DECIMAL(10,2))

**Indexes**: order_number, customer_id, vendor_id, payment_id, status

#### order_items
Items in orders.

**Key Columns**:
- `id` (CHAR(36), PK)
- `order_id` (CHAR(36), FK → orders.id)
- `product_id` (CHAR(36), FK → products.id)
- `variant_id` (CHAR(36), FK → variants.id, nullable)
- `rental_period_id` (CHAR(36), FK → rental_periods.id)
- `quantity` (INT)
- `unit_price` (DECIMAL(10,2))
- `total_price` (DECIMAL(10,2))

### Inventory Management

#### inventory_locks
Time-based inventory reservations.

**Key Columns**:
- `id` (CHAR(36), PK)
- `order_id` (CHAR(36), FK → orders.id)
- `product_id` (CHAR(36), FK → products.id)
- `variant_id` (CHAR(36), FK → variants.id, nullable)
- `rental_period_id` (CHAR(36), FK → rental_periods.id)
- `locked_at` (TIMESTAMP)
- `released_at` (TIMESTAMP, nullable)

**Indexes**: order_id, product_id, variant_id, rental_period_id, released_at

**Purpose**: Prevents overlapping rentals by locking inventory for specific time periods.

### Financial Records

#### invoices
Immutable financial records.

**Key Columns**:
- `id` (CHAR(36), PK)
- `invoice_number` (VARCHAR(50), UNIQUE)
- `order_id` (CHAR(36), FK → orders.id)
- `vendor_id` (CHAR(36), FK → vendors.id)
- `customer_id` (CHAR(36), FK → users.id)
- `subtotal` (DECIMAL(10,2))
- `tax_amount` (DECIMAL(10,2))
- `total_amount` (DECIMAL(10,2))
- `status` (ENUM) - Draft, Finalized
- `finalized_at` (TIMESTAMP, nullable)

**Indexes**: invoice_number, order_id, vendor_id, customer_id, status

#### invoice_line_items
Individual line items in invoices.

**Key Columns**:
- `id` (CHAR(36), PK)
- `invoice_id` (CHAR(36), FK → invoices.id)
- `description` (VARCHAR(500))
- `item_type` (ENUM) - Rental, Deposit, Delivery, Fee, Penalty
- `quantity` (INT)
- `unit_price` (DECIMAL(10,2))
- `total_price` (DECIMAL(10,2))
- `tax_rate` (DECIMAL(5,2))
- `tax_amount` (DECIMAL(10,2))

**Indexes**: invoice_id, item_type

#### refunds
Refund processing records.

**Key Columns**:
- `id` (CHAR(36), PK)
- `order_id` (CHAR(36), FK → orders.id)
- `payment_id` (CHAR(36), FK → payments.id)
- `razorpay_refund_id` (VARCHAR(255))
- `amount` (DECIMAL(10,2))
- `reason` (TEXT)
- `status` (ENUM) - Initiated, In_Progress, Completed, Failed
- `initiated_at` (TIMESTAMP)
- `completed_at` (TIMESTAMP, nullable)

**Indexes**: order_id, payment_id, status

### Documents & Verification

#### documents
Uploaded verification documents.

**Key Columns**:
- `id` (CHAR(36), PK)
- `order_id` (CHAR(36), FK → orders.id)
- `customer_id` (CHAR(36), FK → users.id)
- `document_type` (VARCHAR(100))
- `file_path` (VARCHAR(500))
- `file_size` (INT)
- `mime_type` (VARCHAR(100))
- `uploaded_at` (TIMESTAMP)

**Indexes**: order_id, customer_id

**Supported Formats**: PDF, JPG, PNG

### System Tables

#### audit_logs
Audit trail for sensitive actions.

**Key Columns**:
- `id` (CHAR(36), PK)
- `user_id` (CHAR(36), FK → users.id, nullable)
- `entity_type` (VARCHAR(100))
- `entity_id` (CHAR(36))
- `action` (VARCHAR(100))
- `old_value` (JSON, nullable)
- `new_value` (JSON, nullable)
- `timestamp` (TIMESTAMP)
- `ip_address` (VARCHAR(45))

**Indexes**: user_id, entity_type, entity_id, action, timestamp

#### notifications
Email notification queue.

**Key Columns**:
- `id` (CHAR(36), PK)
- `user_id` (CHAR(36), FK → users.id)
- `event_type` (VARCHAR(100))
- `subject` (VARCHAR(500))
- `body` (TEXT)
- `sent_at` (TIMESTAMP, nullable)
- `status` (ENUM) - Pending, Sent, Failed

**Indexes**: user_id, event_type, status, sent_at

#### migrations
Migration tracking table.

**Key Columns**:
- `id` (INT, AUTO_INCREMENT, PK)
- `migration` (VARCHAR(255), UNIQUE)
- `executed_at` (TIMESTAMP)

## Key Relationships

### One-to-One
- `users` ↔ `vendors` (via user_id)

### One-to-Many
- `vendors` → `products`
- `products` → `variants`
- `products` → `pricing`
- `users` (customer) → `orders`
- `vendors` → `orders`
- `orders` → `order_items`
- `orders` → `inventory_locks`
- `orders` → `invoices`
- `payments` → `orders`

### Many-to-Many (via junction tables)
- `products` ↔ `attributes` (via variants and attribute_values)

## Data Integrity Rules

### Cascading Deletes
- Deleting a user cascades to their orders, carts, and notifications
- Deleting a vendor cascades to their products
- Deleting a product cascades to its variants, pricing, and cart items
- Deleting an order cascades to order items, inventory locks, and documents

### Null Handling
- `variant_id` is nullable (products without variants)
- `parent_id` in categories is nullable (top-level categories)
- `released_at` in inventory_locks is nullable (active locks)
- `verified_at` in payments is nullable (unverified payments)

### Unique Constraints
- `username` and `email` in users
- `user_id` in vendors (one vendor per user)
- `sku` in variants
- `order_number` in orders
- `invoice_number` in invoices

## Enum Values

### User Roles
- Customer
- Vendor
- Administrator

### Order Status
- Payment_Successful
- Pending_Vendor_Approval
- Auto_Approved
- Active_Rental
- Completed
- Rejected
- Refunded

### Payment Status
- Pending
- Verified
- Failed

### Invoice Status
- Draft
- Finalized

### Refund Status
- Initiated
- In_Progress
- Completed
- Failed

### Duration Units
- Hourly
- Daily
- Weekly
- Monthly

### Item Types (Invoice Line Items)
- Rental
- Deposit
- Delivery
- Fee
- Penalty

## Performance Considerations

### Indexed Columns
All foreign keys are indexed for join performance.

Additional indexes on:
- Status columns (orders, payments, invoices)
- Date/time columns (rental periods, timestamps)
- Unique identifiers (order_number, invoice_number)
- Search columns (username, email)

### Query Optimization Tips
1. Use indexes for WHERE clauses
2. Avoid SELECT * in production
3. Use EXPLAIN to analyze query plans
4. Consider composite indexes for multi-column queries
5. Use LIMIT for pagination

## Security Notes

### Sensitive Data
- `password_hash` - Never expose in API responses
- `razorpay_signature` - Backend verification only
- `documents.file_path` - Access control required
- `audit_logs` - Admin access only

### Access Control
- Vendor isolation enforced via vendor_id
- Customer isolation enforced via customer_id
- Role-based permissions at application level

## Maintenance

### Regular Tasks
- Archive old completed orders
- Clean up old notifications
- Review audit logs
- Monitor table sizes
- Optimize indexes

### Backup Strategy
- Daily full backups
- Transaction log backups
- Test restore procedures
- Off-site backup storage
