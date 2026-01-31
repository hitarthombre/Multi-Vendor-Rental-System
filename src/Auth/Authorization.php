<?php

namespace RentalPlatform\Auth;

use RentalPlatform\Models\User;

/**
 * Authorization Service
 * 
 * Handles authorization checks and enforces role-based access control
 */
class Authorization
{
    /**
     * Check if current user is authorized to perform an action on a resource
     * 
     * @param string $resource Resource type
     * @param string $action Action to perform
     * @param User|null $user User to check (defaults to current session user)
     * @return bool
     */
    public static function authorize(
        string $resource,
        string $action,
        ?User $user = null
    ): bool {
        // If no user provided, check session
        if ($user === null) {
            if (!Session::isAuthenticated()) {
                return false;
            }
            $role = Session::getRole();
        } else {
            $role = $user->getRole();
        }

        if ($role === null) {
            return false;
        }

        return Permission::hasPermission($role, $resource, $action);
    }

    /**
     * Require authorization or throw exception
     * 
     * @param string $resource Resource type
     * @param string $action Action to perform
     * @param User|null $user User to check (defaults to current session user)
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireAuthorization(
        string $resource,
        string $action,
        ?User $user = null
    ): void {
        if (!self::authorize($resource, $action, $user)) {
            $role = $user ? $user->getRole() : Session::getRole();
            throw new UnauthorizedException(
                "User with role '{$role}' is not authorized to perform '{$action}' on '{$resource}'"
            );
        }
    }

    /**
     * Check if current user can access another user's data
     * 
     * @param string $targetUserId Target user ID
     * @param User|null $currentUser Current user (defaults to session user)
     * @return bool
     */
    public static function canAccessUserData(
        string $targetUserId,
        ?User $currentUser = null
    ): bool {
        if ($currentUser === null) {
            if (!Session::isAuthenticated()) {
                return false;
            }
            // Need to get full user object - for now check by ID
            $currentUserId = Session::getUserId();
            $currentRole = Session::getRole();
            
            // Administrators can access all user data
            if ($currentRole === User::ROLE_ADMINISTRATOR) {
                return true;
            }
            
            // Users can only access their own data
            return $currentUserId === $targetUserId;
        }

        return Permission::canAccessUserData($currentUser, $targetUserId);
    }

    /**
     * Require user data access or throw exception
     * 
     * @param string $targetUserId Target user ID
     * @param User|null $currentUser Current user (defaults to session user)
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireUserDataAccess(
        string $targetUserId,
        ?User $currentUser = null
    ): void {
        if (!self::canAccessUserData($targetUserId, $currentUser)) {
            throw new UnauthorizedException(
                "You are not authorized to access this user's data"
            );
        }
    }

    /**
     * Check if current user can access vendor data
     * 
     * @param string $targetVendorId Target vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @param User|null $currentUser Current user (defaults to session user)
     * @return bool
     */
    public static function canAccessVendorData(
        string $targetVendorId,
        ?string $currentUserVendorId = null,
        ?User $currentUser = null
    ): bool {
        if ($currentUser === null) {
            if (!Session::isAuthenticated()) {
                return false;
            }
            $currentRole = Session::getRole();
            
            // Administrators can access all vendor data
            if ($currentRole === User::ROLE_ADMINISTRATOR) {
                return true;
            }
            
            // Vendors can only access their own data
            if ($currentRole === User::ROLE_VENDOR && $currentUserVendorId !== null) {
                return $currentUserVendorId === $targetVendorId;
            }
            
            return false;
        }

        return Permission::canAccessVendorData($currentUser, $targetVendorId, $currentUserVendorId);
    }

    /**
     * Require vendor data access or throw exception
     * 
     * @param string $targetVendorId Target vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @param User|null $currentUser Current user (defaults to session user)
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireVendorDataAccess(
        string $targetVendorId,
        ?string $currentUserVendorId = null,
        ?User $currentUser = null
    ): void {
        if (!self::canAccessVendorData($targetVendorId, $currentUserVendorId, $currentUser)) {
            throw new UnauthorizedException(
                "You are not authorized to access this vendor's data"
            );
        }
    }

    /**
     * Check if current user can access order data
     * 
     * @param string $orderCustomerId Order's customer ID
     * @param string $orderVendorId Order's vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @param User|null $currentUser Current user (defaults to session user)
     * @return bool
     */
    public static function canAccessOrderData(
        string $orderCustomerId,
        string $orderVendorId,
        ?string $currentUserVendorId = null,
        ?User $currentUser = null
    ): bool {
        if ($currentUser === null) {
            if (!Session::isAuthenticated()) {
                return false;
            }
            $currentUserId = Session::getUserId();
            $currentRole = Session::getRole();
            
            // Administrators can access all orders
            if ($currentRole === User::ROLE_ADMINISTRATOR) {
                return true;
            }
            
            // Customers can access their own orders
            if ($currentRole === User::ROLE_CUSTOMER && $currentUserId === $orderCustomerId) {
                return true;
            }
            
            // Vendors can access orders for their products
            if ($currentRole === User::ROLE_VENDOR && $currentUserVendorId !== null) {
                return $currentUserVendorId === $orderVendorId;
            }
            
            return false;
        }

        return Permission::canAccessOrderData(
            $currentUser,
            $orderCustomerId,
            $orderVendorId,
            $currentUserVendorId
        );
    }

