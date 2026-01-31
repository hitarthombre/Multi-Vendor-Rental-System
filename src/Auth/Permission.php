<?php

namespace RentalPlatform\Auth;

use RentalPlatform\Models\User;

/**
 * Permission System
 * 
 * Defines and manages permissions for different user roles
 */
class Permission
{
    /**
     * Resource types
     */
    public const RESOURCE_USER = 'user';
    public const RESOURCE_PRODUCT = 'product';
    public const RESOURCE_ORDER = 'order';
    public const RESOURCE_INVOICE = 'invoice';
    public const RESOURCE_DOCUMENT = 'document';
    public const RESOURCE_VENDOR = 'vendor';
    public const RESOURCE_CATEGORY = 'category';
    public const RESOURCE_REPORT = 'report';
    public const RESOURCE_PLATFORM_CONFIG = 'platform_config';
    public const RESOURCE_AUDIT_LOG = 'audit_log';

    /**
     * Action types
     */
    public const ACTION_CREATE = 'create';
    public const ACTION_READ = 'read';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_APPROVE = 'approve';
    public const ACTION_REJECT = 'reject';
    public const ACTION_REFUND = 'refund';
    public const ACTION_MANAGE = 'manage';

    /**
     * Permission matrix: role => [resource => [actions]]
     */
    private static array $permissions = [
        User::ROLE_CUSTOMER => [
            self::RESOURCE_USER => [self::ACTION_READ, self::ACTION_UPDATE], // Own profile only
            self::RESOURCE_PRODUCT => [self::ACTION_READ], // Browse products
            self::RESOURCE_ORDER => [self::ACTION_CREATE, self::ACTION_READ], // Own orders only
            self::RESOURCE_INVOICE => [self::ACTION_READ], // Own invoices only
            self::RESOURCE_DOCUMENT => [self::ACTION_CREATE, self::ACTION_READ], // Own documents only
            self::RESOURCE_REPORT => [self::ACTION_READ], // Own reports only
        ],
        User::ROLE_VENDOR => [
            self::RESOURCE_USER => [self::ACTION_READ, self::ACTION_UPDATE], // Own profile only
            self::RESOURCE_PRODUCT => [self::ACTION_CREATE, self::ACTION_READ, self::ACTION_UPDATE, self::ACTION_DELETE], // Own products only
            self::RESOURCE_ORDER => [self::ACTION_READ, self::ACTION_UPDATE, self::ACTION_APPROVE, self::ACTION_REJECT], // Own orders only
            self::RESOURCE_INVOICE => [self::ACTION_READ], // Own invoices only
            self::RESOURCE_DOCUMENT => [self::ACTION_READ], // Documents for own orders only
            self::RESOURCE_VENDOR => [self::ACTION_READ, self::ACTION_UPDATE], // Own vendor profile only
            self::RESOURCE_REPORT => [self::ACTION_READ], // Own reports only
        ],
        User::ROLE_ADMINISTRATOR => [
            self::RESOURCE_USER => [self::ACTION_CREATE, self::ACTION_READ, self::ACTION_UPDATE, self::ACTION_DELETE, self::ACTION_MANAGE],
            self::RESOURCE_PRODUCT => [self::ACTION_READ, self::ACTION_UPDATE, self::ACTION_DELETE, self::ACTION_MANAGE],
            self::RESOURCE_ORDER => [self::ACTION_READ, self::ACTION_UPDATE, self::ACTION_MANAGE],
            self::RESOURCE_INVOICE => [self::ACTION_READ, self::ACTION_MANAGE],
            self::RESOURCE_DOCUMENT => [self::ACTION_READ, self::ACTION_DELETE, self::ACTION_MANAGE],
            self::RESOURCE_VENDOR => [self::ACTION_CREATE, self::ACTION_READ, self::ACTION_UPDATE, self::ACTION_DELETE, self::ACTION_MANAGE],
            self::RESOURCE_CATEGORY => [self::ACTION_CREATE, self::ACTION_READ, self::ACTION_UPDATE, self::ACTION_DELETE, self::ACTION_MANAGE],
            self::RESOURCE_REPORT => [self::ACTION_READ, self::ACTION_MANAGE],
            self::RESOURCE_PLATFORM_CONFIG => [self::ACTION_READ, self::ACTION_UPDATE, self::ACTION_MANAGE],
            self::RESOURCE_AUDIT_LOG => [self::ACTION_READ, self::ACTION_MANAGE],
        ],
    ];

