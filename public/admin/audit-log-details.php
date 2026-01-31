<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Database\Connection;
use RentalPlatform\Repositories\AuditLogRepository;
use RentalPlatform\Repositories\UserRepository;

Session::start();
Middleware::requireAuthentication();
Middleware::requireRole('Administrator');

$db = Connection::getInstance();
$auditLogRepo = new AuditLogRepository($db);
$userRepo = new UserRepository();

$logId = $_GET['id'] ?? '';

if (empty($logId)) {
    echo '<div class="text-center text-red-600"><i class="fas fa-exclamation-circle mr-2"></i>Invalid log ID</div>';
    exit;
}

$log = $auditLogRepo->findById($logId);

if (!$log) {
    echo '<div class="text-center text-red-600"><i class="fas fa-exclamation-circle mr-2"></i>Audit log not found</div>';
    exit;
}

$user = $log->getUserId() ? $userRepo->findById($log->getUserId()) : null;
$changes = $log->getChanges();
?>

<div class="space-y-6">
    <!-- Basic Information -->
    <div>
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Basic Information</h4>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500">Log ID</p>
                <p class="text-sm font-mono text-gray-900 mt-1"><?= htmlspecialchars($log->getId()) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Timestamp</p>
                <p class="text-sm text-gray-900 mt-1"><?= $log->getTimestamp()->format('Y-m-d H:i:s') ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500">User</p>
                <?php if ($user): ?>
                    <p class="text-sm text-gray-900 mt-1">
                        <?= htmlspecialchars($user->getUsername()) ?> 
                        <span class="text-xs text-gray-500">(<?= htmlspecialchars($user->getRole()) ?>)</span>
                    </p>
                <?php else: ?>
                    <p class="text-sm text-gray-500 italic mt-1">System</p>
                <?php endif; ?>
            </div>
            <div>
                <p class="text-xs text-gray-500">IP Address</p>
                <p class="text-sm font-mono text-gray-900 mt-1"><?= htmlspecialchars($log->getIpAddress()) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Entity Information -->
    <div>
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Entity Information</h4>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500">Entity Type</p>
                <p class="text-sm text-gray-900 mt-1"><?= htmlspecialchars($log->getEntityType()) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Entity ID</p>
                <p class="text-sm font-mono text-gray-900 mt-1"><?= htmlspecialchars($log->getEntityId()) ?></p>
            </div>
            <div class="col-span-2">
                <p class="text-xs text-gray-500">Action</p>
                <p class="text-sm text-gray-900 mt-1">
                    <span class="px-2 py-1 bg-gray-100 rounded text-xs font-medium">
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $log->getAction()))) ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Changes -->
    <?php if (!empty($changes)): ?>
        <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Changes</h4>
            <div class="space-y-3">
                <?php foreach ($changes as $field => $change): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="text-xs font-medium text-gray-700 mb-2"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $field))) ?></p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Old Value</p>
                                <div class="bg-red-50 border border-red-200 rounded p-2">
                                    <pre class="text-xs text-red-900 whitespace-pre-wrap"><?= htmlspecialchars(json_encode($change['old'], JSON_PRETTY_PRINT)) ?></pre>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">New Value</p>
                                <div class="bg-green-50 border border-green-200 rounded p-2">
                                    <pre class="text-xs text-green-900 whitespace-pre-wrap"><?= htmlspecialchars(json_encode($change['new'], JSON_PRETTY_PRINT)) ?></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Raw Data -->
    <div>
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Raw Data</h4>
        <div class="space-y-3">
            <?php if ($log->getOldValue()): ?>
                <div>
                    <p class="text-xs text-gray-500 mb-2">Old Value (JSON)</p>
                    <div class="bg-gray-50 border border-gray-200 rounded p-3 overflow-x-auto">
                        <pre class="text-xs text-gray-900"><?= htmlspecialchars(json_encode($log->getOldValue(), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($log->getNewValue()): ?>
                <div>
                    <p class="text-xs text-gray-500 mb-2">New Value (JSON)</p>
                    <div class="bg-gray-50 border border-gray-200 rounded p-3 overflow-x-auto">
                        <pre class="text-xs text-gray-900"><?= htmlspecialchars(json_encode($log->getNewValue(), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!$log->getOldValue() && !$log->getNewValue()): ?>
                <p class="text-sm text-gray-500 italic">No additional data available</p>
            <?php endif; ?>
        </div>
    </div>
</div>
