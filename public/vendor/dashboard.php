<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Models\Order;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Database\Connection;

Session::start();
Middleware::requireRole(User::ROLE_VENDOR);

$userId = Session::getUserId();
$username = Session::getUsername();

// Get vendor profile
$vendorRepo = new VendorRepository();
$vendor = $vendorRepo->findByUserId($userId);

if (!$vendor) {
    // Better error message for debugging
    error_log("Vendor profile not found for user_id: {$userId}");
    die('Vendor profile not found. Please <a href="/Multi-Vendor-Rental-System/public/logout.php">log out</a> and log in again. If the problem persists, contact support.');
}

// Get vendor branding
$brandColor = $vendor->getBrandColor() ?? '#3b82f6';
$vendorLogo = $vendor->getLogo();

// Get statistics
$productRepo = new ProductRepository();
$db = Connection::getInstance();

// Total products
$stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE vendor_id = :vendor_id");
$stmt->execute([':vendor_id' => $vendor->getId()]);
$totalProducts = $stmt->fetchColumn();

// Active products
$stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE vendor_id = :vendor_id AND status = 'Active'");
$stmt->execute([':vendor_id' => $vendor->getId()]);
$activeProducts = $stmt->fetchColumn();

// Get order statistics using OrderRepository
$orderRepo = new \RentalPlatform\Repositories\OrderRepository();
$orderStats = $orderRepo->getVendorStatistics($vendor->getId());

// Calculate totals
$totalOrders = array_sum(array_column($orderStats, 'count'));
$totalRevenue = array_sum(array_column($orderStats, 'total_amount'));
$pendingApprovals = $orderStats[Order::STATUS_PENDING_VENDOR_APPROVAL]['count'] ?? 0;
$activeRentals = $orderStats[Order::STATUS_ACTIVE_RENTAL]['count'] ?? 0;

// Get recent orders for display
$recentOrders = $orderRepo->findByVendorId($vendor->getId());
$recentOrders = array_slice($recentOrders, 0, 5); // Show only 5 most recent

// Recent products
$stmt = $db->prepare("SELECT * FROM products WHERE vendor_id = :vendor_id ORDER BY created_at DESC LIMIT 5");
$stmt->execute([':vendor_id' => $vendor->getId()]);
$recentProducts = $stmt->fetchAll();

$pageTitle = 'Vendor Dashboard';
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Dynamic Branding Styles -->
<style>
:root {
    --vendor-brand-color: <?= htmlspecialchars($brandColor) ?>;
    --vendor-brand-light: <?= htmlspecialchars($brandColor) ?>20;
    --vendor-brand-dark: <?= htmlspecialchars($brandColor) ?>dd;
}

.brand-bg { background-color: var(--vendor-brand-color) !important; }
.brand-text { color: var(--vendor-brand-color) !important; }
.brand-border { border-color: var(--vendor-brand-color) !important; }
.brand-bg-light { background-color: var(--vendor-brand-light) !important; }
.brand-hover:hover { background-color: var(--vendor-brand-dark) !important; }
</style>

<!-- Vendor Header with Logo -->
<?php if ($vendorLogo): ?>
<div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center space-x-4">
        <div class="w-16 h-16 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
            <img src="/Multi-Vendor-Rental-System/public<?= htmlspecialchars($vendorLogo) ?>" 
                 alt="<?= htmlspecialchars($vendor->getBusinessName()) ?> Logo" 
                 class="max-w-full max-h-full object-contain">
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($vendor->getBusinessName()) ?></h1>
            <p class="text-gray-600">Welcome back to your dashboard</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Welcome Message -->
