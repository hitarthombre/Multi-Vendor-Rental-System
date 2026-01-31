<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Repositories\OrderRepository;
use RentalPlatform\Repositories\OrderItemRepository;
use RentalPlatform\Repositories\UserRepository;
use RentalPlatform\Repositories\ProductRepository;

Session::start();
Middleware::requireRole(User::ROLE_VENDOR);

$userId = Session::getUserId();
$orderId = $_GET['id'] ?? '';

if (empty($orderId)) {
    header('Location: /Multi-Vendor-Rental-System/public/vendor/active-rentals.php');
    exit;
}

// Get vendor profile
$vendorRepo = new VendorRepository();
$vendor = $vendorRepo->findByUserId($userId);

if (!$vendor) {
    die('Vendor profile not found. Please contact support.');
}

// Get order details
$orderRepo = new OrderRepository();
$order = $orderRepo->findById($orderId);

if (!$order || $order->getVendorId() !== $vendor->getId()) {
    header('Location: /Multi-Vendor-Rental-System/public/vendor/active-rentals.php');
    exit;
}

// Check if order is active
if (!$order->isActive()) {
    header('Location: /Multi-Vendor-Rental-System/public/vendor/active-rentals.php');
    exit;
}

// Get customer details
$userRepo = new UserRepository();
$customer = $userRepo->findById($order->getCustomerId());

// Get order items
$orderItemRepo = new OrderItemRepository();
$orderItems = $orderItemRepo->findByOrderId($orderId);

// Get product details for order items
$productRepo = new ProductRepository();
$products = [];
foreach ($orderItems as $item) {
    if (!isset($products[$item->getProductId()])) {
        $products[$item->getProductId()] = $productRepo->findById($item->getProductId());
    }
}

$pageTitle = 'Complete Rental - ' . $order->getOrderNumber();
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Complete Rental</h1>
            <p class="mt-2 text-gray-600">Mark rental as completed and process security deposit</p>
        </div>
        <div class="flex items-center space-x-4">
            <a href="/Multi-Vendor-Rental-System/public/vendor/active-rentals.php" 
               class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-2"></i>Back to Active Rentals
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Order Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Order Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Order Number</label>
                    <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($order->getOrderNumber()) ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                    <p class="text-gray-900"><?= htmlspecialchars($customer ? $customer->getUsername() : 'Unknown') ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                        Active Rental
                    </span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Started</label>
                    <p class="text-gray-900"><?= date('F d, Y \a\t H:i', strtotime($order->getCreatedAt())) ?></p>
                </div>
            </div>
        </div>

        <!-- Rental Items -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Rental Items</h2>
            <div class="space-y-4">
                <?php foreach ($orderItems as $item): ?>
                    <?php $product = $products[$item->getProductId()] ?? null; ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?= htmlspecialchars($product ? $product->getName() : 'Product not found') ?>
                                </h3>
                                <?php if ($product): ?>
                                    <p class="text-gray-600 mt-1"><?= htmlspecialchars($product->getDescription()) ?></p>
                                <?php endif; ?>
                                <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium text-gray-700">Quantity:</span>
                                        <span class="text-gray-900"><?= $item->getQuantity() ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Unit Price:</span>
                                        <span class="text-gray-900">₹<?= number_format($item->getUnitPrice(), 2) ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Rental Period:</span>
                                        <span class="text-gray-900">
                                            <?= date('M d', strtotime($item->getRentalStartDate())) ?> - 
                                            <?= date('M d, Y', strtotime($item->getRentalEndDate())) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Subtotal:</span>
                                        <span class="text-gray-900 font-semibold">₹<?= number_format($item->getTotalPrice(), 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Completion Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Completion Details</h2>
            <form id="completionForm">
                <div class="space-y-6">
                    <!-- Completion Notes -->
                    <div>
                        <label for="completionNotes" class="block text-sm font-medium text-gray-700 mb-2">
                            Completion Notes (Optional)
                        </label>
                        <textarea id="completionNotes" name="completion_notes" rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                  placeholder="Add any notes about the rental completion, condition of items, etc."></textarea>
                    </div>

                    <?php if ($order->getDepositAmount() > 0): ?>
                        <!-- Deposit Processing -->
                        <div class="border border-blue-200 rounded-lg p-4 bg-blue-50">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                Security Deposit Processing
                                <span class="text-blue-600 font-bold">₹<?= number_format($order->getDepositAmount(), 2) ?></span>
                            </h3>
                            
                            <div class="space-y-4">
                                <!-- Release Full Deposit -->
                                <label class="flex items-start">
                                    <input type="radio" name="deposit_action" value="release" checked
                                           class="mt-1 mr-3 text-green-600 focus:ring-green-500">
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Release Full Deposit</span>
                                        <p class="text-sm text-gray-600">No damages or issues found. Return full deposit to customer.</p>
                                    </div>
                                </label>

                                <!-- Apply Penalty -->
                                <label class="flex items-start">
                                    <input type="radio" name="deposit_action" value="penalty"
                                           class="mt-1 mr-3 text-yellow-600 focus:ring-yellow-500">
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Apply Penalty for Damages</span>
                                        <p class="text-sm text-gray-600">Deduct penalty amount from deposit for damages or issues.</p>
                                    </div>
                                </label>

                                <!-- Withhold Full Deposit -->
                                <label class="flex items-start">
                                    <input type="radio" name="deposit_action" value="withhold"
                                           class="mt-1 mr-3 text-red-600 focus:ring-red-500">
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Withhold Full Deposit</span>
                                        <p class="text-sm text-gray-600">Keep entire deposit due to significant damages or violations.</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Penalty Amount Input -->
                            <div id="penaltyAmountSection" class="mt-4 hidden">
                                <label for="penaltyAmount" class="block text-sm font-medium text-gray-700 mb-2">
                                    Penalty Amount <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-gray-500">₹</span>
                                    <input type="number" id="penaltyAmount" name="penalty_amount" 
                                           min="0" max="<?= $order->getDepositAmount() ?>" step="0.01"
                                           class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                           placeholder="0.00">
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Maximum: ₹<?= number_format($order->getDepositAmount(), 2) ?></p>
                            </div>

                            <!-- Reason Input -->
                            <div id="reasonSection" class="mt-4 hidden">
                                <label for="depositReason" class="block text-sm font-medium text-gray-700 mb-2">
                                    Reason for Penalty/Withholding <span class="text-red-500">*</span>
                                </label>
                                <textarea id="depositReason" name="deposit_reason" rows="3" required
                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                          placeholder="Explain the reason for penalty or withholding the deposit..."></textarea>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Order Summary -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Order Summary</h3>
            <div class="space-y-3">
                <?php
                $subtotal = 0;
                foreach ($orderItems as $item) {
                    $subtotal += $item->getTotalPrice();
                }
                ?>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Rental Charges</span>
                    <span class="text-gray-900">₹<?= number_format($subtotal, 2) ?></span>
                </div>
                <?php if ($order->getDepositAmount() > 0): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Security Deposit</span>
                        <span class="text-blue-600 font-semibold">₹<?= number_format($order->getDepositAmount(), 2) ?></span>
                    </div>
                <?php endif; ?>
                <div class="border-t border-gray-200 pt-3">
                    <div class="flex justify-between">
                        <span class="text-base font-semibold text-gray-900">Total Paid</span>
                        <span class="text-base font-semibold text-gray-900">₹<?= number_format($order->getTotalAmount(), 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Panel -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Complete Rental</h3>
            <div class="space-y-4">
                <button onclick="completeRental()" 
                        class="w-full bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition-colors font-semibold">
                    <i class="fas fa-check-circle mr-2"></i>Complete Rental
                </button>
                <div class="p-3 bg-green-50 rounded-lg">
                    <p class="text-xs text-green-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        Completing the rental will release inventory locks and notify the customer.
                        <?php if ($order->getDepositAmount() > 0): ?>
                        The security deposit will be processed according to your selection above.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="/Multi-Vendor-Rental-System/public/vendor/order-details.php?id=<?= $order->getId() ?>" 
                   class="block w-full text-center bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-eye mr-2"></i>View Full Details
                </a>
                <a href="/Multi-Vendor-Rental-System/public/vendor/active-rentals.php" 
                   class="block w-full text-center bg-blue-100 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-200 transition-colors">
                    <i class="fas fa-list mr-2"></i>All Active Rentals
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Handle deposit action radio buttons
document.querySelectorAll('input[name="deposit_action"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const penaltySection = document.getElementById('penaltyAmountSection');
        const reasonSection = document.getElementById('reasonSection');
        const penaltyAmount = document.getElementById('penaltyAmount');
        const depositReason = document.getElementById('depositReason');
        
        if (this.value === 'penalty') {
            penaltySection.classList.remove('hidden');
            reasonSection.classList.remove('hidden');
            penaltyAmount.required = true;
            depositReason.required = true;
        } else if (this.value === 'withhold') {
            penaltySection.classList.add('hidden');
            reasonSection.classList.remove('hidden');
            penaltyAmount.required = false;
            depositReason.required = true;
        } else {
            penaltySection.classList.add('hidden');
            reasonSection.classList.add('hidden');
            penaltyAmount.required = false;
            depositReason.required = false;
        }
    });
});

