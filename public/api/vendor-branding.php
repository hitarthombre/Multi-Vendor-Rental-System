<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../src/Auth/Session.php';
require_once '../../src/Auth/Middleware.php';
require_once '../../src/Models/User.php';
require_once '../../src/Repositories/VendorRepository.php';
require_once '../../src/Services/ImageUploadService.php';
require_once '../../src/Services/BrandingService.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Services\ImageUploadService;
use RentalPlatform\Services\BrandingService;

// Start session and require vendor role
Session::start();
Middleware::requireRole(User::ROLE_VENDOR);

$userId = Session::getUserId();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$vendorRepo = new VendorRepository();
$brandingService = new BrandingService();
$vendor = $vendorRepo->findByUserId($userId);

if (!$vendor) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Vendor profile not found'
    ]);
    exit;
}

// Validate branding scope
if (!$brandingService->validateBrandingScope($vendor->getId(), $userId, Session::getUserRole())) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied: Invalid branding scope'
    ]);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'get_branding':
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'brand_color' => $vendor->getBrandColor(),
                            'logo' => $vendor->getLogo(),
                            'business_name' => $vendor->getBusinessName()
                        ]
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Invalid action'
                    ]);
                    break;
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'update_brand_color':
                    $brandColor = $_POST['brand_color'] ?? '';
                    
                    if (empty($brandColor)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Brand color is required'
                        ]);
                        break;
                    }
                    
                    // Validate and sanitize color using BrandingService
                    $sanitizedColor = $brandingService->sanitizeBrandColor($brandColor);
                    if (!$sanitizedColor) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Invalid color format. Use hex format like #3b82f6'
                        ]);
                        break;
                    }
                    
                    $vendor->setBrandColor($sanitizedColor);
                    $vendorRepo->update($vendor);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Brand color updated successfully',
                        'data' => [
                            'brand_color' => $vendor->getBrandColor()
                        ]
                    ]);
                    break;
                    
                case 'upload_logo':
                    if (!isset($_FILES['logo'])) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'No logo file provided'
                        ]);
                        break;
                    }
                    
                    // Validate logo file using BrandingService
                    $validation = $brandingService->validateLogoFile($_FILES['logo']);
                    if (!$validation['valid']) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => $validation['error']
                        ]);
                        break;
                    }
                    
                    $imageUploadService = new ImageUploadService();
                    
                    try {
                        $logoPath = $imageUploadService->uploadVendorLogo($_FILES['logo'], $vendor->getId());
                        
                        $vendor->setLogo($logoPath);
                        $vendorRepo->update($vendor);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Logo uploaded successfully',
                            'data' => [
                                'logo' => $vendor->getLogo()
                            ]
                        ]);
                    } catch (Exception $e) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => $e->getMessage()
                        ]);
                    }
                    break;
                    
                case 'remove_logo':
                    $vendor->setLogo(null);
                    $vendorRepo->update($vendor);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Logo removed successfully'
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Invalid action'
                    ]);
                    break;
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}