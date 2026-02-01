<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Repositories\CartRepository;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\VariantRepository;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Services\CartService;

Session::start();
Middleware::requireAuth();
Middleware::requireCustomer();

$customerId = Session::getUserId();
$cartRepo = new CartRepository();
$productRepo = new ProductRepository();
$variantRepo = new VariantRepository();
$vendorRepo = new VendorRepository();
$cartService = new CartService();

// Get cart
$cart = $cartRepo->getOrCreateForCustomer($customerId);
$cartItems = $cart->getItems();

// Redirect to cart if empty
if (empty($cartItems)) {
    header('Location: cart.php?error=' . urlencode('Your cart is empty'));
    exit;
}

// Validate cart for checkout
$validationResult = $cartService->validateForCheckout($customerId);
$isValid = $validationResult['valid'];
$validationErrors = $validationResult['errors'] ?? [];

// DEBUG: Log validation result
error_log("Checkout Validation - Customer ID: $customerId");
error_log("Checkout Validation - Is Valid: " . ($isValid ? 'true' : 'false'));
error_log("Checkout Validation - Errors: " . json_encode($validationErrors));

// Group items by vendor
$groupedItems = $cart->groupByVendor();

// Calculate totals
$subtotal = 0;
$totalItems = 0;

// Load product and vendor details for each item
$itemsWithDetails = [];
foreach ($cartItems as $item) {
    $product = $productRepo->findById($item->getProductId());
    $variant = $variantRepo->findById($item->getVariantId());
    
    // Get vendor_id from product
    $vendorId = $product ? $product->getVendorId() : null;
    $vendor = $vendorId ? $vendorRepo->findById($vendorId) : null;
    
    $itemsWithDetails[] = [
        'item' => $item,
        'product' => $product,
        'variant' => $variant,
        'vendor' => $vendor
    ];
    
    $subtotal += $item->getSubtotal();
    $totalItems += $item->getQuantity();
}

$pageTitle = 'Checkout';
$showNav = true;
$showContainer = true;

// Add Razorpay script
$additionalHead = '<script src="https://checkout.razorpay.com/v1/checkout.js"></script>';

ob_start();
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
        <a href="cart.php" class="hover:text-primary-600">Cart</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 font-medium">Checkout</span>
    </div>
    <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
    <p class="mt-2 text-gray-600">Review your order and complete payment</p>
</div>

