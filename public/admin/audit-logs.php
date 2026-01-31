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

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Build filters
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

// Get audit logs
$auditLogs = $auditLogRepo->search($filters, $perPage, $offset);
$totalLogs = $auditLogRepo->count($filters);
$totalPages = ceil($totalLogs / $perPage);

// Get unique entity types and actions for filters
$entityTypes = ['User', 'Vendor', 'Product', 'Order', 'Payment', 'Invoice', 'Refund', 'Document', 'Category', 'PlatformConfig', 'Permission', 'InventoryLock'];
$actions = ['create', 'update', 'delete', 'status_change', 'approval', 'rejection', 'refund', 'login', 'logout', 'login_failed', 'permission_denied', 'payment_verification', 'inventory_lock', 'inventory_release', 'document_upload', 'document_access', 'invoice_finalize', 'vendor_suspend', 'vendor_activate', 'config_change'];

// Get all users for filter dropdown
$allUsers = $userRepo->findAll();

$pageTitle = 'Audit Logs';
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Page Header -->
<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Audit Logs</h1>
        <p class="mt-2 text-gray-600">System-wide audit trail of sensitive actions</p>
    </div>
    <div class="flex gap-3">
        <button onclick="exportLogs()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-download mr-2"></i>Export CSV
        </button>
        <button onclick="clearFilters()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
            <i class="fas fa-times mr-2"></i>Clear Filters
        </button>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6" x-data="{ showFilters: true }">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-filter mr-2 text-primary-600"></i>Filters
        </h3>
        <button @click="showFilters = !showFilters" class="text-gray-500 hover:text-gray-700">
            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </button>
    </div>
    
    <form method="GET" x-show="showFilters" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">User</label>
            <select id="user_id" name="user_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <option value="">All Users</option>
                <?php foreach ($allUsers as $user): ?>
                    <option value="<?= htmlspecialchars($user->getId()) ?>" <?= (isset($_GET['user_id']) && $_GET['user_id'] === $user->getId()) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user->getUsername()) ?> (<?= htmlspecialchars($user->getRole()) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label for="entity_type" class="block text-sm font-medium text-gray-700 mb-1">Entity Type</label>
            <select id="entity_type" name="entity_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <option value="">All Types</option>
                <?php foreach ($entityTypes as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>" <?= (isset($_GET['entity_type']) && $_GET['entity_type'] === $type) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label for="action" class="block text-sm font-medium text-gray-700 mb-1">Action</label>
            <select id="action" name="action" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <option value="">All Actions</option>
                <?php foreach ($actions as $action): ?>
                    <option value="<?= htmlspecialchars($action) ?>" <?= (isset($_GET['action']) && $_GET['action'] === $action) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $action))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        </div>
        
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Apply Filters
            </button>
        </div>
    </form>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Logs</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($totalLogs) ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-list text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Showing</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= count($auditLogs) ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-eye text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Current Page</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= $page ?> / <?= max(1, $totalPages) ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-file text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Filters Active</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= count($filters) ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-filter text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Audit Logs Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($auditLogs)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                            <p>No audit logs found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($auditLogs as $log): ?>
                        <?php
                        $user = $log->getUserId() ? $userRepo->findById($log->getUserId()) : null;
                        $actionColor = match($log->getAction()) {
                            'create' => 'text-green-600 bg-green-100',
                            'update' => 'text-blue-600 bg-blue-100',
                            'delete' => 'text-red-600 bg-red-100',
                            'approval' => 'text-green-600 bg-green-100',
                            'rejection' => 'text-red-600 bg-red-100',
                            'login' => 'text-blue-600 bg-blue-100',
                            'logout' => 'text-gray-600 bg-gray-100',
                            'login_failed' => 'text-red-600 bg-red-100',
                            'permission_denied' => 'text-orange-600 bg-orange-100',
                            default => 'text-gray-600 bg-gray-100'
                        };
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= $log->getTimestamp()->format('Y-m-d H:i:s') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($user): ?>
                                    <div>
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($user->getUsername()) ?></div>
                                        <div class="text-gray-500 text-xs"><?= htmlspecialchars($user->getRole()) ?></div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-500 italic">System</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= $actionColor ?>">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $log->getAction()))) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div>
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($log->getEntityType()) ?></div>
                                    <div class="text-gray-500 text-xs font-mono"><?= htmlspecialchars(substr($log->getEntityId(), 0, 8)) ?>...</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                <?= htmlspecialchars($log->getIpAddress()) ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="showDetails('<?= htmlspecialchars($log->getId()) ?>')" 
                                        class="text-primary-600 hover:text-primary-800 font-medium">
                                    <i class="fas fa-eye mr-1"></i>View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-medium"><?= $offset + 1 ?></span> to 
                    <span class="font-medium"><?= min($offset + $perPage, $totalLogs) ?></span> of 
                    <span class="font-medium"><?= $totalLogs ?></span> results
                </div>
                
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left mr-1"></i>Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                           class="px-4 py-2 border rounded-lg text-sm font-medium <?= $i === $page ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Next<i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="closeModal(event)">
    <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900">Audit Log Details</h3>
                <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <div id="detailsContent" class="p-6">
            <div class="flex items-center justify-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(logId) {
    const modal = document.getElementById('detailsModal');
    const content = document.getElementById('detailsContent');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Fetch details via AJAX
    fetch(`audit-log-details.php?id=${logId}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<div class="text-center text-red-600"><i class="fas fa-exclamation-circle mr-2"></i>Failed to load details</div>';
        });
}

function closeDetailsModal() {
    const modal = document.getElementById('detailsModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function closeModal(event) {
    if (event.target.id === 'detailsModal') {
        closeDetailsModal();
    }
}

function clearFilters() {
    window.location.href = 'audit-logs.php';
}

function exportLogs() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = 'audit-logs-export.php?' + params.toString();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>