    /**
     * Check if a role has permission to perform an action on a resource
     * 
     * @param string $role User role
     * @param string $resource Resource type
     * @param string $action Action to perform
     * @return bool
     */
    public static function hasPermission(string $role, string $resource, string $action): bool
    {
        // Check if role exists in permission matrix
        if (!isset(self::$permissions[$role])) {
            return false;
        }

        // Check if resource exists for this role
        if (!isset(self::$permissions[$role][$resource])) {
            return false;
        }

        // Check if action is allowed for this resource
        return in_array($action, self::$permissions[$role][$resource], true);
    }

    /**
     * Get all permissions for a role
     * 
     * @param string $role User role
     * @return array
     */
    public static function getPermissionsForRole(string $role): array
    {
        return self::$permissions[$role] ?? [];
    }

    /**
     * Get all allowed actions for a role and resource
     * 
     * @param string $role User role
     * @param string $resource Resource type
     * @return array
     */
    public static function getAllowedActions(string $role, string $resource): array
    {
        if (!isset(self::$permissions[$role][$resource])) {
            return [];
        }

        return self::$permissions[$role][$resource];
    }

    /**
     * Check if user can access another user's data
     * 
     * @param User $currentUser Current authenticated user
     * @param string $targetUserId Target user ID
     * @return bool
     */
    public static function canAccessUserData(User $currentUser, string $targetUserId): bool
    {
        // Administrators can access all user data
        if ($currentUser->isAdministrator()) {
            return true;
        }

        // Users can only access their own data
        return $currentUser->getId() === $targetUserId;
    }

    /**
     * Check if user can access vendor data
     * 
     * @param User $currentUser Current authenticated user
     * @param string $targetVendorId Target vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @return bool
     */
    public static function canAccessVendorData(
        User $currentUser,
        string $targetVendorId,
        ?string $currentUserVendorId = null
    ): bool {
        // Administrators can access all vendor data
        if ($currentUser->isAdministrator()) {
            return true;
        }

        // Vendors can only access their own data
        if ($currentUser->isVendor() && $currentUserVendorId !== null) {
            return $currentUserVendorId === $targetVendorId;
        }

        // Customers cannot access vendor data
        return false;
    }

    /**
     * Check if user can access order data
     * 
     * @param User $currentUser Current authenticated user
     * @param string $orderCustomerId Order's customer ID
     * @param string $orderVendorId Order's vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @return bool
     */
    public static function canAccessOrderData(
        User $currentUser,
        string $orderCustomerId,
        string $orderVendorId,
        ?string $currentUserVendorId = null
    ): bool {
        // Administrators can access all orders
        if ($currentUser->isAdministrator()) {
            return true;
        }

        // Customers can access their own orders
        if ($currentUser->isCustomer() && $currentUser->getId() === $orderCustomerId) {
            return true;
        }

        // Vendors can access orders for their products
        if ($currentUser->isVendor() && $currentUserVendorId !== null) {
            return $currentUserVendorId === $orderVendorId;
        }

        return false;
    }

    /**
     * Check if user can access product data
     * 
     * @param User $currentUser Current authenticated user
     * @param string $productVendorId Product's vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @return bool
     */
    public static function canAccessProductData(
        User $currentUser,
        string $productVendorId,
        ?string $currentUserVendorId = null
    ): bool {
        // Administrators can access all products
        if ($currentUser->isAdministrator()) {
            return true;
        }

        // Customers can view all products (read-only)
        if ($currentUser->isCustomer()) {
            return true; // Read-only access checked separately
        }

        // Vendors can only access their own products
        if ($currentUser->isVendor() && $currentUserVendorId !== null) {
            return $currentUserVendorId === $productVendorId;
        }

        return false;
    }

    /**
     * Check if user can modify product data
     * 
     * @param User $currentUser Current authenticated user
     * @param string $productVendorId Product's vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @return bool
     */
    public static function canModifyProductData(
        User $currentUser,
        string $productVendorId,
        ?string $currentUserVendorId = null
    ): bool {
        // Administrators can modify all products
        if ($currentUser->isAdministrator()) {
            return true;
        }

        // Vendors can only modify their own products
        if ($currentUser->isVendor() && $currentUserVendorId !== null) {
            return $currentUserVendorId === $productVendorId;
        }

        // Customers cannot modify products
        return false;
    }
}
