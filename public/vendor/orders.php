<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Repositories\OrderRepository;
use RentalPlatform\Repositories\UserRepository;
use RentalPlatform\Database\Connection;

Session::start();
Middleware::requireRole(User::ROLE_VENDOR);

$userId = Session::getUserId();

// Get vendor profile
$vendorRepo = new VendorRepository();
$vendor = $vendorRepo->findByUserId($userId);

if (!$vendor) {
    die('Vendor profile not found. Please contact support.');
}

// Get orders for this vendor
$orderRepo = new OrderRepository();
$userRepo = new UserRepository();
$orders = $orderRepo->findByVendorId($vendor->getId());

// Get order statistics
$stats = $orderRepo->getVendorStatistics($vendor->getId());

// Calculate totals
$totalOrders = array_sum(array_column($stats, 'count'));
$totalRevenue = array_sum(array_column($stats, 'total_amount'));
$pendingApprovals = $stats['Pending_Vendor_Approval']['count'] ?? 0;
$activeRentals = $stats['Active_Rental']['count'] ?? 0;

// Get customer details for orders
$customerDetails = [];
foreach ($orders as $order) {
    if (!isset($customerDetails[$order->getCustomerId()])) {
        $customer = $userRepo->findById($order->getCustomerId());
        $customerDetails[$order->getCustomerId()] = $customer;
    }
}

$pageTitle = 'My Orders';
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">My Orders</h1>
    <p class="mt-2 text-gray-600">Manage all your rental orders and track their status.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Orders -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Orders</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?= $totalOrders ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Pending Approvals -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2"><?= $pendingApprovals ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
        </div>
        <?php if ($pendingApprovals > 0): ?>
            <div class="mt-4">
                <a href="/Multi-Vendor-Rental-System/public/vendor/approval-queue.php" 
                   class="text-sm font-medium text-yellow-600 hover:text-yellow-700">
                    Review now <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Active Rentals -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Active Rentals</p>
                <p class="text-3xl font-bold text-green-600 mt-2"><?= $activeRentals ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-play-circle text-green-600 text-xl"></i>
            </div>
        </div>
        <?php if ($activeRentals > 0): ?>
            <div class="mt-4">
                <a href="/Multi-Vendor-Rental-System/public/vendor/active-rentals.php" 
                   class="text-sm font-medium text-green-600 hover:text-green-700">
                    View rentals <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Total Revenue -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                <p class="text-3xl font-bold text-purple-600 mt-2">₹<?= number_format($totalRevenue, 2) ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-rupee-sign text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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

        <a href="/Multi-Vendor-Rental-System/public/vendor/financial-view.php" 
           class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-all group">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                <i class="fas fa-chart-line text-purple-600"></i>
            </div>
            <div class="ml-4">
                <p class="font-semibold text-gray-900">Financial View</p>
                <p class="text-sm text-gray-500">View earnings & invoices</p>
            </div>
        </a>
    </div>
</div>

<!-- Orders Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-6 border-b border-gray-100">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">All Orders</h2>
            <div class="flex items-center space-x-4">
                <!-- Status Filter -->
                <select id="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Statuses</option>
                    <option value="Pending_Vendor_Approval">Pending Approval</option>
                    <option value="Active_Rental">Active Rental</option>
                    <option value="Completed">Completed</option>
                    <option value="Rejected">Rejected</option>
                    <option value="Refunded">Refunded</option>
                </select>
            </div>
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="ordersTableBody">
                    <?php foreach ($orders as $order): ?>
                        <?php $customer = $customerDetails[$order->getCustomerId()] ?? null; ?>
                        <tr class="hover:bg-gray-50 transition-colors order-row" data-status="<?= $order->getStatus() ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order->getOrderNumber()) ?></div>
                                    <div class="text-sm text-gray-500">ID: <?= substr($order->getId(), 0, 8) ?>...</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($customer): ?>
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($customer->getUsername()) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($customer->getEmail()) ?></div>
                                <?php else: ?>
                                    <div class="text-sm text-gray-500">Customer not found</div>
                                <?php endif; ?>
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
                                <div class="text-xs text-gray-400"><?= date('H:i', strtotime($order->getCreatedAt())) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/Multi-Vendor-Rental-System/public/vendor/order-details.php?id=<?= $order->getId() ?>" 
                                   class="text-primary-600 hover:text-primary-900 mr-3">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if ($order->requiresVendorApproval()): ?>
                                    <a href="/Multi-Vendor-Rental-System/public/vendor/order-review.php?id=<?= $order->getId() ?>" 
                                       class="text-yellow-600 hover:text-yellow-900">
                                        <i class="fas fa-gavel"></i> Review
                                    </a>
                                <?php elseif ($order->isActive()): ?>
                                    <a href="/Multi-Vendor-Rental-System/public/vendor/rental-completion.php?id=<?= $order->getId() ?>" 
                                       class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-check-circle"></i> Complete
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

<script>
// Status filter functionality
document.getElementById('statusFilter').addEventListener('change', function() {
    const selectedStatus = this.value;
    const rows = document.querySelectorAll('.order-row');
    
    rows.forEach(row => {
        if (selectedStatus === '' || row.dataset.status === selectedStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>