<?php

/**
 * RBAC System Usage Examples
 * 
 * This file demonstrates how to use the Role-Based Access Control system
 * in various scenarios throughout the application.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Auth\Authorization;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Auth\Permission;
use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\UnauthorizedException;
use RentalPlatform\Models\User;

// Start session
Session::start();

echo "=== RBAC System Examples ===\n\n";

// Example 1: Basic Permission Checking
echo "Example 1: Basic Permission Checking\n";
echo "-------------------------------------\n";

$canCustomerCreateProduct = Permission::hasPermission(
    User::ROLE_CUSTOMER,
    Permission::RESOURCE_PRODUCT,
    Permission::ACTION_CREATE
);
echo "Can customer create product? " . ($canCustomerCreateProduct ? "Yes" : "No") . "\n";

$canVendorCreateProduct = Permission::hasPermission(
    User::ROLE_VENDOR,
    Permission::RESOURCE_PRODUCT,
    Permission::ACTION_CREATE
);
echo "Can vendor create product? " . ($canVendorCreateProduct ? "Yes" : "No") . "\n";

$canAdminManageUsers = Permission::hasPermission(
    User::ROLE_ADMINISTRATOR,
    Permission::RESOURCE_USER,
    Permission::ACTION_MANAGE
);
echo "Can admin manage users? " . ($canAdminManageUsers ? "Yes" : "No") . "\n\n";

// Example 2: Protecting a Product Creation Endpoint
echo "Example 2: Protecting a Product Creation Endpoint\n";
echo "--------------------------------------------------\n";

function createProductEndpoint() {
    try {
        // Step 1: Require authentication
        Middleware::requireAuth();
        echo "✓ User is authenticated\n";
        
        // Step 2: Require vendor or admin role
        Middleware::requireVendorOrAdmin();
        echo "✓ User is vendor or admin\n";
        
        // Step 3: Check permission to create products
        Middleware::requirePermission(
            Permission::RESOURCE_PRODUCT,
            Permission::ACTION_CREATE
        );
        echo "✓ User has permission to create products\n";
        
        // Step 4: Create product (simulated)
        echo "✓ Product created successfully\n";
        
        return ['success' => true, 'message' => 'Product created'];
        
    } catch (UnauthorizedException $e) {
        echo "✗ Authorization failed: " . $e->getMessage() . "\n";
        return Middleware::handleUnauthorized($e);
    }
}

// Simulate vendor creating product
$vendor = User::create('vendor1', 'vendor1@example.com', 'password123', User::ROLE_VENDOR);
Session::create($vendor);
echo "Simulating vendor creating product:\n";
$result = createProductEndpoint();
echo "\n";

// Example 3: Protecting an Order Viewing Endpoint
echo "Example 3: Protecting an Order Viewing Endpoint\n";
echo "------------------------------------------------\n";

function viewOrderEndpoint($orderId, $orderCustomerId, $orderVendorId) {
    try {
        // Step 1: Require authentication
        Middleware::requireAuth();
        echo "✓ User is authenticated\n";
        
        // Step 2: Check if user can access this order
        // For vendors, we need their vendor ID
        $currentUserVendorId = null;
        if (Authorization::isVendor()) {
            // In real app, get from database
            $currentUserVendorId = 'vendor-123';
        }
        
        Middleware::requireOrderDataAccess(
            $orderCustomerId,
            $orderVendorId,
            $currentUserVendorId
        );
        echo "✓ User can access this order\n";
        
        // Step 3: Return order data (simulated)
        echo "✓ Order data retrieved\n";
        
        return [
            'success' => true,
            'order' => [
                'id' => $orderId,
                'customer_id' => $orderCustomerId,
                'vendor_id' => $orderVendorId
            ]
        ];
        
    } catch (UnauthorizedException $e) {
        echo "✗ Authorization failed: " . $e->getMessage() . "\n";
        return Middleware::handleUnauthorized($e);
    }
}

// Simulate customer viewing their own order
$customer = User::create('customer1', 'customer1@example.com', 'password123', User::ROLE_CUSTOMER);
Session::create($customer);
echo "Simulating customer viewing their own order:\n";
$result = viewOrderEndpoint('order-1', $customer->getId(), 'vendor-123');
echo "\n";

// Example 4: Data Isolation - Customer Cannot Access Other Customer's Order
echo "Example 4: Data Isolation - Customer Cannot Access Other Customer's Order\n";
echo "--------------------------------------------------------------------------\n";

// Customer1 tries to access Customer2's order
echo "Customer1 trying to access Customer2's order:\n";
$result = viewOrderEndpoint('order-2', 'other-customer-id', 'vendor-123');
echo "\n";

// Example 5: Vendor Data Isolation
echo "Example 5: Vendor Data Isolation\n";
echo "---------------------------------\n";

function updateProductEndpoint($productId, $productVendorId) {
    try {
        // Step 1: Require authentication
        Middleware::requireAuth();
        echo "✓ User is authenticated\n";
        
        // Step 2: Check if user can modify this product
        $currentUserVendorId = null;
        if (Authorization::isVendor()) {
            // In real app, get from database
            $currentUserVendorId = 'vendor-123';
        }
        
        Middleware::requireProductModificationAccess(
            $productVendorId,
            $currentUserVendorId
        );
        echo "✓ User can modify this product\n";
        
        // Step 3: Update product (simulated)
        echo "✓ Product updated successfully\n";
        
        return ['success' => true, 'message' => 'Product updated'];
        
    } catch (UnauthorizedException $e) {
        echo "✗ Authorization failed: " . $e->getMessage() . "\n";
        return Middleware::handleUnauthorized($e);
    }
}

// Vendor tries to update their own product
$vendor = User::create('vendor1', 'vendor1@example.com', 'password123', User::ROLE_VENDOR);
Session::create($vendor);
echo "Vendor updating their own product:\n";
$result = updateProductEndpoint('product-1', 'vendor-123');
echo "\n";

// Vendor tries to update another vendor's product
echo "Vendor trying to update another vendor's product:\n";
$result = updateProductEndpoint('product-2', 'other-vendor-id');
echo "\n";

// Example 6: Administrator Access
echo "Example 6: Administrator Access\n";
echo "--------------------------------\n";

function adminViewAllOrdersEndpoint() {
    try {
        // Step 1: Require authentication
        Middleware::requireAuth();
        echo "✓ User is authenticated\n";
        
        // Step 2: Require administrator role
        Middleware::requireAdministrator();
        echo "✓ User is administrator\n";
        
        // Step 3: Check permission to manage orders
        Middleware::requirePermission(
            Permission::RESOURCE_ORDER,
            Permission::ACTION_MANAGE
        );
        echo "✓ User has permission to manage orders\n";
        
        // Step 4: Return all orders (simulated)
        echo "✓ All orders retrieved\n";
        
        return [
            'success' => true,
            'orders' => [
                ['id' => 'order-1', 'customer' => 'customer1', 'vendor' => 'vendor1'],
                ['id' => 'order-2', 'customer' => 'customer2', 'vendor' => 'vendor2'],
            ]
        ];
        
    } catch (UnauthorizedException $e) {
        echo "✗ Authorization failed: " . $e->getMessage() . "\n";
        return Middleware::handleUnauthorized($e);
    }
}

// Admin accessing all orders
$admin = User::create('admin1', 'admin1@example.com', 'password123', User::ROLE_ADMINISTRATOR);
Session::create($admin);
echo "Administrator viewing all orders:\n";
$result = adminViewAllOrdersEndpoint();
echo "\n";

// Example 7: Role-Based UI Rendering
echo "Example 7: Role-Based UI Rendering\n";
echo "-----------------------------------\n";

function renderDashboard() {
    if (!Authorization::isAuthenticated()) {
        echo "Please log in to view dashboard\n";
        return;
    }
    
    echo "Dashboard for: " . Session::getUsername() . "\n";
    echo "Role: " . Session::getRole() . "\n\n";
    
    if (Authorization::isCustomer()) {
        echo "Customer Dashboard:\n";
        echo "- Browse Products\n";
        echo "- View My Orders\n";
        echo "- View My Invoices\n";
        echo "- Upload Documents\n";
    } elseif (Authorization::isVendor()) {
        echo "Vendor Dashboard:\n";
        echo "- Manage My Products\n";
        echo "- View Orders for My Products\n";
        echo "- Approve/Reject Orders\n";
        echo "- View My Invoices\n";
        echo "- View My Reports\n";
    } elseif (Authorization::isAdministrator()) {
        echo "Administrator Dashboard:\n";
        echo "- Manage All Users\n";
        echo "- Manage All Vendors\n";
        echo "- Manage All Products\n";
        echo "- View All Orders\n";
        echo "- Platform Configuration\n";
        echo "- View Audit Logs\n";
        echo "- Platform-Wide Reports\n";
    }
}

echo "Customer Dashboard:\n";
$customer = User::create('customer1', 'customer1@example.com', 'password123', User::ROLE_CUSTOMER);
Session::create($customer);
renderDashboard();
echo "\n";

echo "Vendor Dashboard:\n";
$vendor = User::create('vendor1', 'vendor1@example.com', 'password123', User::ROLE_VENDOR);
Session::create($vendor);
renderDashboard();
echo "\n";

echo "Administrator Dashboard:\n";
$admin = User::create('admin1', 'admin1@example.com', 'password123', User::ROLE_ADMINISTRATOR);
Session::create($admin);
renderDashboard();
echo "\n";

// Example 8: Permission Matrix Query
echo "Example 8: Permission Matrix Query\n";
echo "-----------------------------------\n";

function displayRolePermissions($role) {
    echo "Permissions for role: $role\n";
    $permissions = Permission::getPermissionsForRole($role);
    
    foreach ($permissions as $resource => $actions) {
        echo "  $resource: " . implode(', ', $actions) . "\n";
    }
    echo "\n";
}

displayRolePermissions(User::ROLE_CUSTOMER);
displayRolePermissions(User::ROLE_VENDOR);
displayRolePermissions(User::ROLE_ADMINISTRATOR);

// Clean up
Session::destroy();

echo "=== Examples Complete ===\n";
