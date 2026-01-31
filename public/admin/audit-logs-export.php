<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Database\Connection;
use RentalPlatform\Repositories\AuditLogRepository;
use RentalPlatform\Repositories\UserRepository;

Session::start();
Middleware::requireAdministrator();

$db = Connection::getInstance();
$auditLogRepo = new AuditLogRepository($db);
$userRepo = new UserRepository();

// Build filters (same as main page)
$filters = [];

if (!empty($_GET['user_id'])) {
    $filters['user_id'] = $_GET['user_id'];
}

if (!empty($_GET['entity_type'])) {
    $filters['entity_type'] = $_GET['entity_type'];
}

if (!empty($_GET['action'])) {
    $filters['action'] = $_GET['action'];
}

if (!empty($_GET['start_date'])) {
    try {
        $filters['start_date'] = new DateTime($_GET['start_date']);
    } catch (Exception $e) {
        // Invalid date, ignore
    }
}

if (!empty($_GET['end_date'])) {
    try {
        $filters['end_date'] = new DateTime($_GET['end_date'] . ' 23:59:59');
    } catch (Exception $e) {
        // Invalid date, ignore
    }
}

// Get all matching audit logs (no pagination for export)
$auditLogs = $auditLogRepo->search($filters, 10000, 0);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="audit-logs-' . date('Y-m-d-His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, [
    'Log ID',
    'Timestamp',
    'User ID',
    'Username',
    'User Role',
    'Entity Type',
    'Entity ID',
    'Action',
    'IP Address',
    'Old Value',
    'New Value'
]);

// Write data rows
foreach ($auditLogs as $log) {
    $user = $log->getUserId() ? $userRepo->findById($log->getUserId()) : null;
    
    fputcsv($output, [
        $log->getId(),
        $log->getTimestamp()->format('Y-m-d H:i:s'),
        $log->getUserId() ?? 'System',
        $user ? $user->getUsername() : 'System',
        $user ? $user->getRole() : 'N/A',
        $log->getEntityType(),
        $log->getEntityId(),
        $log->getAction(),
        $log->getIpAddress(),
        $log->getOldValue() ? json_encode($log->getOldValue()) : '',
        $log->getNewValue() ? json_encode($log->getNewValue()) : ''
    ]);
}

fclose($output);
exit;
