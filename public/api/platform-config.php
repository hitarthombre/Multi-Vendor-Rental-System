<?php
/**
 * Platform Configuration API
 * 
 * Handles platform configuration management for administrators
 */

require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Auth/Session.php';
require_once __DIR__ . '/../../src/Auth/Authorization.php';
require_once __DIR__ . '/../../src/Auth/Permission.php';
require_once __DIR__ . '/../../src/Models/User.php';
require_once __DIR__ . '/../../src/Repositories/PlatformConfigRepository.php';
require_once __DIR__ . '/../../src/Services/ConfigurationService.php';
require_once __DIR__ . '/../../src/Services/AuditLogger.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Authorization;
use RentalPlatform\Auth\Permission;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\PlatformConfigRepository;
use RentalPlatform\Services\ConfigurationService;
use RentalPlatform\Services\AuditLogger;

header('Content-Type: application/json');

try {
    $db = Connection::getInstance();
    $session = new Session();
    $auth = new Authorization($session);
    
    // Check authentication
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $currentUser = $auth->getCurrentUser();
    
    // Check admin permission
    if (!Permission::hasPermission($currentUser->getRole(), Permission::RESOURCE_PLATFORM_CONFIG, Permission::ACTION_READ)) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }
    
    $repository = new PlatformConfigRepository($db);
    $auditLogger = new AuditLogger($db);
    $configService = new ConfigurationService($repository, $auditLogger);
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGet($configService);
            break;
            
        case 'POST':
        case 'PUT':
            handleUpdate($configService, $currentUser, $auth);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Platform Config API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function handleGet(ConfigurationService $configService): void
{
    $category = $_GET['category'] ?? null;
    $publicOnly = isset($_GET['public']) && $_GET['public'] === 'true';
    
    if ($category) {
        $configs = $configService->getByCategory($category);
        echo json_encode(['success' => true, 'data' => $configs]);
    } else {
        $configs = $configService->getAllGrouped($publicOnly);
        $categories = $configService->getCategories();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'configurations' => $configs,
                'categories' => $categories
            ]
        ]);
    }
}

function handleUpdate(ConfigurationService $configService, User $currentUser, Authorization $auth): void
{
    // Check update permission
    if (!Permission::hasPermission($currentUser->getRole(), Permission::RESOURCE_PLATFORM_CONFIG, Permission::ACTION_UPDATE)) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions to update configuration']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['configurations'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input data']);
        return;
    }
    
    $configurations = $input['configurations'];
    
    // Validate all configurations first
    $errors = $configService->validateConfigData($configurations);
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Validation failed',
            'validation_errors' => $errors
        ]);
        return;
    }
    
    // Update configurations
    $success = $configService->bulkUpdate($configurations, $currentUser->getId());
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Configuration updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update configuration']);
    }
}