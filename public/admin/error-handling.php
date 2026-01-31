<?php
/**
 * Admin Error Handling Dashboard
 * 
 * Provides interface for managing error handling and admin interventions
 * Tasks 28.5, 28.9 - Refund failures, error logging, admin interventions
 */

require_once '../../src/Auth/Session.php';
require_once '../../src/Services/ErrorHandlingService.php';
require_once '../../src/Repositories/AuditLogRepository.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Services\ErrorHandlingService;
use RentalPlatform\Repositories\AuditLogRepository;

// Check admin authentication
Session::start();
if (!Session::isLoggedIn() || Session::getUserRole() !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$errorHandlingService = new ErrorHandlingService();
$auditLogRepo = new AuditLogRepository();

// Handle admin actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'resolve_intervention':
            $interventionId = $_POST['intervention_id'] ?? '';
            $resolution = $_POST['resolution'] ?? '';
            
            if ($interventionId && $resolution) {
                try {
                    $errorHandlingService->resolveAdminIntervention(
                        $interventionId,
                        Session::getUserId(),
                        $resolution
                    );
                    $message = 'Intervention resolved successfully';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Error resolving intervention: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
            break;
    }
}

// Get data for dashboard
$pendingInterventions = $errorHandlingService->getPendingAdminInterventions();
$recentErrors = $auditLogRepo->findByAction('error_logged', 50);
$errorStats = $auditLogRepo->getErrorStatistics();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Handling Dashboard - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Error Handling Dashboard</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">← Back to Admin Dashboard</a>
                        <span class="text-sm text-gray-500">Admin: <?= htmlspecialchars(Session::getUsername()) ?></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-md <?= $messageType === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Error Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-bold">!</span>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Pending Interventions</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?= count($pendingInterventions) ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-bold">⚠</span>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Recent Errors (24h)</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?= count(array_filter($recentErrors, fn($e) => strtotime($e->getCreatedAt()) > time() - 86400)) ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-bold">💰</span>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Refund Failures</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?= count(array_filter($recentErrors, fn($e) => strpos($e->getNewData()['error_type'] ?? '', 'refund_failure') !== false)) ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-bold">✓</span>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">System Health</dt>
                                    <dd class="text-lg font-medium text-gray-900">Good</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Admin Interventions -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md mb-8">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Pending Admin Interventions</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Issues requiring immediate admin attention</p>
                </div>
                <div class="border-t border-gray-200">
                    <?php if (empty($pendingInterventions)): ?>
                        <div class="px-4 py-5 sm:px-6 text-center text-gray-500">
                            No pending interventions
                        </div>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($pendingInterventions as $intervention): ?>
                                <?php $data = $intervention->getNewData(); ?>
                                <li class="px-4 py-4 sm:px-6" x-data="{ showDetails: false }">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    <?= ($data['priority'] ?? 'medium') === 'high' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                                    <?= ucfirst($data['priority'] ?? 'medium') ?> Priority
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= ucwords(str_replace('_', ' ', $data['intervention_type'] ?? 'Unknown')) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    Entity: <?= htmlspecialchars($intervention->getEntityId()) ?> | 
                                                    Created: <?= date('M j, Y H:i', strtotime($intervention->getCreatedAt())) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button @click="showDetails = !showDetails" 
                                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                Details
                                            </button>
                                            <button onclick="resolveIntervention('<?= $intervention->getId() ?>')"
                                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm">
                                                Resolve
                                            </button>
                                        </div>
                                    </div>
                                    <div x-show="showDetails" x-collapse class="mt-4 bg-gray-50 p-4 rounded">
                                        <pre class="text-xs text-gray-600 whitespace-pre-wrap"><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) ?></pre>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent System Errors -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent System Errors</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Latest error logs for monitoring</p>
                </div>
                <div class="border-t border-gray-200">
                    <?php if (empty($recentErrors)): ?>
                        <div class="px-4 py-5 sm:px-6 text-center text-gray-500">
                            No recent errors
                        </div>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach (array_slice($recentErrors, 0, 20) as $error): ?>
                                <?php $data = $error->getNewData(); ?>
                                <li class="px-4 py-4 sm:px-6" x-data="{ showDetails: false }">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <?= ucwords(str_replace('_', ' ', $data['error_type'] ?? 'Error')) ?>
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($error->getEntityType()) ?> - <?= htmlspecialchars($error->getEntityId()) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?= date('M j, Y H:i:s', strtotime($error->getCreatedAt())) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <button @click="showDetails = !showDetails" 
                                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                            Details
                                        </button>
                                    </div>
                                    <div x-show="showDetails" x-collapse class="mt-4 bg-gray-50 p-4 rounded">
                                        <pre class="text-xs text-gray-600 whitespace-pre-wrap"><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) ?></pre>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Resolution Modal -->
    <div id="resolutionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Resolve Intervention</h3>
            <form method="POST">
                <input type="hidden" name="action" value="resolve_intervention">
                <input type="hidden" name="intervention_id" id="interventionId">
                <div class="mb-4">
                    <label for="resolution" class="block text-sm font-medium text-gray-700">Resolution Details</label>
                    <textarea name="resolution" id="resolution" rows="4" required
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                              placeholder="Describe how this issue was resolved..."></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeResolutionModal()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
                        Resolve
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function resolveIntervention(interventionId) {
            document.getElementById('interventionId').value = interventionId;
            document.getElementById('resolutionModal').classList.remove('hidden');
            document.getElementById('resolutionModal').classList.add('flex');
        }

        function closeResolutionModal() {
            document.getElementById('resolutionModal').classList.add('hidden');
            document.getElementById('resolutionModal').classList.remove('flex');
            document.getElementById('resolution').value = '';
        }

        // Auto-refresh every 30 seconds
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>