# Role-Based Access Control (RBAC) System

## Overview

The RBAC system provides comprehensive authorization and permission management for the multi-vendor rental platform. It enforces role-based access control at the backend level, ensuring that users can only perform actions permitted for their role and can only access data they are authorized to view.

## Architecture

The RBAC system consists of four main components:

### 1. Permission System (`Permission.php`)
Defines the permission matrix that maps roles to resources and actions. This is the single source of truth for what each role can do.

### 2. Authorization Service (`Authorization.php`)
Provides high-level authorization checks and enforcement. Uses the Permission system and Session to determine if the current user is authorized for specific operations.

### 3. Middleware (`Middleware.php`)
Provides convenient middleware functions for protecting routes and endpoints. Wraps Authorization service methods with exception handling.

### 4. UnauthorizedException (`UnauthorizedException.php`)
Custom exception thrown when authorization fails. Returns HTTP 403 Forbidden status code.

## User Roles

The system supports three distinct user roles:

### Customer
- Can browse and search products
- Can create and view their own orders
- Can upload documents for their orders
- Can view their own invoices and reports
- **Cannot** access vendor or admin functions
- **Cannot** access other customers' data

### Vendor
- Can create, read, update, and delete their own products
- Can approve or reject orders for their products
- Can view orders and documents for their products
- Can view their own invoices and reports
- Can manage their vendor profile
- **Cannot** access other vendors' data
- **Cannot** access platform configuration

### Administrator
- Can manage all users, vendors, and products
- Can view all orders, invoices, and documents
- Can configure platform settings
- Can access audit logs
- Can generate platform-wide reports
- **Full access** to all system resources

## Resources and Actions

### Resources
- `user` - User accounts and profiles
- `product` - Rental products and variants
- `order` - Rental orders
- `invoice` - Financial invoices
- `document` - Verification documents
- `vendor` - Vendor profiles and settings
- `category` - Product categories
- `report` - Analytics and reports
- `platform_config` - Platform-wide settings
- `audit_log` - System audit logs

### Actions
- `create` - Create new resources
- `read` - View resources
- `update` - Modify existing resources
- `delete` - Remove resources
- `approve` - Approve orders (vendor-specific)
- `reject` - Reject orders (vendor-specific)
- `refund` - Process refunds
- `manage` - Full management access (admin-specific)

## Usage Examples

### Basic Permission Checking

```php
use RentalPlatform\Auth\Permission;
use RentalPlatform\Models\User;

// Check if a role has permission
$canCreate = Permission::hasPermission(
    User::ROLE_VENDOR,
    Permission::RESOURCE_PRODUCT,
    Permission::ACTION_CREATE
); // Returns true

// Check if customer can create products
$canCreate = Permission::hasPermission(
    User::ROLE_CUSTOMER,
    Permission::RESOURCE_PRODUCT,
    Permission::ACTION_CREATE
); // Returns false
```

### Authorization Service

```php
use RentalPlatform\Auth\Authorization;
use RentalPlatform\Auth\Permission;

// Check authorization for current session user
if (Authorization::authorize(Permission::RESOURCE_PRODUCT, Permission::ACTION_CREATE)) {
    // User is authorized to create products
}

// Require authorization (throws exception if unauthorized)
try {
    Authorization::requireAuthorization(
        Permission::RESOURCE_PRODUCT,
        Permission::ACTION_CREATE
    );
    // Proceed with product creation
} catch (UnauthorizedException $e) {
    // Handle unauthorized access
}

// Check if user can access specific data
if (Authorization::canAccessUserData($targetUserId)) {
    // User can access this user's data
}

// Require authentication
Authorization::requireAuthentication();

// Check role
if (Authorization::isVendor()) {
    // Current user is a vendor
}
```

### Middleware Protection

```php
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Auth\Permission;

// Protect a route - require authentication
try {
    Middleware::requireAuth();
    // User is authenticated, proceed
} catch (UnauthorizedException $e) {
    // User not authenticated
    Middleware::sendUnauthorizedResponse($e);
}

// Require specific role
try {
    Middleware::requireVendor();
    // User is a vendor, proceed
} catch (UnauthorizedException $e) {
    // User is not a vendor
    Middleware::sendUnauthorizedResponse($e);
}

// Require specific permission
try {
    Middleware::requirePermission(
        Permission::RESOURCE_PRODUCT,
        Permission::ACTION_CREATE
    );
    // User has permission, proceed
} catch (UnauthorizedException $e) {
    // User lacks permission
    Middleware::sendUnauthorizedResponse($e);
}

// Require vendor or admin (deny customers)
try {
    Middleware::requireVendorOrAdmin();
    // User is vendor or admin, proceed
} catch (UnauthorizedException $e) {
    // User is customer
    Middleware::sendUnauthorizedResponse($e);
}

// Check data access
try {
    Middleware::requireUserDataAccess($targetUserId);
    // User can access this user's data
} catch (UnauthorizedException $e) {
    // Access denied
    Middleware::sendUnauthorizedResponse($e);
}
```

### Protecting API Endpoints

