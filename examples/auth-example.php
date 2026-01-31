<?php
/**
 * Authentication System Usage Example
 * 
 * This file demonstrates how to use the authentication system
 * for user registration, login, and session management.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Auth\AuthService;
use RentalPlatform\Auth\Session;
use RentalPlatform\Models\User;

// Initialize the authentication service
$authService = new AuthService();

echo "=== Multi-Vendor Rental Platform - Authentication Example ===\n\n";

// Example 1: User Registration
echo "1. User Registration\n";
echo "-------------------\n";

$registrationResult = $authService->register(
    'john_doe',
    'john@example.com',
    'securePassword123',
    User::ROLE_CUSTOMER
);

if ($registrationResult['success']) {
    echo "✓ Registration successful!\n";
    echo "  User ID: " . $registrationResult['user']->getId() . "\n";
    echo "  Username: " . $registrationResult['user']->getUsername() . "\n";
    echo "  Role: " . $registrationResult['user']->getRole() . "\n";
} else {
    echo "✗ Registration failed: " . $registrationResult['message'] . "\n";
}

echo "\n";

// Example 2: User Login
echo "2. User Login\n";
echo "-------------\n";

$loginResult = $authService->login('john_doe', 'securePassword123');

if ($loginResult['success']) {
    echo "✓ Login successful!\n";
    echo "  Welcome, " . $loginResult['user']->getUsername() . "!\n";
    echo "  Role: " . $loginResult['user']->getRole() . "\n";
} else {
    echo "✗ Login failed: " . $loginResult['message'] . "\n";
}

echo "\n";

// Example 3: Check Authentication Status
echo "3. Check Authentication Status\n";
echo "------------------------------\n";

if ($authService->isAuthenticated()) {
    echo "✓ User is authenticated\n";
    echo "  User ID: " . Session::getUserId() . "\n";
    echo "  Username: " . Session::getUsername() . "\n";
    echo "  Email: " . Session::getEmail() . "\n";
    echo "  Role: " . Session::getRole() . "\n";
} else {
    echo "✗ User is not authenticated\n";
}

echo "\n";

// Example 4: Role Checking
echo "4. Role Checking\n";
echo "----------------\n";

if (Session::isCustomer()) {
    echo "✓ User is a Customer\n";
} elseif (Session::isVendor()) {
    echo "✓ User is a Vendor\n";
} elseif (Session::isAdministrator()) {
    echo "✓ User is an Administrator\n";
}

echo "\n";

// Example 5: Get Current User
echo "5. Get Current User\n";
echo "-------------------\n";

$currentUser = $authService->getCurrentUser();
if ($currentUser) {
    echo "✓ Current user retrieved\n";
    echo "  Username: " . $currentUser->getUsername() . "\n";
    echo "  Email: " . $currentUser->getEmail() . "\n";
    echo "  Is Customer: " . ($currentUser->isCustomer() ? 'Yes' : 'No') . "\n";
    echo "  Is Vendor: " . ($currentUser->isVendor() ? 'Yes' : 'No') . "\n";
    echo "  Is Administrator: " . ($currentUser->isAdministrator() ? 'Yes' : 'No') . "\n";
}

echo "\n";

// Example 6: User Logout
echo "6. User Logout\n";
echo "--------------\n";

$logoutResult = $authService->logout();

if ($logoutResult['success']) {
    echo "✓ Logout successful!\n";
    echo "  Message: " . $logoutResult['message'] . "\n";
} else {
    echo "✗ Logout failed\n";
}

echo "\n";

// Example 7: Verify Logout
echo "7. Verify Logout\n";
echo "----------------\n";

if ($authService->isAuthenticated()) {
    echo "✗ User is still authenticated (unexpected)\n";
} else {
    echo "✓ User is not authenticated (as expected after logout)\n";
}

echo "\n";

// Example 8: Failed Login Attempt
echo "8. Failed Login Attempt\n";
echo "-----------------------\n";

$failedLogin = $authService->login('john_doe', 'wrongPassword');

if ($failedLogin['success']) {
    echo "✗ Login succeeded (unexpected)\n";
} else {
    echo "✓ Login failed as expected: " . $failedLogin['message'] . "\n";
}

echo "\n";

// Example 9: Register Different User Roles
echo "9. Register Different User Roles\n";
echo "--------------------------------\n";

// Register a vendor
$vendorResult = $authService->register(
    'vendor_shop',
    'vendor@example.com',
    'vendorPass123',
    User::ROLE_VENDOR
);

if ($vendorResult['success']) {
    echo "✓ Vendor registered: " . $vendorResult['user']->getUsername() . "\n";
}

// Register an administrator
$adminResult = $authService->register(
    'admin_user',
    'admin@example.com',
    'adminPass123',
    User::ROLE_ADMINISTRATOR
);

if ($adminResult['success']) {
    echo "✓ Administrator registered: " . $adminResult['user']->getUsername() . "\n";
}

echo "\n";

// Example 10: Validation Errors
echo "10. Validation Errors\n";
echo "---------------------\n";

// Try to register with short username
$shortUsername = $authService->register('ab', 'test@example.com', 'password123');
echo "Short username: " . $shortUsername['message'] . "\n";

// Try to register with invalid email
$invalidEmail = $authService->register('testuser', 'invalid-email', 'password123');
echo "Invalid email: " . $invalidEmail['message'] . "\n";

// Try to register with short password
$shortPassword = $authService->register('testuser', 'test@example.com', 'short');
echo "Short password: " . $shortPassword['message'] . "\n";

echo "\n=== Example Complete ===\n";
