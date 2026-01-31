<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Auth\Permission;
use RentalPlatform\Models\User;

/**
 * Permission System Tests
 * 
 * Tests the permission checking system for role-based access control
 */
class PermissionTest extends TestCase
{
    /**
     * Test customer permissions
     */
    public function testCustomerPermissions(): void
    {
        // Customers can read products
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_CUSTOMER, Permission::RESOURCE_PRODUCT, Permission::ACTION_READ)
        );

        // Customers can create orders
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_CUSTOMER, Permission::RESOURCE_ORDER, Permission::ACTION_CREATE)
        );

        // Customers can read their own orders
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_CUSTOMER, Permission::RESOURCE_ORDER, Permission::ACTION_READ)
        );

        // Customers cannot create products
        $this->assertFalse(
            Permission::hasPermission(User::ROLE_CUSTOMER, Permission::RESOURCE_PRODUCT, Permission::ACTION_CREATE)
        );

        // Customers cannot approve orders
        $this->assertFalse(
            Permission::hasPermission(User::ROLE_CUSTOMER, Permission::RESOURCE_ORDER, Permission::ACTION_APPROVE)
        );

        // Customers cannot access vendor resources
        $this->assertFalse(
            Permission::hasPermission(User::ROLE_CUSTOMER, Permission::RESOURCE_VENDOR, Permission::ACTION_READ)
        );

        // Customers cannot access platform config
        $this->assertFalse(
            Permission::hasPermission(User::ROLE_CUSTOMER, Permission::RESOURCE_PLATFORM_CONFIG, Permission::ACTION_READ)
        );
    }

    /**
     * Test vendor permissions
     */
    public function testVendorPermissions(): void
    {
        // Vendors can create products
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_VENDOR, Permission::RESOURCE_PRODUCT, Permission::ACTION_CREATE)
        );

        // Vendors can read products
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_VENDOR, Permission::RESOURCE_PRODUCT, Permission::ACTION_READ)
        );

        // Vendors can update products
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_VENDOR, Permission::RESOURCE_PRODUCT, Permission::ACTION_UPDATE)
        );

        // Vendors can delete products
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_VENDOR, Permission::RESOURCE_PRODUCT, Permission::ACTION_DELETE)
        );

        // Vendors can approve orders
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_VENDOR, Permission::RESOURCE_ORDER, Permission::ACTION_APPROVE)
        );

        // Vendors can reject orders
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_VENDOR, Permission::RESOURCE_ORDER, Permission::ACTION_REJECT)
        );

        // Vendors can read documents
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_VENDOR, Permission::RESOURCE_DOCUMENT, Permission::ACTION_READ)
        );

        // Vendors cannot create orders
        $this->assertFalse(
            Permission::hasPermission(User::ROLE_VENDOR, Permission::RESOURCE_ORDER, Permission::ACTION_CREATE)
        );

        // Vendors cannot access platform config
        $this->assertFalse(
            Permission::hasPermission(User::ROLE_VENDOR, Permission::RESOURCE_PLATFORM_CONFIG, Permission::ACTION_READ)
        );

        // Vendors cannot manage categories
        $this->assertFalse(
            Permission::hasPermission(User::ROLE_VENDOR, Permission::RESOURCE_CATEGORY, Permission::ACTION_MANAGE)
        );
    }

    /**
     * Test administrator permissions
     */
    public function testAdministratorPermissions(): void
    {
        // Administrators can manage users
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_ADMINISTRATOR, Permission::RESOURCE_USER, Permission::ACTION_MANAGE)
        );

        // Administrators can manage products
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_ADMINISTRATOR, Permission::RESOURCE_PRODUCT, Permission::ACTION_MANAGE)
        );

        // Administrators can manage orders
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_ADMINISTRATOR, Permission::RESOURCE_ORDER, Permission::ACTION_MANAGE)
        );

        // Administrators can manage vendors
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_ADMINISTRATOR, Permission::RESOURCE_VENDOR, Permission::ACTION_MANAGE)
        );

        // Administrators can manage categories
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_ADMINISTRATOR, Permission::RESOURCE_CATEGORY, Permission::ACTION_MANAGE)
        );

        // Administrators can access platform config
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_ADMINISTRATOR, Permission::RESOURCE_PLATFORM_CONFIG, Permission::ACTION_READ)
        );

        // Administrators can manage platform config
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_ADMINISTRATOR, Permission::RESOURCE_PLATFORM_CONFIG, Permission::ACTION_MANAGE)
        );

        // Administrators can access audit logs
        $this->assertTrue(
            Permission::hasPermission(User::ROLE_ADMINISTRATOR, Permission::RESOURCE_AUDIT_LOG, Permission::ACTION_READ)
        );
    }

    /**
     * Test invalid role returns false
     */
    public function testInvalidRoleReturnsFalse(): void
    {
        $this->assertFalse(
            Permission::hasPermission('InvalidRole', Permission::RESOURCE_PRODUCT, Permission::ACTION_READ)
        );
    }

    /**
     * Test invalid resource returns false
     */
    public function testInvalidResourceReturnsFalse(): void
    {
        $this->assertFalse(
            Permission::hasPermission(User::ROLE_CUSTOMER, 'invalid_resource', Permission::ACTION_READ)
        );
    }

    /**
     * Test get permissions for role
     */
    public function testGetPermissionsForRole(): void
    {
        $customerPermissions = Permission::getPermissionsForRole(User::ROLE_CUSTOMER);
        $this->assertIsArray($customerPermissions);
        $this->assertArrayHasKey(Permission::RESOURCE_PRODUCT, $customerPermissions);
        $this->assertArrayHasKey(Permission::RESOURCE_ORDER, $customerPermissions);

        $vendorPermissions = Permission::getPermissionsForRole(User::ROLE_VENDOR);
        $this->assertIsArray($vendorPermissions);
        $this->assertArrayHasKey(Permission::RESOURCE_PRODUCT, $vendorPermissions);
        $this->assertArrayHasKey(Permission::RESOURCE_VENDOR, $vendorPermissions);

        $adminPermissions = Permission::getPermissionsForRole(User::ROLE_ADMINISTRATOR);
        $this->assertIsArray($adminPermissions);
        $this->assertArrayHasKey(Permission::RESOURCE_PLATFORM_CONFIG, $adminPermissions);
    }

    /**
     * Test get allowed actions
     */
    public function testGetAllowedActions(): void
    {
        $customerProductActions = Permission::getAllowedActions(User::ROLE_CUSTOMER, Permission::RESOURCE_PRODUCT);
        $this->assertIsArray($customerProductActions);
        $this->assertContains(Permission::ACTION_READ, $customerProductActions);
        $this->assertNotContains(Permission::ACTION_CREATE, $customerProductActions);

        $vendorProductActions = Permission::getAllowedActions(User::ROLE_VENDOR, Permission::RESOURCE_PRODUCT);
        $this->assertIsArray($vendorProductActions);
        $this->assertContains(Permission::ACTION_CREATE, $vendorProductActions);
        $this->assertContains(Permission::ACTION_UPDATE, $vendorProductActions);
        $this->assertContains(Permission::ACTION_DELETE, $vendorProductActions);
    }

    /**
     * Test user can access own data
     */
    public function testUserCanAccessOwnData(): void
    {
        $user = User::create('testuser', 'test@example.com', 'password123', User::ROLE_CUSTOMER);
        
        $this->assertTrue(Permission::canAccessUserData($user, $user->getId()));
    }

    /**
     * Test user cannot access other user data
     */
    public function testUserCannotAccessOtherUserData(): void
    {
        $user1 = User::create('user1', 'user1@example.com', 'password123', User::ROLE_CUSTOMER);
        $user2 = User::create('user2', 'user2@example.com', 'password123', User::ROLE_CUSTOMER);
        
        $this->assertFalse(Permission::canAccessUserData($user1, $user2->getId()));
    }

    /**
     * Test administrator can access any user data
     */
    public function testAdministratorCanAccessAnyUserData(): void
    {
        $admin = User::create('admin', 'admin@example.com', 'password123', User::ROLE_ADMINISTRATOR);
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        
        $this->assertTrue(Permission::canAccessUserData($admin, $customer->getId()));
    }

    /**
     * Test vendor can access own vendor data
     */
    public function testVendorCanAccessOwnVendorData(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        $vendorId = 'vendor-123';
        
        $this->assertTrue(Permission::canAccessVendorData($vendor, $vendorId, $vendorId));
    }

    /**
     * Test vendor cannot access other vendor data
     */
    public function testVendorCannotAccessOtherVendorData(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        $vendorId1 = 'vendor-123';
        $vendorId2 = 'vendor-456';
        
        $this->assertFalse(Permission::canAccessVendorData($vendor, $vendorId2, $vendorId1));
    }

    /**
     * Test customer cannot access vendor data
     */
    public function testCustomerCannotAccessVendorData(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        $vendorId = 'vendor-123';
        
        $this->assertFalse(Permission::canAccessVendorData($customer, $vendorId, null));
    }

    /**
     * Test administrator can access any vendor data
     */
    public function testAdministratorCanAccessAnyVendorData(): void
    {
        $admin = User::create('admin', 'admin@example.com', 'password123', User::ROLE_ADMINISTRATOR);
        $vendorId = 'vendor-123';
        
        $this->assertTrue(Permission::canAccessVendorData($admin, $vendorId, null));
    }

    /**
     * Test customer can access own orders
     */
    public function testCustomerCanAccessOwnOrders(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        $vendorId = 'vendor-123';
        
        $this->assertTrue(Permission::canAccessOrderData($customer, $customer->getId(), $vendorId, null));
    }

    /**
     * Test customer cannot access other customer orders
     */
    public function testCustomerCannotAccessOtherCustomerOrders(): void
    {
        $customer1 = User::create('customer1', 'customer1@example.com', 'password123', User::ROLE_CUSTOMER);
        $customer2 = User::create('customer2', 'customer2@example.com', 'password123', User::ROLE_CUSTOMER);
        $vendorId = 'vendor-123';
        
        $this->assertFalse(Permission::canAccessOrderData($customer1, $customer2->getId(), $vendorId, null));
    }

    /**
     * Test vendor can access orders for their products
     */
    public function testVendorCanAccessOwnOrders(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        $customerId = 'customer-123';
        $vendorId = 'vendor-123';
        
        $this->assertTrue(Permission::canAccessOrderData($vendor, $customerId, $vendorId, $vendorId));
    }

    /**
     * Test vendor cannot access other vendor orders
     */
    public function testVendorCannotAccessOtherVendorOrders(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        $customerId = 'customer-123';
        $vendorId1 = 'vendor-123';
        $vendorId2 = 'vendor-456';
        
        $this->assertFalse(Permission::canAccessOrderData($vendor, $customerId, $vendorId2, $vendorId1));
    }

    /**
     * Test administrator can access any order
     */
    public function testAdministratorCanAccessAnyOrder(): void
    {
        $admin = User::create('admin', 'admin@example.com', 'password123', User::ROLE_ADMINISTRATOR);
        $customerId = 'customer-123';
        $vendorId = 'vendor-123';
        
        $this->assertTrue(Permission::canAccessOrderData($admin, $customerId, $vendorId, null));
    }

    /**
     * Test customer can view products
     */
    public function testCustomerCanViewProducts(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        $vendorId = 'vendor-123';
        
        $this->assertTrue(Permission::canAccessProductData($customer, $vendorId, null));
    }

    /**
     * Test customer cannot modify products
     */
    public function testCustomerCannotModifyProducts(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        $vendorId = 'vendor-123';
        
        $this->assertFalse(Permission::canModifyProductData($customer, $vendorId, null));
    }

    /**
     * Test vendor can modify own products
     */
    public function testVendorCanModifyOwnProducts(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        $vendorId = 'vendor-123';
        
        $this->assertTrue(Permission::canModifyProductData($vendor, $vendorId, $vendorId));
    }

    /**
     * Test vendor cannot modify other vendor products
     */
    public function testVendorCannotModifyOtherVendorProducts(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        $vendorId1 = 'vendor-123';
        $vendorId2 = 'vendor-456';
        
        $this->assertFalse(Permission::canModifyProductData($vendor, $vendorId2, $vendorId1));
    }

    /**
     * Test administrator can modify any product
     */
    public function testAdministratorCanModifyAnyProduct(): void
    {
        $admin = User::create('admin', 'admin@example.com', 'password123', User::ROLE_ADMINISTRATOR);
        $vendorId = 'vendor-123';
        
        $this->assertTrue(Permission::canModifyProductData($admin, $vendorId, null));
    }
}
