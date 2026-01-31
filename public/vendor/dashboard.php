<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
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
    die('Vendor profile not found. Please contact support.');
}

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

// Total orders (placeholder - will be implemented later)
$totalOrders = 0;

// Total revenue (placeholder - will be implemented later)
$totalRevenue = 0;

// Recent products
$stmt = $db->prepare("SELECT * FROM products WHERE vendor_id = :vendor_id ORDER BY created_at DESC LIMIT 5");
$stmt->execute([':vendor_id' => $vendor->getId()]);
$recentProducts = $stmt->fetchAll();

$pageTitle = 'Vendor Dashboard';
$showNav = true;
$showContainer = true;

ob_start();
?>

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
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-box text-blue-600 text-xl"></i>
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
            <span class="text-gray-500">All time orders</span>
        </div>
    </div>

    <!-- Total Revenue -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">₹<?= number_format($totalRevenue, 2) ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-rupee-sign text-yellow-600 text-xl"></i>
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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

        <a href="/Multi-Vendor-Rental-System/public/vendor/products.php" 
           class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary-500 hover:bg-primary-50 transition-all group">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                <i class="fas fa-list text-blue-600"></i>
            </div>
            <div class="ml-4">
                <p class="font-semibold text-gray-900">View All Products</p>
                <p class="text-sm text-gray-500">Manage your inventory</p>
            </div>
        </a>

        <a href="/Multi-Vendor-Rental-System/public/vendor/orders.php" 
           class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary-500 hover:bg-primary-50 transition-all group">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                <i class="fas fa-shopping-bag text-green-600"></i>
            </div>
            <div class="ml-4">
                <p class="font-semibold text-gray-900">View Orders</p>
                <p class="text-sm text-gray-500">Check rental orders</p>
            </div>
        </a>
    </div>
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
