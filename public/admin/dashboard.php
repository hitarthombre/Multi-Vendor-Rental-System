<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Database\Connection;
use RentalPlatform\Repositories\UserRepository;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\OrderRepository;
use RentalPlatform\Services\AdminAnalyticsService;

Session::start();
Middleware::requireAdministrator();

$db = Connection::getInstance();
$userRepo = new UserRepository();
$vendorRepo = new VendorRepository();
$productRepo = new ProductRepository($db);
$orderRepo = new OrderRepository($db);
$analyticsService = new AdminAnalyticsService();

// Get statistics
$stats = $analyticsService->getPlatformOverview();
$totalUsers = $stats['total_users'];
$totalVendors = $stats['total_vendors'];
$totalProducts = $stats['total_products'];
$activeVendors = $stats['active_vendors'];
$totalOrders = $stats['total_orders'];
$pendingOrders = $stats['pending_orders'];
$totalRevenue = $stats['total_revenue'];

$pageTitle = 'Admin Dashboard';
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Administrator Dashboard</h1>
    <p class="mt-2 text-gray-600">Platform overview and management</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Users</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= number_format($totalUsers) ?></p>
            </div>
            <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Vendors</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= number_format($totalVendors) ?></p>
                <p class="text-xs text-green-600 mt-1"><?= $activeVendors ?> active</p>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-store text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Products</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= number_format($totalProducts) ?></p>
            </div>
            <div class="w-14 h-14 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-box text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Orders</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= number_format($totalOrders) ?></p>
                <p class="text-xs text-yellow-600 mt-1"><?= $pendingOrders ?> pending</p>
            </div>
            <div class="w-14 h-14 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-shopping-cart text-yellow-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Revenue and Performance -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Total Revenue</h3>
            <i class="fas fa-rupee-sign text-2xl opacity-80"></i>
        </div>
        <p class="text-4xl font-bold">₹<?= number_format($totalRevenue, 2) ?></p>
        <p class="text-sm opacity-80 mt-2">All-time platform revenue</p>
    </div>
    
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Active Rentals</h3>
            <i class="fas fa-clock text-2xl opacity-80"></i>
        </div>
        <p class="text-4xl font-bold"><?= number_format($stats['active_orders']) ?></p>
        <p class="text-sm opacity-80 mt-2">Currently active rentals</p>
    </div>
    
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Completed</h3>
            <i class="fas fa-check-circle text-2xl opacity-80"></i>
        </div>
        <p class="text-4xl font-bold"><?= number_format($stats['completed_orders']) ?></p>
        <p class="text-sm opacity-80 mt-2">Successfully completed rentals</p>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-bolt text-primary-600 mr-2"></i>Quick Actions
        </h3>
        <div class="grid grid-cols-2 gap-3">
            <a href="/Multi-Vendor-Rental-System/public/admin/users.php" 
               class="flex items-center justify-center px-4 py-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                <i class="fas fa-users mr-2"></i>Manage Users
            </a>
            <a href="/Multi-Vendor-Rental-System/public/admin/vendors.php" 
               class="flex items-center justify-center px-4 py-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors">
                <i class="fas fa-store mr-2"></i>Manage Vendors
            </a>
            <a href="/Multi-Vendor-Rental-System/public/admin/categories.php" 
               class="flex items-center justify-center px-4 py-3 bg-orange-50 text-orange-700 rounded-lg hover:bg-orange-100 transition-colors">
                <i class="fas fa-tags mr-2"></i>Categories
            </a>
            <a href="/Multi-Vendor-Rental-System/public/admin/analytics.php" 
               class="flex items-center justify-center px-4 py-3 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors">
                <i class="fas fa-chart-bar mr-2"></i>Analytics
            </a>
            <a href="/Multi-Vendor-Rental-System/public/admin/orders.php" 
               class="flex items-center justify-center px-4 py-3 bg-pink-50 text-pink-700 rounded-lg hover:bg-pink-100 transition-colors">
                <i class="fas fa-shopping-bag mr-2"></i>Orders
            </a>
            <a href="/Multi-Vendor-Rental-System/public/admin/audit-logs.php" 
               class="flex items-center justify-center px-4 py-3 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition-colors">
                <i class="fas fa-clipboard-list mr-2"></i>Audit Logs
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-info-circle text-primary-600 mr-2"></i>System Information
        </h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Platform Version</span>
                <span class="text-sm font-medium text-gray-900">1.0.0</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">PHP Version</span>
                <span class="text-sm font-medium text-gray-900"><?= phpversion() ?></span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Database</span>
                <span class="text-sm font-medium text-gray-900">MySQL</span>
            </div>
            <div class="flex justify-between items-center py-2">
                <span class="text-sm text-gray-600">Environment</span>
                <span class="text-sm font-medium text-gray-900">Development</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-clock text-primary-600 mr-2"></i>Recent Activity
    </h3>
    <div class="text-center py-8 text-gray-500">
        <i class="fas fa-chart-line text-4xl mb-3 text-gray-300"></i>
        <p>Activity monitoring coming soon</p>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>