```php
// Example: Product creation endpoint
function createProduct() {
    try {
        // Require authentication
        Middleware::requireAuth();
        
        // Require vendor or admin role
        Middleware::requireVendorOrAdmin();
        
        // Require permission to create products
        Middleware::requirePermission(
            Permission::RESOURCE_PRODUCT,
            Permission::ACTION_CREATE
        );
        
        // Get current user's vendor ID
        $vendorId = getCurrentUserVendorId();
        
        // Create product
        $product = createProductInDatabase($_POST, $vendorId);
        
        return ['success' => true, 'product' => $product];
        
    } catch (UnauthorizedException $e) {
        return Middleware::handleUnauthorized($e);
    }
}

// Example: Order viewing endpoint
function viewOrder($orderId) {
    try {
        // Require authentication
        Middleware::requireAuth();
        
        // Get order details
        $order = getOrderFromDatabase($orderId);
        
        // Check if user can access this order
        $vendorId = getCurrentUserVendorId(); // null if not vendor
        Middleware::requireOrderDataAccess(
            $order['customer_id'],
            $order['vendor_id'],
            $vendorId
        );
        
        return ['success' => true, 'order' => $order];
        
    } catch (UnauthorizedException $e) {
        return Middleware::handleUnauthorized($e);
    }
}

// Example: Product modification endpoint
function updateProduct($productId) {
    try {
        // Require authentication
        Middleware::requireAuth();
        
        // Get product details
        $product = getProductFromDatabase($productId);
        
        // Check if user can modify this product
        $vendorId = getCurrentUserVendorId(); // null if not vendor
        Middleware::requireProductModificationAccess(
            $product['vendor_id'],
            $vendorId
        );
        
        // Update product
        $updated = updateProductInDatabase($productId, $_POST);
        
        return ['success' => true, 'product' => $updated];
        
    } catch (UnauthorizedException $e) {
        return Middleware::handleUnauthorized($e);
    }
}
```

## Data Isolation

The RBAC system enforces strict data isolation:

### Customer Isolation
- Customers can only access their own orders, invoices, and documents
- Customers cannot access other customers' data
- Administrators can access all customer data

### Vendor Isolation
- Vendors can only access their own products, orders, and financial data
- Vendors cannot access other vendors' data
- Administrators can access all vendor data

### Cross-Role Isolation
- Customers cannot access vendor-specific functions
- Vendors cannot access customer-specific data (except for orders involving their products)
- Only administrators have cross-role access

## Permission Matrix

### Customer Permissions
| Resource | Actions |
|----------|---------|
| user | read, update (own profile) |
| product | read |
| order | create, read (own orders) |
| invoice | read (own invoices) |
| document | create, read (own documents) |
| report | read (own reports) |

### Vendor Permissions
| Resource | Actions |
|----------|---------|
| user | read, update (own profile) |
| product | create, read, update, delete (own products) |
| order | read, update, approve, reject (own orders) |
| invoice | read (own invoices) |
| document | read (for own orders) |
| vendor | read, update (own profile) |
| report | read (own reports) |

### Administrator Permissions
| Resource | Actions |
|----------|---------|
| user | create, read, update, delete, manage |
| product | read, update, delete, manage |
| order | read, update, manage |
| invoice | read, manage |
| document | read, delete, manage |
| vendor | create, read, update, delete, manage |
| category | create, read, update, delete, manage |
| report | read, manage |
| platform_config | read, update, manage |
| audit_log | read, manage |

## Security Best Practices

1. **Always check authorization at the backend level** - Never trust frontend checks
2. **Use middleware for route protection** - Consistent enforcement across all endpoints
3. **Check data ownership** - Verify user can access specific resources, not just resource types
4. **Log authorization failures** - Track unauthorized access attempts for security monitoring
5. **Use exceptions for authorization failures** - Consistent error handling
6. **Validate vendor IDs** - Always verify vendor ownership for vendor-specific operations
7. **Session validation** - Authorization checks include session validation and timeout

## Testing

Comprehensive unit tests are provided for all RBAC components:

- `tests/Unit/Auth/PermissionTest.php` - Tests permission matrix and data access rules
- `tests/Unit/Auth/AuthorizationTest.php` - Tests authorization service
- `tests/Unit/Auth/MiddlewareTest.php` - Tests middleware functions

Run tests:
```bash
vendor/bin/phpunit tests/Unit/Auth/PermissionTest.php
vendor/bin/phpunit tests/Unit/Auth/AuthorizationTest.php
vendor/bin/phpunit tests/Unit/Auth/MiddlewareTest.php
```

## Requirements Validation

This RBAC implementation validates the following requirements:

- **Requirement 1.4**: Backend-level role-based permission enforcement
- **Requirement 1.5**: Customers denied access to vendor/admin functions
- **Requirement 1.6**: Vendors denied access to other vendors' data
- **Requirement 21.2**: Role-based access control enforced at backend level
- **Requirement 21.3**: Vendor data isolation
- **Requirement 21.4**: Customer data isolation

## Integration with Existing System

The RBAC system integrates seamlessly with the existing authentication system:

- Uses `Session` class for current user context
- Works with `User` model and role constants
- Compatible with existing `AuthService`
- No changes required to existing authentication code

## Future Enhancements

Potential future improvements:

1. **Dynamic permissions** - Allow runtime permission configuration
2. **Permission inheritance** - Role hierarchies with inherited permissions
3. **Fine-grained permissions** - More specific action types
4. **Permission caching** - Cache permission checks for performance
5. **Audit logging integration** - Automatic logging of all authorization checks
6. **API rate limiting** - Per-role rate limits
7. **Permission delegation** - Allow users to delegate permissions temporarily