<!-- DEBUG INFO (Remove after testing) -->
<div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
    <h3 class="font-semibold text-yellow-900 mb-2">Debug Information:</h3>
    <div class="text-sm text-yellow-800 space-y-1">
        <p><strong>Customer ID:</strong> <?= htmlspecialchars($customerId) ?></p>
        <p><strong>Cart Items:</strong> <?= count($cartItems) ?></p>
        <p><strong>Is Valid:</strong> <?= $isValid ? 'TRUE' : 'FALSE' ?></p>
        <p><strong>Validation Errors:</strong> <?= empty($validationErrors) ? 'None' : count($validationErrors) ?></p>
        <?php if (!empty($validationErrors)): ?>
            <ul class="list-disc list-inside ml-4">
                <?php foreach ($validationErrors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($validationErrors)): ?>
    <!-- Validation Errors -->
    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded animate-slide-in">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Cannot proceed with checkout</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    <?php foreach ($validationErrors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-4">
                    <a href="cart.php" class="text-sm font-medium text-red-800 hover:text-red-900">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Return to Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Order Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Cart Items by Vendor -->
        <?php foreach ($groupedItems as $vendorId => $vendorItems): ?>
            <?php
            $vendor = $vendorRepo->findById($vendorId);
            $vendorTotal = 0;
            foreach ($vendorItems as $item) {
                $vendorTotal += $item->getSubtotal();
            }
            ?>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Vendor Header -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-store text-primary-600 mr-2"></i>
                            <h3 class="font-semibold text-gray-900">
                                <?= htmlspecialchars($vendor ? $vendor->getBusinessName() : 'Unknown Vendor') ?>
                            </h3>
                        </div>
                        <div class="text-sm text-gray-600">
                            <?= count($vendorItems) ?> item<?= count($vendorItems) !== 1 ? 's' : '' ?>
                        </div>
                    </div>
                </div>
                
                <!-- Vendor Items -->
                <div class="divide-y divide-gray-200">
                    <?php foreach ($vendorItems as $item): ?>
                        <?php
                        $product = $productRepo->findById($item->getProductId());
                        $variant = $variantRepo->findById($item->getVariantId());
                        ?>
                        
                        <div class="p-6">
                            <div class="flex gap-4">
                                <!-- Product Image -->
                                <div class="flex-shrink-0">
                                    <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden">
                                        <?php if ($product && !empty($product->getImages())): ?>
                                            <img src="<?= htmlspecialchars($product->getImages()[0]) ?>" 
                                                 alt="<?= htmlspecialchars($product->getName()) ?>"
                                                 class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <i class="fas fa-image text-xl"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Product Details -->
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900 mb-1">
                                        <?= htmlspecialchars($product ? $product->getName() : 'Unknown Product') ?>
                                    </h4>
                                    
                                    <?php if ($variant): ?>
                                        <p class="text-sm text-gray-600 mb-2">
                                            SKU: <?= htmlspecialchars($variant->getSku()) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center gap-4 text-sm text-gray-600 mb-2">
                                        <span>
                                            <i class="fas fa-calendar mr-1"></i>
                                            <?= $item->getStartDate()->format('M j, Y') ?> - <?= $item->getEndDate()->format('M j, Y') ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-clock mr-1"></i>
                                            <?= $item->getRentalDuration() ?> day<?= $item->getRentalDuration() !== 1 ? 's' : '' ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-600">
                                            Quantity: <?= $item->getQuantity() ?>
                                        </div>
                                        
                                        <!-- Price -->
                                        <div class="text-right">
                                            <div class="text-sm text-gray-600">₹<?= number_format($item->getPricePerUnit(), 2) ?> / day</div>
                                            <div class="text-lg font-bold text-gray-900">₹<?= number_format($item->getSubtotal(), 2) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Vendor Total -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Vendor Subtotal</span>
                        <span class="text-lg font-bold text-gray-900">₹<?= number_format($vendorTotal, 2) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Important Information -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <h3 class="font-semibold text-blue-900 mb-3 flex items-center">
                <i class="fas fa-info-circle mr-2"></i>
                Important Information
            </h3>
            <ul class="space-y-2 text-sm text-blue-800">
                <li class="flex items-start">
                    <i class="fas fa-check text-blue-600 mr-2 mt-0.5"></i>
                    <span>Your order will be split into separate orders for each vendor</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-blue-600 mr-2 mt-0.5"></i>
                    <span>Each vendor will review and approve your order</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-blue-600 mr-2 mt-0.5"></i>
                    <span>You will receive separate invoices for each vendor</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-blue-600 mr-2 mt-0.5"></i>
                    <span>Payment is secure and processed through Razorpay</span>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Order Summary & Payment -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
            
            <div class="space-y-3 mb-4">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>₹<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Total Items</span>
                    <span><?= $totalItems ?></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Vendors</span>
                    <span><?= count($groupedItems) ?></span>
                </div>
            </div>
            
            <div class="border-t border-gray-200 pt-4 mb-6">
                <div class="flex justify-between text-xl font-bold text-gray-900">
                    <span>Total</span>
                    <span>₹<?= number_format($subtotal, 2) ?></span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Amount in Indian Rupees (INR)</p>
            </div>
            
            <!-- DEBUG: Button Logic Check -->
            <div class="mb-2 p-2 bg-purple-50 border border-purple-200 rounded text-xs">
                <strong>Button Debug:</strong> $isValid = <?= var_export($isValid, true) ?>
            </div>
            
            <?php if ($isValid): ?>
                <button id="pay-button" 
                        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); transform: scale(1); transition: all 0.3s ease;"
                        onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 6px 20px rgba(16, 185, 129, 0.5)';"
                        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 15px rgba(16, 185, 129, 0.4)';"
                        class="w-full px-6 py-4 text-white rounded-lg mb-3 font-bold text-lg">
                    <i class="fas fa-lock mr-2"></i>
                    💳 PAY NOW - ₹<?= number_format($subtotal, 2) ?>
                </button>
            <?php else: ?>
                <button disabled 
                        class="w-full px-6 py-3 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed mb-3 font-semibold">
                    <i class="fas fa-lock mr-2"></i>
                    Cannot Proceed
                </button>
            <?php endif; ?>
            
            <a href="cart.php" 
               class="block w-full px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Cart
            </a>
            
            <div class="mt-6 flex items-center justify-center gap-4 text-sm text-gray-500">
                <i class="fas fa-shield-alt text-green-600"></i>
                <span>Secure Payment</span>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add payment script