    /**
     * Require order data access or throw exception
     * 
     * @param string $orderCustomerId Order's customer ID
     * @param string $orderVendorId Order's vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @param User|null $currentUser Current user (defaults to session user)
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireOrderDataAccess(
        string $orderCustomerId,
        string $orderVendorId,
        ?string $currentUserVendorId = null,
        ?User $currentUser = null
    ): void {
        if (!self::canAccessOrderData($orderCustomerId, $orderVendorId, $currentUserVendorId, $currentUser)) {
            throw new UnauthorizedException(
                "You are not authorized to access this order"
            );
        }
    }

    /**
     * Check if current user can access product data
     * 
     * @param string $productVendorId Product's vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @param User|null $currentUser Current user (defaults to session user)
     * @return bool
     */
    public static function canAccessProductData(
        string $productVendorId,
        ?string $currentUserVendorId = null,
        ?User $currentUser = null
    ): bool {
        if ($currentUser === null) {
            if (!Session::isAuthenticated()) {
                return false;
            }
            $currentRole = Session::getRole();
            
            // Administrators can access all products
            if ($currentRole === User::ROLE_ADMINISTRATOR) {
                return true;
            }
            
            // Customers can view all products
            if ($currentRole === User::ROLE_CUSTOMER) {
                return true;
            }
            
            // Vendors can only access their own products
            if ($currentRole === User::ROLE_VENDOR && $currentUserVendorId !== null) {
                return $currentUserVendorId === $productVendorId;
            }
            
            return false;
        }

        return Permission::canAccessProductData($currentUser, $productVendorId, $currentUserVendorId);
    }

    /**
     * Check if current user can modify product data
     * 
     * @param string $productVendorId Product's vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @param User|null $currentUser Current user (defaults to session user)
     * @return bool
     */
    public static function canModifyProductData(
        string $productVendorId,
        ?string $currentUserVendorId = null,
        ?User $currentUser = null
    ): bool {
        if ($currentUser === null) {
            if (!Session::isAuthenticated()) {
                return false;
            }
            $currentRole = Session::getRole();
            
            // Administrators can modify all products
            if ($currentRole === User::ROLE_ADMINISTRATOR) {
                return true;
            }
            
            // Vendors can only modify their own products
            if ($currentRole === User::ROLE_VENDOR && $currentUserVendorId !== null) {
                return $currentUserVendorId === $productVendorId;
            }
            
            return false;
        }

        return Permission::canModifyProductData($currentUser, $productVendorId, $currentUserVendorId);
    }

    /**
     * Require product modification access or throw exception
     * 
     * @param string $productVendorId Product's vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @param User|null $currentUser Current user (defaults to session user)
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireProductModificationAccess(
        string $productVendorId,
        ?string $currentUserVendorId = null,
        ?User $currentUser = null
    ): void {
        if (!self::canModifyProductData($productVendorId, $currentUserVendorId, $currentUser)) {
            throw new UnauthorizedException(
                "You are not authorized to modify this product"
            );
        }
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        return Session::isAuthenticated();
    }

    /**
     * Require authentication or throw exception
     * 
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireAuthentication(): void
    {
        if (!self::isAuthenticated()) {
            throw new UnauthorizedException("Authentication required");
        }
    }

    /**
     * Check if current user has a specific role
     * 
     * @param string $role Role to check
     * @return bool
     */
    public static function hasRole(string $role): bool
    {
        if (!Session::isAuthenticated()) {
            return false;
        }

        return Session::hasRole($role);
    }

    /**
     * Require specific role or throw exception
     * 
     * @param string $role Required role
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireRole(string $role): void
    {
        if (!self::hasRole($role)) {
            throw new UnauthorizedException("Role '{$role}' required");
        }
    }

    /**
     * Check if current user is a customer
     * 
     * @return bool
     */
    public static function isCustomer(): bool
    {
        return self::hasRole(User::ROLE_CUSTOMER);
    }

    /**
     * Check if current user is a vendor
     * 
     * @return bool
     */
    public static function isVendor(): bool
    {
        return self::hasRole(User::ROLE_VENDOR);
    }

    /**
     * Check if current user is an administrator
     * 
     * @return bool
     */
    public static function isAdministrator(): bool
    {
        return self::hasRole(User::ROLE_ADMINISTRATOR);
    }
}
