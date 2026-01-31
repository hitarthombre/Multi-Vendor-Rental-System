<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../src/Database/Connection.php';
require_once '../../src/Helpers/UUID.php';
require_once '../../src/Models/Notification.php';
require_once '../../src/Repositories/NotificationRepository.php';
require_once '../../src/Repositories/UserRepository.php';
require_once '../../src/Services/EmailService.php';
require_once '../../src/Services/NotificationService.php';

use RentalPlatform\Services\NotificationService;

// For demo purposes, use hardcoded admin ID
// In a real application, this would come from the session and check admin permissions
$adminId = 'demo-admin-789';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$notificationService = new NotificationService();

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'statistics':
                    $stats = $notificationService->getStatistics();
                    echo json_encode([
                        'success' => true,
                        'data' => $stats
                    ]);
                    break;
                    
                case 'health':
                    $health = $notificationService->healthCheck();
                    echo json_encode([
                        'success' => true,
                        'data' => $health
                    ]);
                    break;
                    
                case 'by_event_type':
                    $eventType = $_GET['event_type'] ?? '';
                    $status = $_GET['status'] ?? null;
                    $limit = (int)($_GET['limit'] ?? 50);
                    
                    if (empty($eventType)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing event_type parameter'
                        ]);
                        break;
                    }
                    
                    $notifications = $notificationService->getNotificationsByEventType($eventType, $status, $limit);
                    echo json_encode([
                        'success' => true,
                        'data' => $notifications
                    ]);
                    break;
                    
                case 'user_notifications':
                    $userId = $_GET['user_id'] ?? '';
                    $limit = (int)($_GET['limit'] ?? 100);
                    
                    if (empty($userId)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing user_id parameter'
                        ]);
                        break;
                    }
                    
                    $notifications = $notificationService->getUserNotifications($userId, $limit);
                    echo json_encode([
                        'success' => true,
                        'data' => $notifications
                    ]);
                    break;
                    
                default:
                    // Default to statistics
                    $stats = $notificationService->getStatistics();
                    echo json_encode([
                        'success' => true,
                        'data' => $stats
                    ]);
                    break;
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'process_pending':
                    $limit = (int)($_POST['limit'] ?? 50);
                    $processed = $notificationService->processPendingNotifications($limit);
                    echo json_encode([
                        'success' => true,
                        'message' => "Processed {$processed} pending notifications",
                        'data' => ['processed' => $processed]
                    ]);
                    break;
                    
                case 'retry_failed':
                    $limit = (int)($_POST['limit'] ?? 20);
                    $backoffMinutes = (int)($_POST['backoff_minutes'] ?? 30);
                    $retried = $notificationService->retryFailedNotifications($limit, $backoffMinutes);
                    echo json_encode([
                        'success' => true,
                        'message' => "Retried {$retried} failed notifications",
                        'data' => ['retried' => $retried]
                    ]);
                    break;
                    
                case 'cleanup_old':
                    $daysOld = (int)($_POST['days_old'] ?? 30);
                    $deleted = $notificationService->cleanupOldNotifications($daysOld);
                    echo json_encode([
                        'success' => true,
                        'message' => "Deleted {$deleted} old notifications",
                        'data' => ['deleted' => $deleted]
                    ]);
                    break;
                    
                case 'send_test':
                    $userId = $_POST['user_id'] ?? '';
                    
                    if (empty($userId)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing user_id parameter'
                        ]);
                        break;
                    }
                    
                    $success = $notificationService->sendTestNotification($userId);
                    echo json_encode([
                        'success' => $success,
                        'message' => $success ? 'Test notification sent successfully' : 'Test notification failed'
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