$additionalScripts = <<<SCRIPT
<script>
const customerId = '{$customerId}';
const isValid = {$isValid};
const totalAmount = {$subtotal};
const MOCK_MODE = true; // Demo mode - no real Razorpay API calls

// Payment initiation
document.getElementById('pay-button')?.addEventListener('click', async function() {
    const button = this;
    
    if (!isValid) {
        alert('Please fix cart validation errors before proceeding');
        return;
    }
    
    // Show loading state
    button.disabled = true;
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    
    try {
        // Create payment order
        const response = await fetch('/Multi-Vendor-Rental-System/public/api/payment.php?action=create_order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                customer_id: customerId
            })
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Failed to create payment order');
        }
        
        // Open mock payment modal (demo mode)
        if (MOCK_MODE) {
            openMockPaymentModal(result.data);
        } else {
            openRazorpayModal(result.data);
        }
        
    } catch (error) {
        console.error('Payment initiation error:', error);
        alert(error.message || 'Failed to initiate payment');
        button.disabled = false;
        button.innerHTML = originalHTML;
    }
});

// Open MOCK payment modal (Demo Mode)
function openMockPaymentModal(paymentData) {
    // Create demo payment modal
    const modal = document.createElement('div');
    modal.id = 'mock-payment-modal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    
    const amount = (paymentData.amount / 100).toFixed(2);
    const orderId = paymentData.razorpay_order_id;
    
    modal.innerHTML = '<div class="bg-white rounded-lg p-8 max-w-md mx-4 shadow-2xl">' +
        '<div class="text-center mb-6">' +
            '<div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">' +
                '<i class="fas fa-credit-card text-3xl text-green-600"></i>' +
            '</div>' +
            '<h2 class="text-2xl font-bold text-gray-900 mb-2">Demo Payment</h2>' +
            '<p class="text-gray-600">This is a simulated payment for demonstration</p>' +
        '</div>' +
        '<div class="bg-gray-50 rounded-lg p-4 mb-6">' +
            '<div class="flex justify-between mb-2">' +
                '<span class="text-gray-600">Amount:</span>' +
                '<span class="font-bold text-gray-900">₹' + amount + '</span>' +
            '</div>' +
            '<div class="flex justify-between mb-2">' +
                '<span class="text-gray-600">Order ID:</span>' +
                '<span class="text-sm text-gray-700">' + orderId + '</span>' +
            '</div>' +
            '<div class="flex justify-between">' +
                '<span class="text-gray-600">Mode:</span>' +
                '<span class="text-sm font-semibold text-orange-600">DEMO MODE</span>' +
            '</div>' +
        '</div>' +
        '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">' +
            '<p class="text-sm text-blue-800">' +
                '<i class="fas fa-info-circle mr-2"></i>' +
                'No real payment will be processed. This simulates a successful payment.' +
            '</p>' +
        '</div>' +
        '<div class="flex gap-3">' +
            '<button onclick="handleMockPaymentSuccess(\'' + orderId + '\')" ' +
                    'class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">' +
                '<i class="fas fa-check mr-2"></i>Simulate Success' +
            '</button>' +
            '<button onclick="handleMockPaymentCancel()" ' +
                    'class="flex-1 px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-semibold">' +
                '<i class="fas fa-times mr-2"></i>Cancel' +
            '</button>' +
        '</div>' +
    '</div>';
    
    document.body.appendChild(modal);
}

