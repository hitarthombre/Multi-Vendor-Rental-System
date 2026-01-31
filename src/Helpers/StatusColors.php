<?php

namespace RentalPlatform\Helpers;

/**
 * Status Colors Utility
 * 
 * Provides standardized status colors across the platform
 * Ensures consistent visual representation regardless of vendor branding
 */
class StatusColors
{
    /**
     * Standardized status color mappings
     * These colors should remain consistent regardless of vendor branding
     */
    public const STATUS_COLORS = [
        // Order Status Colors
        'Payment_Successful' => [
            'color' => 'blue',
            'bg_class' => 'bg-blue-100',
            'text_class' => 'text-blue-800',
            'border_class' => 'border-blue-200'
        ],
        'Pending_Vendor_Approval' => [
            'color' => 'yellow',
            'bg_class' => 'bg-yellow-100',
            'text_class' => 'text-yellow-800',
            'border_class' => 'border-yellow-200'
        ],
        'Auto_Approved' => [
            'color' => 'green',
            'bg_class' => 'bg-green-100',
            'text_class' => 'text-green-800',
            'border_class' => 'border-green-200'
        ],
        'Active_Rental' => [
            'color' => 'green',
            'bg_class' => 'bg-green-100',
            'text_class' => 'text-green-800',
            'border_class' => 'border-green-200'
        ],
        'Completed' => [
            'color' => 'gray',
            'bg_class' => 'bg-gray-100',
            'text_class' => 'text-gray-800',
            'border_class' => 'border-gray-200'
        ],
        'Rejected' => [
            'color' => 'red',
            'bg_class' => 'bg-red-100',
            'text_class' => 'text-red-800',
            'border_class' => 'border-red-200'
        ],
        'Refunded' => [
            'color' => 'purple',
            'bg_class' => 'bg-purple-100',
            'text_class' => 'text-purple-800',
            'border_class' => 'border-purple-200'
        ],
        
        // Product Status Colors
        'Active' => [
            'color' => 'green',
            'bg_class' => 'bg-green-100',
            'text_class' => 'text-green-800',
            'border_class' => 'border-green-200'
        ],
        'Inactive' => [
            'color' => 'gray',
            'bg_class' => 'bg-gray-100',
            'text_class' => 'text-gray-800',
            'border_class' => 'border-gray-200'
        ],
        'Deleted' => [
            'color' => 'red',
            'bg_class' => 'bg-red-100',
            'text_class' => 'text-red-800',
            'border_class' => 'border-red-200'
        ],
        
        // Vendor Status Colors
        'Pending' => [
            'color' => 'yellow',
            'bg_class' => 'bg-yellow-100',
            'text_class' => 'text-yellow-800',
            'border_class' => 'border-yellow-200'
        ],
        'Suspended' => [
            'color' => 'red',
            'bg_class' => 'bg-red-100',
            'text_class' => 'text-red-800',
            'border_class' => 'border-red-200'
        ],
        
        // Payment Status Colors
        'Verified' => [
            'color' => 'green',
            'bg_class' => 'bg-green-100',
            'text_class' => 'text-green-800',
            'border_class' => 'border-green-200'
        ],
        'Failed' => [
            'color' => 'red',
            'bg_class' => 'bg-red-100',
            'text_class' => 'text-red-800',
            'border_class' => 'border-red-200'
        ]
    ];

    /**
     * Get status color information
     * 
     * @param string $status
     * @return array
     */
    public static function getStatusColors(string $status): array
    {
        return self::STATUS_COLORS[$status] ?? [
            'color' => 'gray',
            'bg_class' => 'bg-gray-100',
            'text_class' => 'text-gray-800',
            'border_class' => 'border-gray-200'
        ];
    }

    /**
     * Get status color name
     * 
     * @param string $status
     * @return string
     */
    public static function getStatusColor(string $status): string
    {
        return self::getStatusColors($status)['color'];
    }

    /**
     * Get status background class
     * 
     * @param string $status
     * @return string
     */
    public static function getStatusBgClass(string $status): string
    {
        return self::getStatusColors($status)['bg_class'];
    }

    /**
     * Get status text class
     * 
     * @param string $status
     * @return string
     */
    public static function getStatusTextClass(string $status): string
    {
        return self::getStatusColors($status)['text_class'];
    }

    /**
     * Get status border class
     * 
     * @param string $status
     * @return string
     */
    public static function getStatusBorderClass(string $status): string
    {
        return self::getStatusColors($status)['border_class'];
    }

    /**
     * Generate status badge HTML
     * 
     * @param string $status
     * @param string $label Optional custom label
     * @return string
     */
    public static function generateStatusBadge(string $status, string $label = null): string
    {
        $colors = self::getStatusColors($status);
        $displayLabel = $label ?? ucfirst(str_replace('_', ' ', $status));
        
        return sprintf(
            '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full %s %s">%s</span>',
            $colors['bg_class'],
            $colors['text_class'],
            htmlspecialchars($displayLabel)
        );
    }

    /**
     * Generate status indicator dot
     * 
     * @param string $status
     * @return string
     */
    public static function generateStatusDot(string $status): string
    {
        $colors = self::getStatusColors($status);
        $colorMap = [
            'green' => 'bg-green-400',
            'yellow' => 'bg-yellow-400',
            'red' => 'bg-red-400',
            'blue' => 'bg-blue-400',
            'purple' => 'bg-purple-400',
            'gray' => 'bg-gray-400'
        ];
        
        $dotColor = $colorMap[$colors['color']] ?? 'bg-gray-400';
        
        return sprintf(
            '<span class="inline-block w-2 h-2 rounded-full %s"></span>',
            $dotColor
        );
    }

    /**
     * Get all available status colors
     * 
     * @return array
     */
    public static function getAllStatusColors(): array
    {
        return self::STATUS_COLORS;
    }

    /**
     * Check if status exists
     * 
     * @param string $status
     * @return bool
     */
    public static function statusExists(string $status): bool
    {
        return isset(self::STATUS_COLORS[$status]);
    }
}