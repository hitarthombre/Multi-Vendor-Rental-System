<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Database\Connection;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Services\AuditLogger;

header('Content-Type: application/json');

Session::start();

try {
    Middleware::requireAdministrator();
    
    $db = Connection::getInstance();
    $vendorRepo = new VendorRepository();
    $auditLogger = new AuditLogger($db);
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? null;
        $vendorId = $data['vendor_id'] ?? null;
        
        if (!$vendorId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Vendor ID is required']);
            exit;
        }
        
        $vendor = $vendorRepo->findById($vendorId);
        if (!$vendor) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Vendor not found']);
            exit;
        }
        
        $currentUser = Session::getCurrentUser();
        
        switch ($action) {
            case 'approve':
                $vendor->setStatus('Active');
                $vendorRepo->update($vendor);
                
                // Log the action
                $auditLogger->logVendorApproval($vendorId, $currentUser['id']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Vendor approved successfully',
                    'status' => 'Active'
                ]);
                break;
                
            case 'suspend':
                $reason = $data['reason'] ?? 'No reason provided';
                $vendor->setStatus('Suspended');
                $vendorRepo->update($vendor);
                
                // Log the action
                $auditLogger->logVendorSuspend($vendorId, $currentUser['id'], $reason);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Vendor suspended successfully',
                    'status' => 'Suspended'
                ]);
                break;
                
            case 'activate':
                $vendor->setStatus('Active');
                $vendorRepo->update($vendor);
                
                // Log the action
                $auditLogger->logAction(
                    $currentUser['id'],
                    'Vendor',
                    $vendorId,
                    'activate',
                    ['status' => 'Suspended'],
                    ['status' => 'Active']
                );
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Vendor activated successfully',
                    'status' => 'Active'
                ]);
                break;
                
            case 'update_profile':
                $businessName = $data['business_name'] ?? null;
                $legalName = $data['legal_name'] ?? null;
                $contactEmail = $data['contact_email'] ?? null;
                $contactPhone = $data['contact_phone'] ?? null;
                
                if ($businessName) $vendor->setBusinessName($businessName);
                if ($legalName) $vendor->setLegalName($legalName);
                if ($contactEmail) $vendor->setContactEmail($contactEmail);
                if ($contactPhone) $vendor->setContactPhone($contactPhone);
                
                $vendorRepo->update($vendor);
                
                // Log the action
                $auditLogger->logUpdate('Vendor', $vendorId, $currentUser['id']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Vendor profile updated successfully'
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}