<?php if (isset($_GET['welcome'])): ?>
    <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-lg animate-slide-in">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">Welcome to your vendor dashboard!</h3>
                <p class="mt-1 text-sm text-green-700">Your account has been created successfully. Start by adding your first product.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Welcome back, <?= htmlspecialchars($vendor->getBusinessName()) ?>!</h1>
    <p class="mt-2 text-gray-600">Here's what's happening with your business today.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Products -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Products</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= $totalProducts ?></p>
            </div>
            <div class="w-12 h-12 brand-bg-light rounded-lg flex items-center justify-center">
                <i class="fas fa-box brand-text text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="text-gray-500">Manage your inventory</span>
        </div>
    </div>

    <!-- Active Products -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Active Products</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= $activeProducts ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="text-gray-500">Currently available</span>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Orders</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= $totalOrders ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-shopping-cart text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <a href="/Multi-Vendor-Rental-System/public/vendor/orders.php" class="brand-text hover:text-gray-700">
                View all orders <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>

    <!-- Pending Approvals -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2"><?= $pendingApprovals ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <?php if ($pendingApprovals > 0): ?>
                <a href="/Multi-Vendor-Rental-System/public/vendor/approval-queue.php" class="text-yellow-600 hover:text-yellow-700">
                    Review now <i class="fas fa-arrow-right ml-1"></i>
                </a>
            <?php else: ?>
                <span class="text-gray-500">No pending approvals</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Rentals -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Active Rentals</p>
                <p class="text-3xl font-bold text-green-600 mt-2"><?= $activeRentals ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-play-circle text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <?php if ($activeRentals > 0): ?>
                <a href="/Multi-Vendor-Rental-System/public/vendor/active-rentals.php" class="text-green-600 hover:text-green-700">
                    View rentals <i class="fas fa-arrow-right ml-1"></i>
                </a>
            <?php else: ?>
                <span class="text-gray-500">No active rentals</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Total Revenue -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">₹<?= number_format($totalRevenue, 2) ?></p>
            </div>
            <div class="w-12 h-12 brand-bg-light rounded-lg flex items-center justify-center">
                <i class="fas fa-rupee-sign brand-text text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="text-gray-500">All time earnings</span>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="/Multi-Vendor-Rental-System/public/vendor/product-create.php" 
           class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary-500 hover:bg-primary-50 transition-all group">
            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center group-hover:bg-primary-200 transition-colors">
                <i class="fas fa-plus text-primary-600"></i>
            </div>
            <div class="ml-4">
                <p class="font-semibold text-gray-900">Add New Product</p>
                <p class="text-sm text-gray-500">List a new rental item</p>
            </div>
        </a>

        <a href="/Multi-Vendor-Rental-System/public/vendor/approval-queue.php" 
           class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-yellow-500 hover:bg-yellow-50 transition-all group">
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center group-hover:bg-yellow-200 transition-colors">
                <i class="fas fa-clock text-yellow-600"></i>
            </div>
            <div class="ml-4">
                <p class="font-semibold text-gray-900">Approval Queue</p>
                <p class="text-sm text-gray-500"><?= $pendingApprovals ?> orders waiting</p>
            </div>
        </a>

        <a href="/Multi-Vendor-Rental-System/public/vendor/active-rentals.php" 
           class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all group">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                <i class="fas fa-play-circle text-green-600"></i>
            </div>
            <div class="ml-4">
                <p class="font-semibold text-gray-900">Active Rentals</p>
                <p class="text-sm text-gray-500"><?= $activeRentals ?> ongoing rentals</p>
            </div>
        </a>

        <a href="/Multi-Vendor-Rental-System/public/vendor/orders.php" 
           class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:brand-border hover:brand-bg-light transition-all group">
            <div class="w-10 h-10 brand-bg-light rounded-lg flex items-center justify-center group-hover:brand-bg transition-colors">
                <i class="fas fa-list brand-text"></i>
            </div>
            <div class="ml-4">
                <p class="font-semibold text-gray-900">View All Orders</p>
                <p class="text-sm text-gray-500">Manage your orders</p>
            </div>
        </a>
    </div>
</div>

<!-- Reports & Analytics -->
<div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Reports & Analytics</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="/Multi-Vendor-Rental-System/public/vendor/reports.php" 
           class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-all group">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                <i class="fas fa-chart-line text-purple-600"></i>
            </div>
            <div class="ml-4">
                <p class="font-semibold text-gray-900">Business Reports</p>
                <p class="text-sm text-gray-500">Rental volume & performance</p>
            </div>
        </a>

        <a href="/Multi-Vendor-Rental-System/public/vendor/financial-view.php" 
           class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all group">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                <i class="fas fa-chart-bar text-green-600"></i>
            </div>
            <div class="ml-4">
                <p class="font-semibold text-gray-900">Financial Dashboard</p>
                <p class="text-sm text-gray-500">Revenue & payment tracking</p>
            </div>
        </a>
    </div>
</div>


<!-- Recent Orders -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8">
    <div class="p-6 border-b border-gray-100">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">Recent Orders</h2>
            <a href="/Multi-Vendor-Rental-System/public/vendor/orders.php" 
               class="text-sm font-medium text-primary-600 hover:text-primary-700">
                View all <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
    
    <?php if (empty($recentOrders)): ?>
        <div class="p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shopping-cart text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No orders yet</h3>
            <p class="text-gray-500 mb-6">Orders will appear here once customers start renting your products</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recentOrders as $order): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order->getOrderNumber()) ?></div>
                                    <div class="text-sm text-gray-500">ID: <?= substr($order->getId(), 0, 8) ?>...</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?= $order->getStatusColor() ?>-100 text-<?= $order->getStatusColor() ?>-800">
                                    <?= htmlspecialchars($order->getStatusLabel()) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">₹<?= number_format($order->getTotalAmount(), 2) ?></div>
                                <?php if ($order->getDepositAmount() > 0): ?>
                                    <div class="text-sm text-gray-500">Deposit: ₹<?= number_format($order->getDepositAmount(), 2) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($order->getCreatedAt())) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/Multi-Vendor-Rental-System/public/vendor/order-details.php?id=<?= $order->getId() ?>" 
                                   class="text-primary-600 hover:text-primary-900 mr-3">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($order->requiresVendorApproval()): ?>
                                    <a href="/Multi-Vendor-Rental-System/public/vendor/order-review.php?id=<?= $order->getId() ?>" 
                                       class="text-yellow-600 hover:text-yellow-900">
                                        <i class="fas fa-gavel"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Products -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-6 border-b border-gray-100">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">Recent Products</h2>
            <a href="/Multi-Vendor-Rental-System/public/vendor/products.php" 
               class="text-sm font-medium text-primary-600 hover:text-primary-700">
                View all <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
    
    <?php if (empty($recentProducts)): ?>
        <div class="p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-box-open text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No products yet</h3>
            <p class="text-gray-500 mb-6">Start by adding your first rental product</p>
            <a href="/Multi-Vendor-Rental-System/public/vendor/product-create.php" 
               class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Add Product
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recentProducts as $product): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-box text-gray-500"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500">-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($product['status'] === 'Active'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <?= htmlspecialchars($product['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($product['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/Multi-Vendor-Rental-System/public/vendor/product-edit.php?id=<?= $product['id'] ?>" 
                                   class="text-primary-600 hover:text-primary-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="/Multi-Vendor-Rental-System/public/vendor/product-variants.php?product_id=<?= $product['id'] ?>" 
                                   class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-layer-group"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>
