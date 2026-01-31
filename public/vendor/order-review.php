<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Services\OrderService;

Session::start();
Middleware::requireRole(User::ROLE_VENDOR);

$userId = Session::getUserId();
$orderId = $_GET['id'] ?? '';

if (empty($orderId)) {
    header('Location: /Multi-Vendor-Rental-System/public/vendor/orders.php');
    exit;
}

// Get vendor profile
$vendorRepo = new VendorRepository();
$vendor = $vendorRepo->findByUserId($userId);

if (!$vendor) {
    die('Vendor profile not found. Please contact support.');
}

// Get comprehensive order review data
$orderService = new OrderService();
try {
    $reviewData = $orderService->getVendorOrderReviewData($orderId, $vendor->getId());
    $order = (object) $reviewData['order'];
    $orderItems = $reviewData['items'];
    $summary = $reviewData['summary'];
    $customer = $reviewData['customer'] ? (object) $reviewData['customer'] : null;
    $documents = $reviewData['documents'];
    $payment = $reviewData['payment'] ? (object) $reviewData['payment'] : null;
} catch (Exception $e) {
    header('Location: /Multi-Vendor-Rental-System/public/vendor/orders.php?error=' . urlencode($e->getMessage()));
    exit;
}

$pageTitle = 'Order Review - ' . $order->order_number;
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Order Review</h1>
            <p class="mt-2 text-gray-600">Review order details and make approval decision</p>
        </div>
        <div class="flex items-center space-x-4">
            <a href="/Multi-Vendor-Rental-System/public/vendor/orders.php" 
               class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-2"></i>Back to Orders
            </a>
        </div>
    </div>
</div>

