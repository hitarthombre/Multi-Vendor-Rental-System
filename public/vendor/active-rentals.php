<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Models\Order;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Repositories\OrderRepository;
use RentalPlatform\Repositories\OrderItemRepository;
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

// Get active rentals for this vendor
$orderRepo = new OrderRepository();
$orderItemRepo = new OrderItemRepository();
$userRepo = new UserRepository();

$activeRentals = $orderRepo->getActiveRentals($vendor->getId());

// Get detailed information for each active rental
$rentalDetails = [];
foreach ($activeRentals as $rental) {
    $customer = $userRepo->findById($rental->getCustomerId());
    $items = $orderItemRepo->findWithProductDetails($rental->getId());
    
    $rentalDetails[] = [
        'order' => $rental,
        'customer' => $customer,
        'items' => $items
    ];
}

$pageTitle = 'Active Rentals';
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Active Rentals</h1>
            <p class="mt-2 text-gray-600">Manage your ongoing rental orders and track rental periods.</p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded-lg font-semibold">
                <i class="fas fa-play-circle mr-2"></i>
                <?= count($activeRentals) ?> Active Rentals
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total Active Rentals -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Active Rentals</p>
                <p class="text-3xl font-bold text-green-600 mt-2"><?= count($activeRentals) ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-play-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Revenue from Active -->
    <?php 
    $totalActiveRevenue = array_sum(array_map(fn($rental) => $rental->getTotalAmount(), $activeRentals));
    ?>
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Active Revenue</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">₹<?= number_format($totalActiveRevenue, 2) ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-rupee-sign text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Deposits Held -->
    <?php 
    $totalDepositsHeld = array_sum(array_map(fn($rental) => $rental->getDepositAmount(), $activeRentals));
    ?>
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Deposits Held</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2">₹<?= number_format($totalDepositsHeld, 2) ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-shield-alt text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Active Rentals List -->
<div class="space-y-6">
    <?php if (empty($activeRentals)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-play-circle text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No active rentals</h3>
            <p class="text-gray-500 mb-6">Active rentals will appear here once customers' orders are approved and activated</p>
            <a href="/Multi-Vendor-Rental-System/public/vendor/approval-queue.php" 
               class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-clock mr-2"></i>
                Check Approval Queue
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($rentalDetails as $detail): ?>
            <?php 
            $order = $detail['order'];
            $customer = $detail['customer'];
            $items = $detail['items'];
            ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Order Header -->
                <div class="bg-green-50 border-b border-green-100 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-play-circle text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($order->getOrderNumber()) ?></h3>
                                <p class="text-sm text-gray-600">
                                    Customer: <?= $customer ? htmlspecialchars($customer->getUsername()) : 'Unknown' ?>
                                    <?php if ($customer): ?>
                                        <span class="text-gray-400">•</span>
                                        <?= htmlspecialchars($customer->getEmail()) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-gray-900">₹<?= number_format($order->getTotalAmount(), 2) ?></div>
                            <?php if ($order->getDepositAmount() > 0): ?>
                                <div class="text-sm text-yellow-600">
                                    <i class="fas fa-shield-alt mr-1"></i>
                                    Deposit: ₹<?= number_format($order->getDepositAmount(), 2) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($items as $item): ?>
                            <div class="flex items-start space-x-4 p-4 bg-gray-50 rounded-lg">
                                <!-- Product Image Placeholder -->
                                <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <?php if (!empty($item['product_images'])): ?>
                                        <img src="<?= htmlspecialchars($item['product_images'][0]) ?>" 
                                             alt="<?= htmlspecialchars($item['product_name']) ?>"
                                             class="w-full h-full object-cover rounded-lg">
                                    <?php else: ?>
                                        <i class="fas fa-box text-gray-400"></i>
                                    <?php endif; ?>
                                </div>

                                <!-- Product Details -->
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-900 truncate">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    </h4>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Quantity: <?= $item['quantity'] ?> × ₹<?= number_format($item['unit_price'], 2) ?>
                                    </p>
                                    <div class="text-sm font-medium text-gray-900 mt-1">
                                        Total: ₹<?= number_format($item['total_price'], 2) ?>
                                    </div>
                                </div>

                                <!-- Rental Period -->
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-medium text-gray-900">Rental Period</div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        <div class="flex items-center">
                                            <i class="fas fa-calendar-alt mr-1 text-green-500"></i>
                                            <?= date('M d, Y', strtotime($item['start_datetime'])) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= date('H:i', strtotime($item['start_datetime'])) ?>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-600 mt-2">
                                        <div class="flex items-center">
                                            <i class="fas fa-calendar-check mr-1 text-red-500"></i>
                                            <?= date('M d, Y', strtotime($item['end_datetime'])) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= date('H:i', strtotime($item['end_datetime'])) ?>
                                        </div>
                                    </div>
                                    <div class="text-xs text-blue-600 mt-2 font-medium">
                                        <?= $item['duration_value'] ?> <?= $item['duration_unit'] ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Actions -->
                <div class="bg-gray-50 border-t border-gray-100 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-clock mr-1"></i>
                            Order created: <?= date('M d, Y H:i', strtotime($order->getCreatedAt())) ?>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="/Multi-Vendor-Rental-System/public/vendor/order-details.php?id=<?= $order->getId() ?>" 
                               class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <i class="fas fa-eye mr-2"></i>
                                View Details
                            </a>
                            <a href="/Multi-Vendor-Rental-System/public/vendor/rental-completion.php?id=<?= $order->getId() ?>" 
                               class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                <i class="fas fa-check-circle mr-2"></i>
                                Mark as Completed
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Back to Orders -->
<div class="mt-8 text-center">
    <a href="/Multi-Vendor-Rental-System/public/vendor/orders.php" 
       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>
        Back to All Orders
    </a>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>