<?php

namespace RentalPlatform\Auth;

use RentalPlatform\Models\User;
use RentalPlatform\Repositories\UserRepository;

/**
 * Authentication Service
 * 
 * Handles user authentication operations including registration and login
 */
class AuthService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository = null)
    {
        $this->userRepository = $userRepository ?? new UserRepository();
    }

    /**
     * Register a new user
     * 
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $role
     * @return array ['success' => bool, 'message' => string, 'user' => User|null]
     */
    public function register(
        string $username,
        string $email,
        string $password,
        string $role = User::ROLE_CUSTOMER
    ): array {
        // Validate inputs
        $validation = $this->validateRegistration($username, $email, $password, $role);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message'],
                'user' => null
            ];
        }

        // Check if username already exists
        if ($this->userRepository->usernameExists($username)) {
            return [
                'success' => false,
                'message' => 'Username already exists',
                'user' => null
            ];
        }

        // Check if email already exists
        if ($this->userRepository->emailExists($email)) {
            return [
                'success' => false,
                'message' => 'Email already exists',
                'user' => null
            ];
        }

        // Create user
        try {
            $user = User::create($username, $email, $password, $role);
            $created = $this->userRepository->create($user);

            if ($created) {
                return [
                    'success' => true,
                    'message' => 'User registered successfully',
                    'user' => $user
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create user',
                    'user' => null
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
                'user' => null
            ];
        }
    }

    /**
     * Authenticate user with username/email and password
     * 
     * @param string $usernameOrEmail
     * @param string $password
     * @return array ['success' => bool, 'message' => string, 'user' => User|null]
     */
    public function login(string $usernameOrEmail, string $password): array
    {
        // Validate inputs
        if (empty($usernameOrEmail) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Username/email and password are required',
                'user' => null
            ];
        }

        // Find user by username or email
        $user = $this->userRepository->findByUsernameOrEmail($usernameOrEmail);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'user' => null
            ];
        }

        // Verify password
        if (!$user->verifyPassword($password)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'user' => null
            ];
        }

        // Create session
        Session::create($user);

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user
        ];
    }

    /**
     * Logout current user
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function logout(): array
    {
        Session::destroy();

        return [
            'success' => true,
            'message' => 'Logout successful'
        ];
    }

    /**
     * Get currently authenticated user
     * 
     * @return User|null
     */
    public function getCurrentUser(): ?User
    {
        if (!Session::isAuthenticated()) {
            return null;
        }

        $userId = Session::getUserId();
        if (!$userId) {
            return null;
        }

        return $this->userRepository->findById($userId);
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return Session::isAuthenticated();
    }

    /**
     * Validate registration inputs
     * 
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $role
     * @return array ['valid' => bool, 'message' => string]
     */
    private function validateRegistration(
        string $username,
        string $email,
        string $password,
        string $role
    ): array {
        // Validate username
        if (empty($username)) {
            return ['valid' => false, 'message' => 'Username is required'];
        }
        if (strlen($username) < 3) {
            return ['valid' => false, 'message' => 'Username must be at least 3 characters'];
        }
        if (strlen($username) > 50) {
            return ['valid' => false, 'message' => 'Username must not exceed 50 characters'];
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['valid' => false, 'message' => 'Username can only contain letters, numbers, and underscores'];
        }

        // Validate email
        if (empty($email)) {
            return ['valid' => false, 'message' => 'Email is required'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email format'];
        }

        // Validate password
        if (empty($password)) {
            return ['valid' => false, 'message' => 'Password is required'];
        }
        if (strlen($password) < 8) {
            return ['valid' => false, 'message' => 'Password must be at least 8 characters'];
        }

        // Validate role
        if (!User::isValidRole($role)) {
            return ['valid' => false, 'message' => 'Invalid role'];
        }

        return ['valid' => true, 'message' => ''];
    }
}
