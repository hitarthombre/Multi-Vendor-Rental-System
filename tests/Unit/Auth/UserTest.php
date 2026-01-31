<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Models\User;

/**
 * Unit tests for User model
 */
class UserTest extends TestCase
{
    public function testCreateUserWithValidData(): void
    {
        $user = User::create('testuser', 'test@example.com', 'password123', User::ROLE_CUSTOMER);

        $this->assertNotEmpty($user->getId());
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals(User::ROLE_CUSTOMER, $user->getRole());
        $this->assertNotEmpty($user->getPasswordHash());
    }

    public function testPasswordHashing(): void
    {
        $password = 'mySecurePassword123';
        $hash = User::hashPassword($password);

        $this->assertNotEmpty($hash);
        $this->assertNotEquals($password, $hash);
        $this->assertTrue(password_verify($password, $hash));
    }

    public function testVerifyPasswordWithCorrectPassword(): void
    {
        $password = 'correctPassword';
        $user = User::create('testuser', 'test@example.com', $password, User::ROLE_CUSTOMER);

        $this->assertTrue($user->verifyPassword($password));
    }

    public function testVerifyPasswordWithIncorrectPassword(): void
    {
        $user = User::create('testuser', 'test@example.com', 'correctPassword', User::ROLE_CUSTOMER);

        $this->assertFalse($user->verifyPassword('wrongPassword'));
    }

    public function testIsValidRoleWithValidRoles(): void
    {
        $this->assertTrue(User::isValidRole(User::ROLE_CUSTOMER));
        $this->assertTrue(User::isValidRole(User::ROLE_VENDOR));
        $this->assertTrue(User::isValidRole(User::ROLE_ADMINISTRATOR));
    }

    public function testIsValidRoleWithInvalidRole(): void
    {
        $this->assertFalse(User::isValidRole('InvalidRole'));
        $this->assertFalse(User::isValidRole(''));
    }

    public function testHasRoleMethod(): void
    {
        $user = User::create('testuser', 'test@example.com', 'password', User::ROLE_VENDOR);

        $this->assertTrue($user->hasRole(User::ROLE_VENDOR));
        $this->assertFalse($user->hasRole(User::ROLE_CUSTOMER));
    }

    public function testIsCustomerMethod(): void
    {
        $customer = User::create('customer', 'customer@example.com', 'password', User::ROLE_CUSTOMER);
        $vendor = User::create('vendor', 'vendor@example.com', 'password', User::ROLE_VENDOR);

        $this->assertTrue($customer->isCustomer());
        $this->assertFalse($vendor->isCustomer());
    }

    public function testIsVendorMethod(): void
    {
        $vendor = User::create('vendor', 'vendor@example.com', 'password', User::ROLE_VENDOR);
        $customer = User::create('customer', 'customer@example.com', 'password', User::ROLE_CUSTOMER);

        $this->assertTrue($vendor->isVendor());
        $this->assertFalse($customer->isVendor());
    }

    public function testIsAdministratorMethod(): void
    {
        $admin = User::create('admin', 'admin@example.com', 'password', User::ROLE_ADMINISTRATOR);
        $customer = User::create('customer', 'customer@example.com', 'password', User::ROLE_CUSTOMER);

        $this->assertTrue($admin->isAdministrator());
        $this->assertFalse($customer->isAdministrator());
    }

    public function testToArrayMethod(): void
    {
        $user = User::create('testuser', 'test@example.com', 'password', User::ROLE_CUSTOMER);
        $array = $user->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('username', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('role', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertArrayNotHasKey('password_hash', $array); // Should not expose password hash
    }

    public function testPasswordHashUsesCorrectAlgorithm(): void
    {
        $password = 'testPassword123';
        $hash = User::hashPassword($password);

        // Verify it's a bcrypt hash (starts with $2y$)
        $this->assertStringStartsWith('$2y$', $hash);
    }
}
