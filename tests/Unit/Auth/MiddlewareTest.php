<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Auth\Permission;
use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\UnauthorizedException;
use RentalPlatform\Models\User;

/**
 * Middleware Tests
 * 
 * Tests the authorization middleware for protecting routes
 */
class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Session::start();
    }

    protected function tearDown(): void
    {
        Session::destroy();
        parent::tearDown();
    }

    /**
     * Test require auth throws exception when not authenticated
     */
    public function testRequireAuthThrowsExceptionWhenNotAuthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);
        Middleware::requireAuth();
    }

    /**
     * Test require auth succeeds when authenticated
     */
    public function testRequireAuthSucceedsWhenAuthenticated(): void
    {
        $user = User::create('user', 'user@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($user);
        
        Middleware::requireAuth();
        $this->assertTrue(true); // If we get here, test passed
    }

    /**
     * Test require customer throws exception for non-customer
     */
    public function testRequireCustomerThrowsExceptionForNonCustomer(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        $this->expectException(UnauthorizedException::class);
        Middleware::requireCustomer();
    }

    /**
     * Test require customer succeeds for customer
     */
    public function testRequireCustomerSucceedsForCustomer(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($customer);
        
        Middleware::requireCustomer();
        $this->assertTrue(true);
    }

    /**
     * Test require vendor throws exception for non-vendor
     */
    public function testRequireVendorThrowsExceptionForNonVendor(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($customer);
        
        $this->expectException(UnauthorizedException::class);
        Middleware::requireVendor();
    }

    /**
     * Test require vendor succeeds for vendor
     */
    public function testRequireVendorSucceedsForVendor(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        Middleware::requireVendor();
        $this->assertTrue(true);
    }

    /**
     * Test require administrator throws exception for non-admin
     */
    public function testRequireAdministratorThrowsExceptionForNonAdmin(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($customer);
        
        $this->expectException(UnauthorizedException::class);
        Middleware::requireAdministrator();
    }

    /**
     * Test require administrator succeeds for admin
     */
    public function testRequireAdministratorSucceedsForAdmin(): void
    {
        $admin = User::create('admin', 'admin@example.com', 'password123', User::ROLE_ADMINISTRATOR);
        Session::create($admin);
        
        Middleware::requireAdministrator();
        $this->assertTrue(true);
    }

    /**
     * Test require permission throws exception when unauthorized
     */
    public function testRequirePermissionThrowsExceptionWhenUnauthorized(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($customer);
        
        $this->expectException(UnauthorizedException::class);
        Middleware::requirePermission(Permission::RESOURCE_PRODUCT, Permission::ACTION_CREATE);
    }

    /**
     * Test require permission succeeds when authorized
     */
    public function testRequirePermissionSucceedsWhenAuthorized(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        Middleware::requirePermission(Permission::RESOURCE_PRODUCT, Permission::ACTION_CREATE);
        $this->assertTrue(true);
    }

    /**
     * Test require vendor or admin succeeds for vendor
     */
    public function testRequireVendorOrAdminSucceedsForVendor(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        Middleware::requireVendorOrAdmin();
        $this->assertTrue(true);
    }

    /**
     * Test require vendor or admin succeeds for admin
     */
    public function testRequireVendorOrAdminSucceedsForAdmin(): void
    {
        $admin = User::create('admin', 'admin@example.com', 'password123', User::ROLE_ADMINISTRATOR);
        Session::create($admin);
        
        Middleware::requireVendorOrAdmin();
        $this->assertTrue(true);
    }

    /**
     * Test require vendor or admin throws exception for customer
     */
    public function testRequireVendorOrAdminThrowsExceptionForCustomer(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($customer);
        
        $this->expectException(UnauthorizedException::class);
        Middleware::requireVendorOrAdmin();
    }

    /**
     * Test deny customer throws exception for customer
     */
    public function testDenyCustomerThrowsExceptionForCustomer(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($customer);
        
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("Customers are not authorized to access this resource");
        Middleware::denyCustomer();
    }

    /**
     * Test deny customer succeeds for vendor
     */
    public function testDenyCustomerSucceedsForVendor(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        Middleware::denyCustomer();
        $this->assertTrue(true);
    }

    /**
     * Test deny vendor throws exception for vendor
     */
    public function testDenyVendorThrowsExceptionForVendor(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("Vendors are not authorized to access this resource");
        Middleware::denyVendor();
    }

    /**
     * Test deny vendor succeeds for customer
     */
    public function testDenyVendorSucceedsForCustomer(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($customer);
        
        Middleware::denyVendor();
        $this->assertTrue(true);
    }

    /**
     * Test require user data access
     */
    public function testRequireUserDataAccess(): void
    {
        $user = User::create('user', 'user@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($user);
        
        // Should succeed for own data
        Middleware::requireUserDataAccess($user->getId());
        $this->assertTrue(true);
        
        // Should throw for other user data
        $this->expectException(UnauthorizedException::class);
        Middleware::requireUserDataAccess('other-user-id');
    }

    /**
     * Test require vendor data access
     */
    public function testRequireVendorDataAccess(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        $vendorId = 'vendor-123';
        
        // Should succeed for own vendor data
        Middleware::requireVendorDataAccess($vendorId, $vendorId);
        $this->assertTrue(true);
        
        // Should throw for other vendor data
        $this->expectException(UnauthorizedException::class);
        Middleware::requireVendorDataAccess('other-vendor-id', $vendorId);
    }

    /**
     * Test require order data access
     */
    public function testRequireOrderDataAccess(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password123', User::ROLE_CUSTOMER);
        Session::create($customer);
        
        $vendorId = 'vendor-123';
        
        // Should succeed for own orders
        Middleware::requireOrderDataAccess($customer->getId(), $vendorId);
        $this->assertTrue(true);
        
        // Should throw for other customer orders
        $this->expectException(UnauthorizedException::class);
        Middleware::requireOrderDataAccess('other-customer-id', $vendorId);
    }

    /**
     * Test require product modification access
     */
    public function testRequireProductModificationAccess(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password123', User::ROLE_VENDOR);
        Session::create($vendor);
        
        $vendorId = 'vendor-123';
        
        // Should succeed for own products
        Middleware::requireProductModificationAccess($vendorId, $vendorId);
        $this->assertTrue(true);
        
        // Should throw for other vendor products
        $this->expectException(UnauthorizedException::class);
        Middleware::requireProductModificationAccess('other-vendor-id', $vendorId);
    }

    /**
     * Test handle unauthorized
     */
    public function testHandleUnauthorized(): void
    {
        $exception = new UnauthorizedException("Test unauthorized message");
        $response = Middleware::handleUnauthorized($exception);
        
        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertEquals('Unauthorized', $response['error']);
        $this->assertEquals('Test unauthorized message', $response['message']);
        $this->assertEquals(403, $response['code']);
    }
}
