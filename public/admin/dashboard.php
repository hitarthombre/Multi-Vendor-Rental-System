<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Database\Connection;
use RentalPlatform\Repositories\UserRepository;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Repositories\ProductRepository;

Session::start();
Middleware::requireAuthentication();
Middleware::requireRole('Administrator');

$db = Connection::getInstance();
$userRepo = new UserRepository();
$vendorRepo = new VendorRepository();
$productRepo = new ProductRepository($db);

// Get statistics
$totalUsers = count($userRepo->findAll());
$totalVendors = count($vendorRepo->findAll());
$totalProducts = count($productRepo->findAll());
$activeVendors = count($vendorRepo->findByStatus('Active'));

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
                <p class="text-sm text-gray-500">Platform Status</p>
                <p class="text-xl font-bold text-green-600 mt-2">Operational</p>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
        </div>
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
            <a href="/Multi-Vendor-Rental-System/public/admin/audit-logs.php" 
               class="flex items-center justify-center px-4 py-3 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition-colors">
                <i class="fas fa-clipboard-list mr-2"></i>Audit Logs
            </a>
            <a href="/Multi-Vendor-Rental-System/public/admin/settings.php" 
               class="flex items-center justify-center px-4 py-3 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="fas fa-cog mr-2"></i>Settings
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
