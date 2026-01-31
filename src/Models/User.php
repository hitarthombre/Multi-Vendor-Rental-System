<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * User Model
 * 
 * Represents a user in the system with role-based access
 */
class User
{
    private string $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private string $role;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Valid user roles
     */
    public const ROLE_CUSTOMER = 'Customer';
    public const ROLE_VENDOR = 'Vendor';
    public const ROLE_ADMINISTRATOR = 'Administrator';

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $username,
        string $email,
        string $passwordHash,
        string $role,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new user instance with generated ID
     */
    public static function create(
        string $username,
        string $email,
        string $password,
        string $role
    ): self {
        $id = UUID::generate();
        $passwordHash = self::hashPassword($password);
        
        return new self($id, $username, $email, $passwordHash, $role);
    }

    /**
     * Hash password using bcrypt
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password against hash
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    /**
     * Check if role is valid
     */
    public static function isValidRole(string $role): bool
    {
        return in_array($role, [
            self::ROLE_CUSTOMER,
            self::ROLE_VENDOR,
            self::ROLE_ADMINISTRATOR
        ], true);
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is a customer
     */
    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    /**
     * Check if user is a vendor
     */
    public function isVendor(): bool
    {
        return $this->role === self::ROLE_VENDOR;
    }

    /**
     * Check if user is an administrator
     */
    public function isAdministrator(): bool
    {
        return $this->role === self::ROLE_ADMINISTRATOR;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
