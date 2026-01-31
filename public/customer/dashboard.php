<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;

Session::start();
Middleware::requireRole(User::ROLE_CUSTOMER);

$username = Session::getUsername();

$pageTitle = 'Customer Dashboard';
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
                <h3 class="text-sm font-medium text-green-800">Welcome to RentalHub!</h3>
                <p class="mt-1 text-sm text-green-700">Your account has been created successfully. Start browsing products to rent.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Welcome back, <?= htmlspecialchars($username) ?>!</h1>
    <p class="mt-2 text-gray-600">Discover amazing products to rent from trusted vendors.</p>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <a href="/Multi-Vendor-Rental-System/public/customer/products.php" 
       class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-all group">
        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-blue-200 transition-colors">
            <i class="fas fa-search text-blue-600 text-xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Browse Products</h3>
        <p class="text-gray-600 text-sm">Explore our wide range of rental products</p>
    </a>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 opacity-60 cursor-not-allowed">
        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-shopping-cart text-green-600 text-xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">My Cart</h3>
        <p class="text-gray-600 text-sm">Coming soon - View items in your shopping cart</p>
        <span class="inline-block mt-2 text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Task 8 - Not Yet Implemented</span>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 opacity-60 cursor-not-allowed">
        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-list text-purple-600 text-xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">My Orders</h3>
        <p class="text-gray-600 text-sm">Coming soon - Track your rental orders</p>
        <span class="inline-block mt-2 text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Task 12+ - Not Yet Implemented</span>
    </div>
</div>

<!-- Coming Soon Section -->
<div class="bg-white rounded-xl shadow-sm p-12 text-center border border-gray-100">
    <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-rocket text-primary-600 text-3xl"></i>
    </div>
    <h2 class="text-2xl font-bold text-gray-900 mb-4">More Features Coming Soon!</h2>
    <p class="text-gray-600 max-w-2xl mx-auto">
        We're working hard to bring you an amazing rental experience. Browse products, manage your cart, and track orders will be available soon.
    </p>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>