// Handle mock payment success
async function handleMockPaymentSuccess(orderId) {
    // Close modal
    const modal = document.getElementById('mock-payment-modal');
    if (modal) modal.remove();
    
    // Show loading overlay
    showLoadingOverlay('Processing payment...');
    
    try {
        const result = await fetch('/Multi-Vendor-Rental-System/public/api/payment.php?action=verify_payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                razorpay_order_id: orderId,
                razorpay_payment_id: 'pay_MOCK' + Date.now(),
                razorpay_signature: 'mock_signature_demo'
            })
        });
        
        const data = await result.json();
        
        console.log('Payment verification response:', data);
        
        if (data.success) {
            // Redirect to success page
            const orderIds = data.data.orders.map(o => o.order_id).join(',');
            window.location.href = '/Multi-Vendor-Rental-System/public/customer/payment-success.php?orders=' + orderIds;
        } else {
            hideLoadingOverlay();
            console.error('Payment verification failed:', data);
            alert('Payment failed: ' + data.error + '\\n\\nCheck console for details');
        }
        
    } catch (error) {
        console.error('Payment verification error:', error);
        hideLoadingOverlay();
        alert('Payment processing failed: ' + error.message);
    }
}

// Handle mock payment cancel
function handleMockPaymentCancel() {
    const modal = document.getElementById('mock-payment-modal');
    if (modal) modal.remove();
    
    const button = document.getElementById('pay-button');
    if (button) {
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-lock mr-2"></i>💳 PAY NOW - ₹{$subtotal}';
    }
}

// Open Razorpay payment modal (Real Mode - not used in demo)
function openRazorpayModal(paymentData) {
    const options = {
        key: paymentData.key_id,
        amount: paymentData.amount,
        currency: paymentData.currency || 'INR',
        order_id: paymentData.razorpay_order_id,
        name: 'Multi-Vendor Rental Platform',
        description: 'Rental Booking Payment',
        image: '/Multi-Vendor-Rental-System/public/assets/images/logo.png',
        handler: function(response) {
            handlePaymentSuccess(response);
        },
        modal: {
            ondismiss: function() {
                handlePaymentCancellation();
            }
        },
        theme: {
            color: '#10b981'
        },
        prefill: {
            name: '',
            email: '',
            contact: ''
        }
    };
    
    const rzp = new Razorpay(options);
    rzp.open();
}

// Handle successful payment (Real Mode)
async function handlePaymentSuccess(response) {
    showLoadingOverlay('Verifying payment...');
    
    try {
        const result = await fetch('/Multi-Vendor-Rental-System/public/api/payment.php?action=verify_payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                razorpay_order_id: response.razorpay_order_id,
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_signature: response.razorpay_signature
            })
        });
        
        const data = await result.json();
        
        if (data.success) {
            const orderIds = data.data.orders.map(o => o.order_id).join(',');
            window.location.href = '/Multi-Vendor-Rental-System/public/customer/payment-success.php?orders=' + orderIds;
        } else {
            throw new Error(data.message || 'Payment verification failed');
        }
        
    } catch (error) {
        console.error('Payment verification error:', error);
        hideLoadingOverlay();
        window.location.href = '/Multi-Vendor-Rental-System/public/customer/payment-failure.php?reason=verification_failed&message=' + encodeURIComponent(error.message);
    }
}

// Handle payment cancellation
function handlePaymentCancellation() {
    window.location.href = '/Multi-Vendor-Rental-System/public/customer/payment-failure.php?reason=cancelled';
}

// Show loading overlay
function showLoadingOverlay(message) {
    message = message || 'Processing...';
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    overlay.innerHTML = '<div class="bg-white rounded-lg p-8 max-w-sm mx-4 text-center">' +
        '<div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primary-600 mx-auto mb-4"></div>' +
        '<p class="text-lg font-semibold text-gray-900">' + message + '</p>' +
        '<p class="text-sm text-gray-600 mt-2">Please do not close this window</p>' +
        '</div>';
    document.body.appendChild(overlay);
}

// Hide loading overlay
function hideLoadingOverlay() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}
</script>
SCRIPT;

include __DIR__ . '/../layouts/modern-base.php';
?>
