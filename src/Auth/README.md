# Authentication Module

This module provides secure user authentication and session management for the Multi-Vendor Rental Platform.

## Features

- **User Registration**: Create new user accounts with role-based access
- **User Login**: Authenticate users with username/email and password
- **Password Security**: Bcrypt password hashing with cost factor 12
- **Session Management**: Secure session handling with timeout and integrity checks
- **Role-Based Access**: Support for Customer, Vendor, and Administrator roles
- **Session Security**: Protection against session fixation and hijacking

## Components

### User Model (`User.php`)

Represents a user in the system with the following properties:
- `id`: Unique identifier (UUID)
- `username`: Unique username
- `email`: Unique email address
- `passwordHash`: Bcrypt hashed password
- `role`: User role (Customer, Vendor, Administrator)
- `createdAt`: Account creation timestamp
- `updatedAt`: Last update timestamp

**Key Methods:**
- `create()`: Create a new user with hashed password
- `verifyPassword()`: Verify password against hash
- `hasRole()`: Check if user has a specific role
- `isCustomer()`, `isVendor()`, `isAdministrator()`: Role checking helpers

### UserRepository (`UserRepository.php`)

Handles database operations for User entities:
- `create()`: Insert new user into database
- `findById()`: Find user by ID
- `findByUsername()`: Find user by username
- `findByEmail()`: Find user by email
- `findByUsernameOrEmail()`: Find user by username or email
- `update()`: Update user information
- `delete()`: Delete user
- `usernameExists()`: Check if username is taken
- `emailExists()`: Check if email is taken
- `findByRole()`: Get all users with a specific role
- `findAll()`: Get all users

### Session Manager (`Session.php`)

Manages secure user sessions:
- `create()`: Create session for authenticated user
- `isAuthenticated()`: Check if user is authenticated
- `getUserId()`, `getUsername()`, `getEmail()`, `getRole()`: Get session data
- `hasRole()`: Check user role from session
- `destroy()`: Logout and destroy session

**Security Features:**
- Session timeout (30 minutes of inactivity)
- Session ID regeneration on login
- IP address validation
- User agent validation
- Secure cookie settings

### AuthService (`AuthService.php`)

High-level authentication service:
- `register()`: Register new user with validation
- `login()`: Authenticate user and create session
- `logout()`: Destroy user session
- `getCurrentUser()`: Get currently authenticated user
- `isAuthenticated()`: Check authentication status

**Validation Rules:**
- Username: 3-50 characters, alphanumeric and underscores only
- Email: Valid email format
- Password: Minimum 8 characters
- Role: Must be valid role (Customer, Vendor, Administrator)

## Usage Examples

### User Registration

```php
use RentalPlatform\Auth\AuthService;
use RentalPlatform\Models\User;

$authService = new AuthService();

$result = $authService->register(
    'john_doe',
    'john@example.com',
    'securePassword123',
    User::ROLE_CUSTOMER
);

if ($result['success']) {
    echo "Registration successful!";
    $user = $result['user'];
} else {
    echo "Error: " . $result['message'];
}
```

### User Login

```php
$result = $authService->login('john_doe', 'securePassword123');

if ($result['success']) {
    echo "Login successful!";
    $user = $result['user'];
} else {
    echo "Error: " . $result['message'];
}
```

### Check Authentication

```php
use RentalPlatform\Auth\Session;

if (Session::isAuthenticated()) {
    echo "User is logged in";
    echo "Username: " . Session::getUsername();
    echo "Role: " . Session::getRole();
}
```

### Role-Based Access Control

```php
if (Session::isCustomer()) {
    // Show customer dashboard
} elseif (Session::isVendor()) {
    // Show vendor dashboard
} elseif (Session::isAdministrator()) {
    // Show admin dashboard
}
```

### Get Current User

```php
$currentUser = $authService->getCurrentUser();

if ($currentUser) {
    echo "Welcome, " . $currentUser->getUsername();
}
```

### Logout

```php
$result = $authService->logout();

if ($result['success']) {
    echo "Logged out successfully";
}
```

## Security Considerations

1. **Password Hashing**: All passwords are hashed using bcrypt with cost factor 12
2. **Session Security**: 
   - Sessions expire after 30 minutes of inactivity
   - Session IDs are regenerated on login
   - IP address and user agent are validated
3. **Input Validation**: All user inputs are validated before processing
4. **SQL Injection Prevention**: All database queries use prepared statements
5. **Credential Hiding**: Password hashes are never exposed in API responses

## Testing

The authentication module includes comprehensive unit tests:

```bash
# Run all authentication tests
php vendor/phpunit/phpunit/phpunit tests/Unit/Auth/

# Run specific test file
php vendor/phpunit/phpunit/phpunit tests/Unit/Auth/UserTest.php
php vendor/phpunit/phpunit/phpunit tests/Unit/Auth/AuthServiceTest.php
```

## Requirements Validation

This implementation satisfies the following requirements:

- **Requirement 1.2**: User authentication with credential verification
- **Requirement 1.3**: Secure session creation on successful authentication
- **Requirement 1.4, 1.5, 1.6**: Role-based access control foundation
- **Requirement 21.2**: Backend-level permission enforcement
- **Requirement 21.4**: Customer data isolation

## Role-Based Access Control (RBAC)

The authentication module is complemented by a comprehensive RBAC system that enforces permissions at the backend level. See [RBAC_README.md](RBAC_README.md) for detailed documentation.

**Key RBAC Components:**
- **Permission System**: Defines permission matrix for roles, resources, and actions
- **Authorization Service**: Provides authorization checks and enforcement
- **Middleware**: Convenient route protection functions
- **Data Isolation**: Ensures vendors and customers can only access their own data

**Quick RBAC Example:**

```php
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Auth\Permission;

// Protect an endpoint
try {
    Middleware::requireAuth();
    Middleware::requireVendor();
    Middleware::requirePermission(
        Permission::RESOURCE_PRODUCT,
        Permission::ACTION_CREATE
    );
    
    // User is authorized, proceed with product creation
} catch (UnauthorizedException $e) {
    // Handle unauthorized access
    Middleware::sendUnauthorizedResponse($e);
}
```

See `examples/rbac-example.php` for comprehensive usage examples.

## Next Steps

After implementing authentication and RBAC, the next tasks are:
1. Add property-based tests for authentication (Task 2.2)
2. Add property-based tests for RBAC (Task 2.4)
3. Implement audit logging for authentication events (Task 3.1)
