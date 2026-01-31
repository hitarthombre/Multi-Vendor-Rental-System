<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Database\Connection;

Session::start();
Middleware::requireAdministrator();

$db = Connection::getInstance();

// Get user ID from URL
$userId = $_GET['id'] ?? null;

if (!$userId) {
    header('Location: /Multi-Vendor-Rental-System/public/admin/users.php');
    exit;
}

// Get user details
$stmt = $db->prepare("
    SELECT u.*,
           v.id as vendor_id,
           v.business_name,
           v.contact_phone as vendor_phone,
           v.contact_email as vendor_email,
           v.legal_name,
           v.tax_id,
           v.status as vendor_status
    FROM users u
    LEFT JOIN vendors v ON u.id = v.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /Multi-Vendor-Rental-System/public/admin/users.php?error=User not found');
    exit;
}

// Get additional stats based on role
$stats = [];
if ($user['role'] === 'Vendor' && $user['vendor_id']) {
    // Get vendor product count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE vendor_id = ?");
    $stmt->execute([$user['vendor_id']]);
    $stats['products'] = $stmt->fetch()['count'];
    
    // Get active products
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE vendor_id = ? AND status = 'Active'");
    $stmt->execute([$user['vendor_id']]);
    $stats['active_products'] = $stmt->fetch()['count'];
}

// Get recent audit logs for this user
$stmt = $db->prepare("
    SELECT * FROM audit_logs 
    WHERE user_id = ? 
    ORDER BY timestamp DESC 
    LIMIT 10
");
$stmt->execute([$userId]);
$auditLogs = $stmt->fetchAll();

$pageTitle = 'User Details - ' . $user['username'];
$showNav = true;
$showContainer = true;
$showFooter = true;

ob_start();
?>

<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="/Multi-Vendor-Rental-System/public/admin/users.php" 
           class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Users
        </a>
    </div>

    <!-- User Header -->
    <div class="card p-6">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-4">
                <div class="h-16 w-16 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-2xl font-bold">
                    <?= strtoupper(substr($user['username'], 0, 2)) ?>
                </div>
                <div>
                    <h1 class="text-2xl font-bold"><?= htmlspecialchars($user['username']) ?></h1>
                    <?php if ($user['business_name']): ?>
                        <p class="text-muted-foreground"><?= htmlspecialchars($user['business_name']) ?></p>
                    <?php endif; ?>
                    <div class="flex items-center gap-4 mt-2 text-sm">
                        <span class="inline-flex items-center">
                            <i class="fas fa-envelope mr-2 text-muted-foreground"></i>
                            <?= htmlspecialchars($user['email']) ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php
            $roleColors = [
                'Administrator' => 'bg-red-100 text-red-800',
                'Vendor' => 'bg-green-100 text-green-800',
                'Customer' => 'bg-blue-100 text-blue-800'
            ];
            $roleIcons = [
                'Administrator' => 'fa-user-shield',
                'Vendor' => 'fa-store',
                'Customer' => 'fa-user'
            ];
            ?>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $roleColors[$user['role']] ?>">
                <i class="fas <?= $roleIcons[$user['role']] ?> mr-2"></i>
                <?= $user['role'] ?>
            </span>
        </div>
    </div>

    <!-- Stats Grid (for vendors) -->
    <?php if ($user['role'] === 'Vendor' && $user['vendor_id']): ?>
        <div class="grid gap-4 md:grid-cols-3">
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Total Products</p>
                        <p class="text-2xl font-bold"><?= $stats['products'] ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-box text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Active Products</p>
                        <p class="text-2xl font-bold"><?= $stats['active_products'] ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Member Since</p>
                        <p class="text-lg font-bold"><?= date('M Y', strtotime($user['created_at'])) ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-calendar text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Account Information -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Account Information</h2>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm font-medium text-muted-foreground mb-1">Username</dt>
                    <dd class="text-sm">@<?= htmlspecialchars($user['username']) ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground mb-1">Email</dt>
                    <dd class="text-sm"><?= htmlspecialchars($user['email']) ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground mb-1">Role</dt>
                    <dd class="text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $roleColors[$user['role']] ?>">
                            <i class="fas <?= $roleIcons[$user['role']] ?> mr-1"></i>
                            <?= $user['role'] ?>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground mb-1">Account Created</dt>
                    <dd class="text-sm"><?= date('F d, Y \a\t g:i A', strtotime($user['created_at'])) ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground mb-1">User ID</dt>
                    <dd class="text-sm font-mono text-xs"><?= htmlspecialchars($user['id']) ?></dd>
                </div>
                <?php if ($user['vendor_id']): ?>
                    <div>
                        <dt class="text-sm font-medium text-muted-foreground mb-1">Vendor ID</dt>
                        <dd class="text-sm font-mono text-xs"><?= htmlspecialchars($user['vendor_id']) ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <!-- Vendor Business Information (if vendor) -->
    <?php if ($user['role'] === 'Vendor' && $user['vendor_id']): ?>
        <div class="card">
            <div class="p-6 border-b flex items-center justify-between">
                <h2 class="text-lg font-semibold">Business Information</h2>
                <a href="/Multi-Vendor-Rental-System/public/admin/vendor-details.php?id=<?= $user['vendor_id'] ?>" 
                   class="text-sm text-primary hover:text-primary/80 font-medium">
                    View Full Vendor Profile →
                </a>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-muted-foreground mb-1">Business Name</dt>
                        <dd class="text-sm"><?= htmlspecialchars($user['business_name']) ?></dd>
                    </div>
                    <?php if (!empty($user['legal_name'])): ?>
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground mb-1">Legal Name</dt>
                            <dd class="text-sm"><?= htmlspecialchars($user['legal_name']) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($user['vendor_phone'])): ?>
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground mb-1">Business Phone</dt>
                            <dd class="text-sm"><?= htmlspecialchars($user['vendor_phone']) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($user['vendor_email'])): ?>
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground mb-1">Business Email</dt>
                            <dd class="text-sm"><?= htmlspecialchars($user['vendor_email']) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($user['tax_id'])): ?>
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground mb-1">Tax ID</dt>
                            <dd class="text-sm"><?= htmlspecialchars($user['tax_id']) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($user['vendor_status'])): ?>
                        <div>
                            <dt class="text-sm font-medium text-muted-foreground mb-1">Vendor Status</dt>
                            <dd class="text-sm">
                                <?php
                                $statusColors = [
                                    'Active' => 'bg-green-100 text-green-800',
                                    'Suspended' => 'bg-red-100 text-red-800',
                                    'Pending' => 'bg-yellow-100 text-yellow-800'
                                ];
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$user['vendor_status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                    <?= htmlspecialchars($user['vendor_status']) ?>
                                </span>
                            </dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Recent Activity</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Entity Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Entity ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php foreach ($auditLogs as $log): ?>
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?= htmlspecialchars($log['action']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?= htmlspecialchars($log['entity_type']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-xs text-muted-foreground">
                                <?= htmlspecialchars(substr($log['entity_id'], 0, 8)) ?>...
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">
                                <?= date('M d, Y g:i A', strtotime($log['timestamp'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($auditLogs)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-muted-foreground">
                                <i class="fas fa-history text-4xl mb-4 block"></i>
                                <p>No activity recorded yet</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($auditLogs) >= 10): ?>
            <div class="p-4 border-t text-center">
                <a href="/Multi-Vendor-Rental-System/public/admin/audit-logs.php?user_id=<?= $user['id'] ?>" 
                   class="text-sm text-primary hover:text-primary/80 font-medium">
                    View All Activity →
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/modern-base.php';
?>
