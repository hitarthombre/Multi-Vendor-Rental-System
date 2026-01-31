<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Database\Connection;

Session::start();
Middleware::requireAdministrator();

$db = Connection::getInstance();

// Get vendor ID from URL
$vendorId = $_GET['id'] ?? null;

if (!$vendorId) {
    header('Location: /Multi-Vendor-Rental-System/public/admin/vendors.php');
    exit;
}

// Get vendor details
$stmt = $db->prepare("
    SELECT v.*, u.username, u.email, u.created_at as user_created_at
    FROM vendors v
    JOIN users u ON v.user_id = u.id
    WHERE v.id = ?
");
$stmt->execute([$vendorId]);
$vendor = $stmt->fetch();

if (!$vendor) {
    header('Location: /Multi-Vendor-Rental-System/public/admin/vendors.php?error=Vendor not found');
    exit;
}

// Get vendor's products
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name,
           COUNT(DISTINCT v.id) as variant_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN variants v ON p.id = v.product_id
    WHERE p.vendor_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$vendorId]);
$products = $stmt->fetchAll();

$pageTitle = 'Vendor Details - ' . $vendor['business_name'];
$showNav = true;
$showContainer = true;
$showFooter = true;

ob_start();
?>

<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="/Multi-Vendor-Rental-System/public/admin/vendors.php" 
           class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Vendors
        </a>
    </div>

    <!-- Vendor Header -->
    <div class="card p-6">
        <div class="flex items-start justify-between">
            <div class="flex items-start gap-4">
                <div class="h-16 w-16 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-2xl font-bold">
                    <?= strtoupper(substr($vendor['business_name'], 0, 2)) ?>
                </div>
                <div>
                    <h1 class="text-2xl font-bold"><?= htmlspecialchars($vendor['business_name']) ?></h1>
                    <p class="text-muted-foreground">@<?= htmlspecialchars($vendor['username']) ?></p>
                    <div class="flex items-center gap-4 mt-2 text-sm">
                        <span class="inline-flex items-center">
                            <i class="fas fa-envelope mr-2 text-muted-foreground"></i>
                            <?= htmlspecialchars($vendor['email']) ?>
                        </span>
                        <?php if (!empty($vendor['phone'])): ?>
                            <span class="inline-flex items-center">
                                <i class="fas fa-phone mr-2 text-muted-foreground"></i>
                                <?= htmlspecialchars($vendor['phone']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <?php
                $statusColors = [
                    'Active' => 'bg-green-100 text-green-800',
                    'Pending' => 'bg-yellow-100 text-yellow-800',
                    'Suspended' => 'bg-red-100 text-red-800'
                ];
                $statusIcons = [
                    'Active' => 'fa-check-circle',
                    'Pending' => 'fa-clock',
                    'Suspended' => 'fa-ban'
                ];
                ?>
                <span id="vendor-status-badge" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusColors[$vendor['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                    <i class="fas <?= $statusIcons[$vendor['status']] ?? 'fa-store' ?> mr-2"></i>
                    <?= htmlspecialchars($vendor['status']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Management Actions -->
    <div class="card p-6">
        <h3 class="text-lg font-semibold mb-4">Vendor Management</h3>
        <div class="flex flex-wrap gap-3">
            <?php if ($vendor['status'] === 'Pending'): ?>
                <button onclick="approveVendor('<?= $vendor['id'] ?>')" 
                        class="btn btn-primary">
                    <i class="fas fa-check mr-2"></i>Approve Vendor
                </button>
                <button onclick="suspendVendor('<?= $vendor['id'] ?>')" 
                        class="btn btn-danger">
                    <i class="fas fa-ban mr-2"></i>Reject/Suspend
                </button>
            <?php elseif ($vendor['status'] === 'Active'): ?>
                <button onclick="suspendVendor('<?= $vendor['id'] ?>')" 
                        class="btn btn-danger">
                    <i class="fas fa-ban mr-2"></i>Suspend Vendor
                </button>
            <?php elseif ($vendor['status'] === 'Suspended'): ?>
                <button onclick="activateVendor('<?= $vendor['id'] ?>')" 
                        class="btn btn-success">
                    <i class="fas fa-check-circle mr-2"></i>Activate Vendor
                </button>
            <?php endif; ?>
            <button onclick="editVendorProfile('<?= $vendor['id'] ?>')" 
                    class="btn btn-secondary">
                <i class="fas fa-edit mr-2"></i>Edit Profile
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid gap-4 md:grid-cols-4">
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Total Products</p>
                    <p class="text-2xl font-bold"><?= count($products) ?></p>
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
                    <p class="text-2xl font-bold"><?= count(array_filter($products, fn($p) => $p['status'] === 'Active')) ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Total Variants</p>
                    <p class="text-2xl font-bold"><?= array_sum(array_column($products, 'variant_count')) ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-layer-group text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Member Since</p>
                    <p class="text-lg font-bold"><?= date('M Y', strtotime($vendor['user_created_at'])) ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center">
                    <i class="fas fa-calendar text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Information -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Business Information</h2>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm font-medium text-muted-foreground mb-1">Business Name</dt>
                    <dd class="text-sm"><?= htmlspecialchars($vendor['business_name']) ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground mb-1">Username</dt>
                    <dd class="text-sm">@<?= htmlspecialchars($vendor['username']) ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground mb-1">Email</dt>
                    <dd class="text-sm"><?= htmlspecialchars($vendor['email']) ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground mb-1">Phone</dt>
                    <dd class="text-sm"><?= !empty($vendor['phone']) ? htmlspecialchars($vendor['phone']) : 'Not provided' ?></dd>
                </div>
                <?php if (!empty($vendor['address'])): ?>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-muted-foreground mb-1">Address</dt>
                        <dd class="text-sm"><?= htmlspecialchars($vendor['address']) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (!empty($vendor['description'])): ?>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-muted-foreground mb-1">Description</dt>
                        <dd class="text-sm"><?= htmlspecialchars($vendor['description']) ?></dd>
                    </div>
                <?php endif; ?>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground mb-1">Account Created</dt>
                    <dd class="text-sm"><?= date('F d, Y \a\t g:i A', strtotime($vendor['user_created_at'])) ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground mb-1">Vendor ID</dt>
                    <dd class="text-sm font-mono text-xs"><?= htmlspecialchars($vendor['id']) ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Products List -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Products (<?= count($products) ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Variants</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php foreach ($products as $product): ?>
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium"><?= htmlspecialchars($product['name']) ?></div>
                                <?php if ($product['description']): ?>
                                    <div class="text-sm text-muted-foreground truncate max-w-md">
                                        <?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?= $product['variant_count'] ?> variant<?= $product['variant_count'] != 1 ? 's' : '' ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $statusColors = [
                                    'Active' => 'bg-green-100 text-green-800',
                                    'Inactive' => 'bg-gray-100 text-gray-800',
                                    'Draft' => 'bg-yellow-100 text-yellow-800'
                                ];
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$product['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                    <?= htmlspecialchars($product['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">
                                <?= date('M d, Y', strtotime($product['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-muted-foreground">
                                <i class="fas fa-box-open text-4xl mb-4 block"></i>
                                <p>No products found for this vendor</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/modern-base.php';
?>


<script>
function approveVendor(vendorId) {
    if (!confirm('Are you sure you want to approve this vendor?')) {
        return;
    }
    
    fetch('/Multi-Vendor-Rental-System/public/api/vendor-management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'approve',
            vendor_id: vendorId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('An error occurred: ' + error.message);
    });
}

function suspendVendor(vendorId) {
    const reason = prompt('Please provide a reason for suspension:');
    if (!reason) {
        return;
    }
    
    fetch('/Multi-Vendor-Rental-System/public/api/vendor-management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'suspend',
            vendor_id: vendorId,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('An error occurred: ' + error.message);
    });
}

function activateVendor(vendorId) {
    if (!confirm('Are you sure you want to activate this vendor?')) {
        return;
    }
    
    fetch('/Multi-Vendor-Rental-System/public/api/vendor-management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'activate',
            vendor_id: vendorId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('An error occurred: ' + error.message);
    });
}

function editVendorProfile(vendorId) {
    alert('Edit profile functionality coming soon!');
}
</script>
