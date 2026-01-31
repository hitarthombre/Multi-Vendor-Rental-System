<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Database\Connection;
use RentalPlatform\Repositories\VendorRepository;

Session::start();
Middleware::requireAdministrator();

$db = Connection::getInstance();
$vendorRepo = new VendorRepository();

// Get all vendors
$stmt = $db->query("
    SELECT v.*, u.username, u.email, u.created_at as user_created_at,
           COUNT(DISTINCT p.id) as product_count
    FROM vendors v
    JOIN users u ON v.user_id = u.id
    LEFT JOIN products p ON v.id = p.vendor_id
    GROUP BY v.id
    ORDER BY v.created_at DESC
");
$vendors = $stmt->fetchAll();

$pageTitle = 'Manage Vendors';
$showNav = true;
$showContainer = true;
$showFooter = true;

ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Vendors</h1>
            <p class="text-muted-foreground mt-2">Manage all vendor accounts and businesses</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-4 md:grid-cols-3">
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Total Vendors</p>
                    <p class="text-2xl font-bold"><?= count($vendors) ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-store text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Total Products</p>
                    <p class="text-2xl font-bold"><?= array_sum(array_column($vendors, 'product_count')) ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-box text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Avg Products/Vendor</p>
                    <p class="text-2xl font-bold"><?= count($vendors) > 0 ? round(array_sum(array_column($vendors, 'product_count')) / count($vendors), 1) : 0 ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendors Table -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">All Vendors</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Products</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php foreach ($vendors as $vendor): ?>
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-medium"><?= htmlspecialchars($vendor['business_name']) ?></div>
                                    <div class="text-sm text-muted-foreground">@<?= htmlspecialchars($vendor['username']) ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div><?= htmlspecialchars($vendor['email']) ?></div>
                                    <?php if (!empty($vendor['phone'])): ?>
                                        <div class="text-muted-foreground"><?= htmlspecialchars($vendor['phone']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?= $vendor['product_count'] ?> products
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">
                                <?= date('M d, Y', strtotime($vendor['user_created_at'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <a href="/Multi-Vendor-Rental-System/public/admin/vendor-details.php?id=<?= $vendor['id'] ?>" 
                                   class="text-primary hover:text-primary/80 text-sm font-medium">
                                    View Details →
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($vendors)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-muted-foreground">
                                <i class="fas fa-store text-4xl mb-4 block"></i>
                                <p>No vendors found</p>
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
