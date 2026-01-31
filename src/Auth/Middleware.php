<?php

namespace RentalPlatform\Auth;

use RentalPlatform\Models\User;

/**
 * Authorization Middleware
 * 
 * Provides middleware functions for protecting routes and endpoints
 */
class Middleware
{
    /**
     * Require authentication middleware
     * 
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireAuth(): void
    {
        Authorization::requireAuthentication();
    }

    /**
     * Require specific role middleware
     * 
     * @param string $role Required role
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireRole(string $role): void
    {
        Authorization::requireAuthentication();
        Authorization::requireRole($role);
    }

    /**
     * Require customer role middleware
     * 
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireCustomer(): void
    {
        self::requireRole(User::ROLE_CUSTOMER);
    }

    /**
     * Require vendor role middleware
     * 
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireVendor(): void
    {
        self::requireRole(User::ROLE_VENDOR);
    }

    /**
     * Require administrator role middleware
     * 
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireAdministrator(): void
    {
        self::requireRole(User::ROLE_ADMINISTRATOR);
    }

    /**
     * Require permission middleware
     * 
     * @param string $resource Resource type
     * @param string $action Action to perform
     * @throws UnauthorizedException
     * @return void
     */
    public static function requirePermission(string $resource, string $action): void
    {
        Authorization::requireAuthentication();
        Authorization::requireAuthorization($resource, $action);
    }

    /**
     * Require vendor or administrator role middleware
     * 
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireVendorOrAdmin(): void
    {
        Authorization::requireAuthentication();
        
        if (!Authorization::isVendor() && !Authorization::isAdministrator()) {
            throw new UnauthorizedException("Vendor or Administrator role required");
        }
    }

    /**
     * Deny customer access middleware (vendor or admin only)
     * 
     * @throws UnauthorizedException
     * @return void
     */
    public static function denyCustomer(): void
    {
        Authorization::requireAuthentication();
        
        if (Authorization::isCustomer()) {
            throw new UnauthorizedException("Customers are not authorized to access this resource");
        }
    }

    /**
     * Deny vendor access middleware (customer or admin only)
     * 
     * @throws UnauthorizedException
     * @return void
     */
    public static function denyVendor(): void
    {
        Authorization::requireAuthentication();
        
        if (Authorization::isVendor()) {
            throw new UnauthorizedException("Vendors are not authorized to access this resource");
        }
    }

    /**
     * Check user data access middleware
     * 
     * @param string $targetUserId Target user ID
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireUserDataAccess(string $targetUserId): void
    {
        Authorization::requireAuthentication();
        Authorization::requireUserDataAccess($targetUserId);
    }

    /**
     * Check vendor data access middleware
     * 
     * @param string $targetVendorId Target vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireVendorDataAccess(
        string $targetVendorId,
        ?string $currentUserVendorId = null
    ): void {
        Authorization::requireAuthentication();
        Authorization::requireVendorDataAccess($targetVendorId, $currentUserVendorId);
    }

    /**
     * Check order data access middleware
     * 
     * @param string $orderCustomerId Order's customer ID
     * @param string $orderVendorId Order's vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireOrderDataAccess(
        string $orderCustomerId,
        string $orderVendorId,
        ?string $currentUserVendorId = null
    ): void {
        Authorization::requireAuthentication();
        Authorization::requireOrderDataAccess($orderCustomerId, $orderVendorId, $currentUserVendorId);
    }

    /**
     * Check product modification access middleware
     * 
     * @param string $productVendorId Product's vendor ID
     * @param string|null $currentUserVendorId Current user's vendor ID (if vendor)
     * @throws UnauthorizedException
     * @return void
     */
    public static function requireProductModificationAccess(
        string $productVendorId,
        ?string $currentUserVendorId = null
    ): void {
        Authorization::requireAuthentication();
        Authorization::requireProductModificationAccess($productVendorId, $currentUserVendorId);
    }

    /**
     * Handle unauthorized access
     * 
     * @param UnauthorizedException $e Exception
     * @return array Response array
     */
    public static function handleUnauthorized(UnauthorizedException $e): array
    {
        return [
            'success' => false,
            'error' => 'Unauthorized',
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ];
    }

    /**
     * Send unauthorized response and exit
     * 
     * @param UnauthorizedException $e Exception
     * @return void
     */
    public static function sendUnauthorizedResponse(UnauthorizedException $e): void
    {
        http_response_code($e->getCode());
        header('Content-Type: application/json');
        echo json_encode(self::handleUnauthorized($e));
        exit;
    }
}
