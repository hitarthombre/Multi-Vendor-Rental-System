<?php

namespace RentalPlatform\Services;

use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Auth\Session;

/**
 * Branding Service
 * 
 * Ensures vendor branding scope isolation and validation
 * Requirements: 22.7 - Vendor theming scoped only to vendor UI
 */
class BrandingService
{
    private VendorRepository $vendorRepo;

    public function __construct()
    {
        $this->vendorRepo = new VendorRepository();
    }

    /**
     * Get branding for current context
     * Returns vendor branding only for vendor pages, platform branding otherwise
     * 
     * @param string|null $vendorId Optional vendor ID for specific vendor branding
     * @return array
     */
    public function getBrandingForContext(?string $vendorId = null): array
    {
        $userRole = Session::getUserRole();
        $currentUserId = Session::getUserId();
        
        // Only apply vendor branding for vendor users on their own pages
        if ($userRole === 'Vendor' && $vendorId) {
            // Verify the vendor ID belongs to the current user
            $vendor = $this->vendorRepo->findById($vendorId);
            if ($vendor && $vendor->getUserId() === $currentUserId) {
                return $this->getVendorBranding($vendor);
            }
        }
        
        // For all other cases, return platform branding
        return $this->getPlatformBranding();
    }

    /**
     * Get vendor branding (only for authorized vendor)
     * 
     * @param \RentalPlatform\Models\Vendor $vendor
     * @return array
     */
    private function getVendorBranding($vendor): array
    {
        return [
            'type' => 'vendor',
            'brand_color' => $vendor->getBrandColor() ?? '#3b82f6',
            'logo' => $vendor->getLogo(),
            'business_name' => $vendor->getBusinessName(),
            'legal_name' => $vendor->getLegalName(),
            'scope' => 'vendor_only'
        ];
    }

    /**
     * Get platform branding
     * 
     * @return array
     */
    private function getPlatformBranding(): array
    {
        return [
            'type' => 'platform',
            'brand_color' => '#3b82f6',
            'logo' => null,
            'business_name' => 'RentalHub',
            'legal_name' => 'RentalHub Multi-Vendor Platform',
            'scope' => 'platform_wide'
        ];
    }

    /**
     * Validate branding scope
     * Ensures vendor branding is only applied in appropriate contexts
     * 
     * @param string $requestedVendorId
     * @param string $currentUserId
     * @param string $userRole
     * @return bool
     */
    public function validateBrandingScope(string $requestedVendorId, string $currentUserId, string $userRole): bool
    {
        // Only vendors can access vendor branding
        if ($userRole !== 'Vendor') {
            return false;
        }

        // Vendor can only access their own branding
        $vendor = $this->vendorRepo->findById($requestedVendorId);
        if (!$vendor || $vendor->getUserId() !== $currentUserId) {
            return false;
        }

        return true;
    }

    /**
     * Generate CSS variables for branding
     * 
     * @param array $branding
     * @return string
     */
    public function generateBrandingCSS(array $branding): string
    {
        if ($branding['type'] === 'vendor') {
            return "
                :root {
                    --vendor-brand-color: {$branding['brand_color']};
                    --vendor-brand-light: {$branding['brand_color']}20;
                    --vendor-brand-dark: {$branding['brand_color']}dd;
                }
                .brand-bg { background-color: var(--vendor-brand-color) !important; }
                .brand-text { color: var(--vendor-brand-color) !important; }
                .brand-border { border-color: var(--vendor-brand-color) !important; }
                .brand-bg-light { background-color: var(--vendor-brand-light) !important; }
                .brand-hover:hover { background-color: var(--vendor-brand-dark) !important; }
            ";
        }
        
        // Platform branding uses default CSS
        return '';
    }

    /**
     * Check if current page should use vendor branding
     * 
     * @param string $currentPath
     * @return bool
     */
    public function shouldUseVendorBranding(string $currentPath): bool
    {
        $vendorPaths = [
            '/vendor/',
            '/Multi-Vendor-Rental-System/public/vendor/'
        ];

        foreach ($vendorPaths as $path) {
            if (strpos($currentPath, $path) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get branding for invoice generation
     * 
     * @param string $vendorId
     * @return array
     */
    public function getBrandingForInvoice(string $vendorId): array
    {
        $vendor = $this->vendorRepo->findById($vendorId);
        if (!$vendor) {
            return $this->getPlatformBranding();
        }

        return $this->getVendorBranding($vendor);
    }

    /**
     * Sanitize brand color input
     * 
     * @param string $color
     * @return string|null
     */
    public function sanitizeBrandColor(string $color): ?string
    {
        // Remove any whitespace
        $color = trim($color);
        
        // Validate hex color format
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
            return null;
        }
        
        return $color;
    }

    /**
     * Validate logo file
     * 
     * @param array $file $_FILES array element
     * @return array ['valid' => bool, 'error' => string]
     */
    public function validateLogoFile(array $file): array
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $maxSize = 5242880; // 5MB

        // Check file size
        if ($file['size'] > $maxSize) {
            return [
                'valid' => false,
                'error' => 'Logo file size must be less than 5MB'
            ];
        }

        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'Logo must be a valid image file (JPEG, PNG, WebP)'
            ];
        }

        // Check if it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return [
                'valid' => false,
                'error' => 'File is not a valid image'
            ];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * Get branding context information
     * 
     * @return array
     */
    public function getBrandingContext(): array
    {
        $userRole = Session::getUserRole();
        $userId = Session::getUserId();
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';

        return [
            'user_role' => $userRole,
            'user_id' => $userId,
            'current_path' => $currentPath,
            'should_use_vendor_branding' => $this->shouldUseVendorBranding($currentPath) && $userRole === 'Vendor',
            'is_vendor_page' => $this->shouldUseVendorBranding($currentPath),
            'is_customer_page' => strpos($currentPath, '/customer/') !== false,
            'is_admin_page' => strpos($currentPath, '/admin/') !== false
        ];
    }
}