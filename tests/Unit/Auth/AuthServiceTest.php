<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Auth\AuthService;
use RentalPlatform\Auth\Session;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\UserRepository;

/**
 * Unit tests for AuthService
 */
class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = new UserRepository();
        $this->authService = new AuthService($this->userRepository);
        
        // Clean up any existing sessions
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    protected function tearDown(): void
    {
        // Clean up sessions after each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }

    public function testRegisterWithValidData(): void
    {
        $username = 'testuser_' . time();
        $email = 'test_' . time() . '@example.com';
        $password = 'password123';

        $result = $this->authService->register($username, $email, $password, User::ROLE_CUSTOMER);

        $this->assertTrue($result['success']);
        $this->assertEquals('User registered successfully', $result['message']);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertEquals($username, $result['user']->getUsername());
        $this->assertEquals($email, $result['user']->getEmail());

        // Clean up
        $this->userRepository->delete($result['user']->getId());
    }

    public function testRegisterWithDuplicateUsername(): void
    {
        $username = 'duplicate_' . time();
        $email1 = 'email1_' . time() . '@example.com';
        $email2 = 'email2_' . time() . '@example.com';

        // Register first user
        $result1 = $this->authService->register($username, $email1, 'password123');
        $this->assertTrue($result1['success']);

        // Try to register with same username
        $result2 = $this->authService->register($username, $email2, 'password123');
        $this->assertFalse($result2['success']);
        $this->assertEquals('Username already exists', $result2['message']);

        // Clean up
        $this->userRepository->delete($result1['user']->getId());
    }

    public function testRegisterWithDuplicateEmail(): void
    {
        $username1 = 'user1_' . time();
        $username2 = 'user2_' . time();
        $email = 'duplicate_' . time() . '@example.com';

        // Register first user
        $result1 = $this->authService->register($username1, $email, 'password123');
        $this->assertTrue($result1['success']);

        // Try to register with same email
        $result2 = $this->authService->register($username2, $email, 'password123');
        $this->assertFalse($result2['success']);
        $this->assertEquals('Email already exists', $result2['message']);

        // Clean up
        $this->userRepository->delete($result1['user']->getId());
    }

    public function testRegisterWithShortUsername(): void
    {
        $result = $this->authService->register('ab', 'test@example.com', 'password123');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('at least 3 characters', $result['message']);
    }

    public function testRegisterWithInvalidEmail(): void
    {
        $result = $this->authService->register('testuser', 'invalid-email', 'password123');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid email', $result['message']);
    }

    public function testRegisterWithShortPassword(): void
    {
        $result = $this->authService->register('testuser', 'test@example.com', 'short');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('at least 8 characters', $result['message']);
    }

    public function testRegisterWithInvalidRole(): void
    {
        $result = $this->authService->register('testuser', 'test@example.com', 'password123', 'InvalidRole');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid role', $result['message']);
    }

    public function testLoginWithValidCredentials(): void
    {
        // Register a user first
        $username = 'logintest_' . time();
        $email = 'logintest_' . time() . '@example.com';
        $password = 'password123';
        
        $registerResult = $this->authService->register($username, $email, $password);
        $this->assertTrue($registerResult['success']);

        // Try to login
        $loginResult = $this->authService->login($username, $password);

        $this->assertTrue($loginResult['success']);
        $this->assertEquals('Login successful', $loginResult['message']);
        $this->assertInstanceOf(User::class, $loginResult['user']);
        $this->assertEquals($username, $loginResult['user']->getUsername());

        // Clean up
        $this->userRepository->delete($registerResult['user']->getId());
    }

    public function testLoginWithEmail(): void
    {
        // Register a user first
        $username = 'emaillogin_' . time();
        $email = 'emaillogin_' . time() . '@example.com';
        $password = 'password123';
        
        $registerResult = $this->authService->register($username, $email, $password);
        $this->assertTrue($registerResult['success']);

        // Try to login with email
        $loginResult = $this->authService->login($email, $password);

        $this->assertTrue($loginResult['success']);
        $this->assertEquals('Login successful', $loginResult['message']);

        // Clean up
        $this->userRepository->delete($registerResult['user']->getId());
    }

    public function testLoginWithInvalidUsername(): void
    {
        $result = $this->authService->login('nonexistent_user', 'password123');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['message']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        // Register a user first
        $username = 'wrongpass_' . time();
        $email = 'wrongpass_' . time() . '@example.com';
        
        $registerResult = $this->authService->register($username, $email, 'correctPassword');
        $this->assertTrue($registerResult['success']);

        // Try to login with wrong password
        $loginResult = $this->authService->login($username, 'wrongPassword');

        $this->assertFalse($loginResult['success']);
        $this->assertEquals('Invalid credentials', $loginResult['message']);

        // Clean up
        $this->userRepository->delete($registerResult['user']->getId());
    }

    public function testLoginWithEmptyCredentials(): void
    {
        $result = $this->authService->login('', '');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('required', $result['message']);
    }

    public function testLogout(): void
    {
        $result = $this->authService->logout();

        $this->assertTrue($result['success']);
        $this->assertEquals('Logout successful', $result['message']);
    }

    public function testIsAuthenticatedWhenNotLoggedIn(): void
    {
        $this->assertFalse($this->authService->isAuthenticated());
    }

    public function testGetCurrentUserWhenNotLoggedIn(): void
    {
        $this->assertNull($this->authService->getCurrentUser());
    }
}
