<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Auth\Authorization;
use RentalPlatform\Auth\Permission;
use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\UnauthorizedException;
use RentalPlatform\Models\User;

/**
 * Authorization Service Tests
 * 
 * Tests the authorization service for role-based access control
 */
class AuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure session is started for tests
        Session::start();
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        Session::destroy();
        parent::tearDown();
    }

    /**
     * Test authorize with user object
     */
    public function testAuthorizeWithUserObject(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        
        // Customer can read products
        $this->assertTrue(
            Authorization::authorize(Permission::RESOURCE_PRODUCT, Permission::ACTION_READ, $customer)
        );

        // Customer cannot create products
        $this->assertFalse(
            Authorization::authorize(Permission::RESOURCE_PRODUCT, Permission::ACTION_CREATE, $customer)
        );
    }

    /**
     * Test authorize without authentication returns false
     */
    public function testAuthorizeWithoutAuthenticationReturnsFalse(): void
    {
        $this->assertFalse(
            Authorization::authorize(Permission::RESOURCE_PRODUCT, Permission::ACTION_READ)
        );
    }

    /**
     * Test authorize with session
     */
    public function testAuthorizeWithSession(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        // Vendor can create products
        $this->assertTrue(
            Authorization::authorize(Permission::RESOURCE_PRODUCT, Permission::ACTION_CREATE)
        );

        // Vendor cannot manage platform config
        $this->assertFalse(
            Authorization::authorize(Permission::RESOURCE_PLATFORM_CONFIG, Permission::ACTION_MANAGE)
        );
    }

    /**
     * Test require authorization throws exception when unauthorized
     */
    public function testRequireAuthorizationThrowsExceptionWhenUnauthorized(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("User with role 'Customer' is not authorized to perform 'create' on 'product'");
        
        Authorization::requireAuthorization(Permission::RESOURCE_PRODUCT, Permission::ACTION_CREATE, $customer);
    }

    /**
     * Test require authorization succeeds when authorized
     */
    public function testRequireAuthorizationSucceedsWhenAuthorized(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        
        // Should not throw exception
        Authorization::requireAuthorization(Permission::RESOURCE_PRODUCT, Permission::ACTION_CREATE, $vendor);
        
        $this->assertTrue(true); // If we get here, test passed
    }

    /**
     * Test can access user data
     */
    public function testCanAccessUserData(): void
    {
        $user = User::create('user', 'user@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($user);
        
        // User can access own data
        $this->assertTrue(Authorization::canAccessUserData($user->getId()));
        
        // User cannot access other user data
        $this->assertFalse(Authorization::canAccessUserData('other-user-id'));
    }

    /**
     * Test administrator can access any user data
     */
    public function testAdministratorCanAccessAnyUserData(): void
    {
        $admin = User::create('admin', 'admin@example.com', 'password123', User::ROLE_ADMINISTRATOR);
        Session::create($admin);
        
        $this->assertTrue(Authorization::canAccessUserData('any-user-id'));
    }

    /**
     * Test require user data access throws exception when unauthorized
     */
    public function testRequireUserDataAccessThrowsExceptionWhenUnauthorized(): void
    {
        $user = User::create('user', 'user@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($user);
        
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("You are not authorized to access this user's data");
        
        Authorization::requireUserDataAccess('other-user-id');
    }

    /**
     * Test can access vendor data
     */
    public function testCanAccessVendorData(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        $vendorId = 'vendor-123';
        
        // Vendor can access own data
        $this->assertTrue(Authorization::canAccessVendorData($vendorId, $vendorId));
        
        // Vendor cannot access other vendor data
        $this->assertFalse(Authorization::canAccessVendorData('other-vendor-id', $vendorId));
    }

    /**
     * Test require vendor data access throws exception when unauthorized
     */
    public function testRequireVendorDataAccessThrowsExceptionWhenUnauthorized(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        $vendorId = 'vendor-123';
        
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("You are not authorized to access this vendor's data");
        
        Authorization::requireVendorDataAccess('other-vendor-id', $vendorId);
    }

    /**
     * Test can access order data
     */
    public function testCanAccessOrderData(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($customer);
        
        $vendorId = 'vendor-123';
        
        // Customer can access own orders
        $this->assertTrue(Authorization::canAccessOrderData($customer->getId(), $vendorId));
        
        // Customer cannot access other customer orders
        $this->assertFalse(Authorization::canAccessOrderData('other-customer-id', $vendorId));
    }

    /**
     * Test vendor can access orders for their products
     */
    public function testVendorCanAccessOwnOrders(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        $vendorId = 'vendor-123';
        $customerId = 'customer-123';
        
        // Vendor can access orders for their products
        $this->assertTrue(Authorization::canAccessOrderData($customerId, $vendorId, $vendorId));
        
        // Vendor cannot access other vendor orders
        $this->assertFalse(Authorization::canAccessOrderData($customerId, 'other-vendor-id', $vendorId));
    }

    /**
     * Test require order data access throws exception when unauthorized
     */
    public function testRequireOrderDataAccessThrowsExceptionWhenUnauthorized(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($customer);
        
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("You are not authorized to access this order");
        
        Authorization::requireOrderDataAccess('other-customer-id', 'vendor-123');
    }

    /**
     * Test can modify product data
     */
    public function testCanModifyProductData(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        $vendorId = 'vendor-123';
        
        // Vendor can modify own products
        $this->assertTrue(Authorization::canModifyProductData($vendorId, $vendorId));
        
        // Vendor cannot modify other vendor products
        $this->assertFalse(Authorization::canModifyProductData('other-vendor-id', $vendorId));
    }

    /**
     * Test require product modification access throws exception when unauthorized
     */
    public function testRequireProductModificationAccessThrowsExceptionWhenUnauthorized(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        $vendorId = 'vendor-123';
        
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("You are not authorized to modify this product");
        
        Authorization::requireProductModificationAccess('other-vendor-id', $vendorId);
    }

    /**
     * Test is authenticated
     */
    public function testIsAuthenticated(): void
    {
        // Not authenticated initially
        $this->assertFalse(Authorization::isAuthenticated());
        
        // Create session
        $user = User::create('user', 'user@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($user);
        
        // Now authenticated
        $this->assertTrue(Authorization::isAuthenticated());
    }

    /**
     * Test require authentication throws exception when not authenticated
     */
    public function testRequireAuthenticationThrowsExceptionWhenNotAuthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("Authentication required");
        
        Authorization::requireAuthentication();
    }

    /**
     * Test has role
     */
    public function testHasRole(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        $this->assertTrue(Authorization::hasRole(User::ROLE_VENDOR));
        $this->assertFalse(Authorization::hasRole(User::ROLE_CUSTOMER));
        $this->assertFalse(Authorization::hasRole(User::ROLE_ADMINISTRATOR));
    }

    /**
     * Test require role throws exception when user doesn't have role
     */
    public function testRequireRoleThrowsExceptionWhenUserDoesntHaveRole(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($customer);
        
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("Role 'Vendor' required");
        
        Authorization::requireRole(User::ROLE_VENDOR);
    }

    /**
     * Test is customer
     */
    public function testIsCustomer(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($customer);
        
        $this->assertTrue(Authorization::isCustomer());
        $this->assertFalse(Authorization::isVendor());
        $this->assertFalse(Authorization::isAdministrator());
    }

    /**
     * Test is vendor
     */
    public function testIsVendor(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        $this->assertTrue(Authorization::isVendor());
        $this->assertFalse(Authorization::isCustomer());
        $this->assertFalse(Authorization::isAdministrator());
    }

    /**
     * Test is administrator
     */
    public function testIsAdministrator(): void
    {
        $admin = User::create('admin', 'admin@example.com', 'password123', User::ROLE_ADMINISTRATOR);
        Session::create($admin);
        
        $this->assertTrue(Authorization::isAdministrator());
        $this->assertFalse(Authorization::isCustomer());
        $this->assertFalse(Authorization::isVendor());
    }
}
