# Bug Fix: Wishlist and Migration System

## Issues Fixed

### 1. Wishlist API JSON Parsing Error
**Error:** `SyntaxError: Unexpected token '<', "<br /> <b>"... is not valid JSON`

**Root Cause:** The `public/api/wishlist.php` file was using `require_once` with relative paths instead of the Composer autoloader, causing PHP errors to be returned as HTML instead of JSON.

**Fix:** Updated `public/api/wishlist.php` to use the autoloader:
```php
require_once __DIR__ . '/../../vendor/autoload.php';
```

### 2. Wishlist Page Fatal Error
**Error:** `Fatal error: Uncaught Error: Class "RentalPlatform\Database\Connection" not found`

**Root Cause:** The `public/wishlist.php` file was using `require_once` with relative paths instead of the Composer autoloader.

**Fix:** Updated `public/wishlist.php` to use the autoloader:
```php
require_once __DIR__ . '/../vendor/autoload.php';
```

### 3. Missing Wishlists Table
**Issue:** The wishlists table didn't exist in the database.

**Fix:** Created migration file `database/migrations/021_create_wishlists_table.sql` with the following schema:
```sql
CREATE TABLE IF NOT EXISTS wishlists (
    id VARCHAR(36) PRIMARY KEY,
    customer_id VARCHAR(36) NOT NULL,
    product_id VARCHAR(36) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_customer_product (customer_id, product_id),
    INDEX idx_customer (customer_id),
    INDEX idx_product (product_id),
    INDEX idx_created_at (created_at)
);
```

### 4. Migration System Not Finding Migration Files
**Issue:** The Migration class only supported date-time pattern filenames (`YYYY_MM_DD_HHMMSS_*.sql`) but the project uses simple numeric patterns (`001_*.sql`, `002_*.sql`, etc.).

**Fix:** Updated `src/Database/Migration.php` to support both patterns:
```php
// Support both date-time pattern and simple numeric pattern
if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.*\.sql$/', $file) || 
    preg_match('/^\d{3}_.*\.sql$/', $file)) {
    $migrations[] = $file;
}
```

### 5. Incorrect Links in Wishlist Page
**Issue:** Links in wishlist.php were pointing to incorrect paths.

**Fix:** Updated all links to use correct paths:
- `products.php` → `customer/products.php`
- `product-details.php` → `customer/product-details.php`
- Added link to `index.php` for home

## Files Modified

1. `public/api/wishlist.php` - Fixed autoloader
2. `public/wishlist.php` - Fixed autoloader and links
3. `src/Database/Migration.php` - Added support for numeric migration pattern
4. `database/migrations/021_create_wishlists_table.sql` - Created (new file)

## Migration Execution

All 21 migrations were successfully executed:
- 001 through 020: Existing tables
- 021: New wishlists table

## Testing Recommendations

1. Test wishlist functionality:
   - Add products to wishlist from products page
   - View wishlist page
   - Remove items from wishlist
   - Clear entire wishlist

2. Test wishlist API endpoints:
   - `POST /api/wishlist.php?action=add&product_id=X`
   - `POST /api/wishlist.php?action=remove&product_id=X`
   - `GET /api/wishlist.php?action=check&product_id=X`
   - `GET /api/wishlist.php?action=count`

3. Verify database:
   - Check that wishlists table exists
   - Check that migrations table tracks all 21 migrations

## Notes

- All API files should use the Composer autoloader (`require_once __DIR__ . '/../../vendor/autoload.php'`)
- All public pages should use the Composer autoloader (`require_once __DIR__ . '/../vendor/autoload.php'`)
- Migration files can use either date-time pattern or simple numeric pattern (001_, 002_, etc.)
- The wishlists table uses a composite unique key to prevent duplicate entries


## Additional Fix: Foreign Key Constraint Error

### Issue 3: Wishlist API 500 Error
**Error:** `Failed to load resource: the server responded with a status of 500 (Internal Server Error)`

**Root Cause:** The wishlist API was using a hardcoded customer ID (`demo-customer-123`) that didn't exist in the database, causing a foreign key constraint violation:
```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: 
a foreign key constraint fails (`rental_platform`.`wishlists`, CONSTRAINT `wishlists_ibfk_1` 
FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE)
```

**Fix:** Updated both `public/api/wishlist.php` and `public/wishlist.php` to:
1. Use session authentication to get the logged-in user's ID
2. Fall back to the first customer in the database for demo purposes
3. Return proper error messages if no customer can be found

```php
// Start session
Session::start();

// For demo purposes, if no user is logged in, use the first customer in the database
$customerId = null;
if (Session::isAuthenticated()) {
    $customerId = Session::getUserId();
} else {
    // Get first customer from database for demo
    $db = \RentalPlatform\Database\Connection::getInstance();
    $stmt = $db->query("SELECT id FROM users WHERE role = 'Customer' LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $customerId = $row['id'];
    }
}
```

## Production Recommendations

- In production, authentication should be required for all wishlist operations
- Remove the fallback to first customer logic
- Implement proper session management and user authentication
- Add rate limiting to prevent abuse
- Consider adding CSRF protection for state-changing operations