function completeRental() {
    const form = document.getElementById('completionForm');
    const formData = new FormData(form);
    
    // Get deposit action
    const depositAction = document.querySelector('input[name="deposit_action"]:checked')?.value || 'release';
    
    // Validate penalty inputs
    if (depositAction === 'penalty') {
        const penaltyAmount = parseFloat(document.getElementById('penaltyAmount').value);
        const depositReason = document.getElementById('depositReason').value.trim();
        
        if (!penaltyAmount || penaltyAmount <= 0) {
            alert('Please enter a valid penalty amount.');
            return;
        }
        
        if (penaltyAmount > <?= $order->getDepositAmount() ?>) {
            alert('Penalty amount cannot exceed the deposit amount.');
            return;
        }
        
        if (!depositReason) {
            alert('Please provide a reason for the penalty.');
            return;
        }
    }
    
    if (depositAction === 'withhold') {
        const depositReason = document.getElementById('depositReason').value.trim();
        if (!depositReason) {
            alert('Please provide a reason for withholding the deposit.');
            return;
        }
    }
    
    if (confirm('Are you sure you want to complete this rental? This action cannot be undone.')) {
        const requestData = {
            action: 'complete',
            order_id: '<?= $order->getId() ?>',
            reason: formData.get('completion_notes') || 'Rental completed by vendor',
            release_deposit: depositAction === 'release',
            penalty_amount: depositAction === 'penalty' ? parseFloat(formData.get('penalty_amount') || 0) : 0,
            penalty_reason: formData.get('deposit_reason') || ''
        };
        
        fetch('/Multi-Vendor-Rental-System/public/api/orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Rental completed successfully! Customer has been notified.');
                window.location.href = '/Multi-Vendor-Rental-System/public/vendor/active-rentals.php';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while completing the rental.');
        });
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>