<!-- Order Status Alert -->
<?php if ($order->status === 'Pending_Vendor_Approval'): ?>
    <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Action Required</h3>
                <p class="mt-1 text-sm text-yellow-700">This order is waiting for your approval. Please review the details below and make a decision.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Order Status: <?= $order->status_label ?></h3>
                <p class="mt-1 text-sm text-blue-700">This order has already been processed.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Order Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Order Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Order Number</label>
                    <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($order->order_number) ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Order Date</label>
                    <p class="text-gray-900"><?= date('F d, Y \a\t H:i', strtotime($order->created_at)) ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-<?= $order->status_color ?>-100 text-<?= $order->status_color ?>-800">
                        <?= htmlspecialchars($order->status_label) ?>
                    </span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount</label>
                    <p class="text-lg font-semibold text-gray-900">₹<?= number_format($order->total_amount, 2) ?></p>
                    <?php if ($order->deposit_amount > 0): ?>
                        <p class="text-sm text-gray-500">Includes ₹<?= number_format($order->deposit_amount, 2) ?> deposit</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payment Information -->
            <?php if ($payment): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Payment Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                <?= htmlspecialchars($payment->status) ?>
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount Paid</label>
                            <p class="text-gray-900">₹<?= number_format($payment->amount, 2) ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                            <p class="text-gray-900"><?= date('M d, Y H:i', strtotime($payment->verified_at ?: $payment->created_at)) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Customer Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Customer Information</h2>
            <?php if ($customer): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <p class="text-gray-900"><?= htmlspecialchars($customer->username) ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <p class="text-gray-900"><?= htmlspecialchars($customer->email) ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <p class="text-gray-900">Not provided</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Member Since</label>
                        <p class="text-gray-900"><?= date('F Y', strtotime($customer->created_at)) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-gray-500">Customer information not available</p>
            <?php endif; ?>
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Order Items</h2>
            <div class="space-y-4">
                <?php foreach ($orderItems as $item): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?= htmlspecialchars($item['product_name'] ?? 'Product not found') ?>
                                </h3>
                                <?php if (!empty($item['product_description'])): ?>
                                    <p class="text-gray-600 mt-1"><?= htmlspecialchars($item['product_description']) ?></p>
                                <?php endif; ?>
                                <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium text-gray-700">Quantity:</span>
                                        <span class="text-gray-900"><?= $item['quantity'] ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Unit Price:</span>
                                        <span class="text-gray-900">₹<?= number_format($item['unit_price'], 2) ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Rental Period:</span>
                                        <span class="text-gray-900">
                                            <?php if (!empty($item['start_datetime']) && !empty($item['end_datetime'])): ?>
                                                <?= date('M d', strtotime($item['start_datetime'])) ?> - 
                                                <?= date('M d, Y', strtotime($item['end_datetime'])) ?>
                                            <?php else: ?>
                                                Period not available
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Subtotal:</span>
                                        <span class="text-gray-900 font-semibold">₹<?= number_format($item['total_price'], 2) ?></span>
                                    </div>
                                </div>
                                
                                <!-- Duration Information -->
                                <?php if (!empty($item['duration_value']) && !empty($item['duration_unit'])): ?>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <span class="font-medium">Duration:</span>
                                        <?= $item['duration_value'] ?> <?= strtolower($item['duration_unit']) ?><?= $item['duration_value'] > 1 ? 's' : '' ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Uploaded Documents -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Uploaded Documents</h2>
            <?php if (empty($documents)): ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-alt text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-500">No documents uploaded for this order</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($documents as $document): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <?php
                                        $extension = pathinfo($document['file_name'], PATHINFO_EXTENSION);
                                        $iconClass = in_array(strtolower($extension), ['pdf']) ? 'fa-file-pdf' : 'fa-file-image';
                                        ?>
                                        <i class="fas <?= $iconClass ?> text-blue-600"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($document['file_name']) ?></p>
                                        <p class="text-xs text-gray-500">
                                            <?= ucfirst($document['document_type']) ?> • 
                                            Uploaded <?= date('M d, Y', strtotime($document['uploaded_at'])) ?>
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            Size: <?= number_format($document['file_size'] / 1024, 1) ?> KB
                                        </p>
                                    </div>
                                </div>
                                <a href="/Multi-Vendor-Rental-System/public/api/documents.php?action=download&id=<?= $document['id'] ?>" 
                                   class="text-blue-600 hover:text-blue-900" target="_blank" title="Download document">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Action Panel -->
        <?php if ($order->status === 'Pending_Vendor_Approval'): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Approval Decision</h3>
                <div class="space-y-4">
                    <button onclick="approveOrder()" 
                            class="w-full bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition-colors font-semibold">
                        <i class="fas fa-check mr-2"></i>Approve Order
                    </button>
                    <button onclick="showRejectModal()" 
                            class="w-full bg-red-600 text-white px-4 py-3 rounded-lg hover:bg-red-700 transition-colors font-semibold">
                        <i class="fas fa-times mr-2"></i>Reject Order
                    </button>
                </div>
                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        Approving will activate the rental. Rejecting will initiate a refund to the customer.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Order Summary -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Order Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Items (<?= $summary['total_items'] ?>)</span>
                    <span class="text-gray-900">₹<?= number_format($summary['total_amount'], 2) ?></span>
                </div>
                <?php if ($order->deposit_amount > 0): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Security Deposit</span>
                        <span class="text-gray-900">₹<?= number_format($order->deposit_amount, 2) ?></span>
                    </div>
                <?php endif; ?>
                <div class="border-t border-gray-200 pt-3">
                    <div class="flex justify-between">
                        <span class="text-base font-semibold text-gray-900">Total</span>
                        <span class="text-base font-semibold text-gray-900">₹<?= number_format($order->total_amount, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="/Multi-Vendor-Rental-System/public/vendor/orders.php" 
                   class="block w-full text-center bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-list mr-2"></i>View All Orders
                </a>
                <a href="/Multi-Vendor-Rental-System/public/vendor/approval-queue.php" 
                   class="block w-full text-center bg-yellow-100 text-yellow-700 px-4 py-2 rounded-lg hover:bg-yellow-200 transition-colors">
                    <i class="fas fa-clock mr-2"></i>Approval Queue
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Reject Order Modal -->
<div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Reject Order</h3>
                <button onclick="hideRejectModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="rejectForm">
                <div class="mb-4">
                    <label for="rejectionReason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for rejection <span class="text-red-500">*</span>
                    </label>
                    <textarea id="rejectionReason" name="reason" rows="4" required
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Please provide a reason for rejecting this order..."></textarea>
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="hideRejectModal()" 
                            class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        Reject Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveOrder() {
    if (confirm('Are you sure you want to approve this order? This will activate the rental.')) {
        fetch('/Multi-Vendor-Rental-System/public/api/orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'approve',
                order_id: '<?= $order->id ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Order approved successfully!');
                window.location.href = '/Multi-Vendor-Rental-System/public/vendor/orders.php';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while approving the order.');
        });
    }
}

function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}

function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectForm').reset();
}

document.getElementById('rejectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const reason = document.getElementById('rejectionReason').value.trim();
    if (!reason) {
        alert('Please provide a reason for rejection.');
        return;
    }
    
    fetch('/Multi-Vendor-Rental-System/public/api/orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'reject',
            order_id: '<?= $order->id ?>',
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order rejected successfully!');
            window.location.href = '/Multi-Vendor-Rental-System/public/vendor/orders.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while rejecting the order.');
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>