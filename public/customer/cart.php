<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Repositories\CartRepository;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\VariantRepository;
use RentalPlatform\Repositories\VendorRepository;

Session::start();
Middleware::requireAuth();
Middleware::requireCustomer();

$customerId = Session::getUserId();
$cartRepo = new CartRepository();
$productRepo = new ProductRepository();
$variantRepo = new VariantRepository();
$vendorRepo = new VendorRepository();

$errors = [];
$success = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_quantity') {
        $itemId = $_POST['item_id'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if ($quantity < 1) {
            $errors[] = 'Quantity must be at least 1';
        } else {
            $cart = $cartRepo->findByCustomerId($customerId);
            if ($cart) {
                $item = $cart->getItem($itemId);
                if ($item) {
                    $item->setQuantity($quantity);
                    $cartRepo->updateItem($item);
                    $cartRepo->update($cart);
                    $success = 'Cart updated successfully';
                }
            }
        }
    }
    
    elseif ($action === 'remove_item') {
        $itemId = $_POST['item_id'] ?? '';
        
        $cart = $cartRepo->findByCustomerId($customerId);
        if ($cart) {
            $cartRepo->removeItem($itemId);
            $cart->removeItem($itemId);
            $cartRepo->update($cart);
            $success = 'Item removed from cart';
        }
    }
    
    elseif ($action === 'clear_cart') {
        $cart = $cartRepo->findByCustomerId($customerId);
        if ($cart) {
            $cartRepo->clearCart($cart->getId());
            $success = 'Cart cleared successfully';
        }
    }
    
    // Redirect to avoid form resubmission
    if ($success) {
        header('Location: cart.php?success=' . urlencode($success));
        exit;
    }
}

// Get success message from URL
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Get cart
$cart = $cartRepo->getOrCreateForCustomer($customerId);
$cartItems = $cart->getItems();
$groupedItems = $cart->groupByVendor();

// Load product and vendor details for each item
$itemsWithDetails = [];
foreach ($cartItems as $item) {
    $product = $productRepo->findById($item->getProductId());
    $variant = $variantRepo->findById($item->getVariantId());
    $vendor = $vendorRepo->findById($item->getVendorId());
    
    $itemsWithDetails[] = [
        'item' => $item,
        'product' => $product,
        'variant' => $variant,
        'vendor' => $vendor
    ];
}

$pageTitle = 'Shopping Cart';
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Success/Error Messages -->
<?php if (!empty($errors)): ?>
    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded animate-slide-in">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Error</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded animate-slide-in">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Shopping Cart</h1>
    <p class="mt-2 text-gray-600"><?= count($cartItems) ?> item<?= count($cartItems) !== 1 ? 's' : '' ?> in your cart</p>
</div>

<?php if (empty($cartItems)): ?>
    <!-- Empty Cart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Your cart is empty</h2>
        <p class="text-gray-600 mb-6">Add some products to get started</p>
        <a href="/Multi-Vendor-Rental-System/public/customer/products.php" 
           class="inline-flex items-center px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-search mr-2"></i>
            Browse Products
        </a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Cart Items -->
        <div class="lg:col-span-2 space-y-4">
            <?php foreach ($groupedItems as $vendorId => $vendorItems): ?>
                <?php
                $firstItem = $vendorItems[0];
                $vendor = $vendorRepo->findById($vendorId);
                ?>
                
                <!-- Vendor Group -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Vendor Header -->
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center">
                            <i class="fas fa-store text-primary-600 mr-2"></i>
                            <h3 class="font-semibold text-gray-900">
                                <?= htmlspecialchars($vendor ? $vendor->getBusinessName() : 'Unknown Vendor') ?>
                            </h3>
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
                                        <div class="w-24 h-24 bg-gray-100 rounded-lg overflow-hidden">
                                            <?php if ($product && !empty($product->getImages())): ?>
                                                <img src="<?= htmlspecialchars($product->getImages()[0]) ?>" 
                                                     alt="<?= htmlspecialchars($product->getName()) ?>"
                                                     class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <i class="fas fa-image text-2xl"></i>
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
                                        
                                        <div class="flex items-center gap-4 text-sm text-gray-600 mb-3">
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
                                            <div class="flex items-center gap-4">
                                                <!-- Quantity -->
                                                <form method="POST" class="flex items-center gap-2">
                                                    <input type="hidden" name="action" value="update_quantity">
                                                    <input type="hidden" name="item_id" value="<?= $item->getId() ?>">
                                                    <label class="text-sm text-gray-600">Qty:</label>
                                                    <input type="number" 
                                                           name="quantity" 
                                                           value="<?= $item->getQuantity() ?>" 
                                                           min="1" 
                                                           max="10"
                                                           class="w-16 px-2 py-1 border border-gray-300 rounded text-center"
                                                           onchange="this.form.submit()">
                                                </form>
                                                
                                                <!-- Remove Button -->
                                                <form method="POST" onsubmit="return confirm('Remove this item from cart?')">
                                                    <input type="hidden" name="action" value="remove_item">
                                                    <input type="hidden" name="item_id" value="<?= $item->getId() ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                                        <i class="fas fa-trash mr-1"></i>Remove
                                                    </button>
                                                </form>
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
                </div>
            <?php endforeach; ?>
            
            <!-- Clear Cart Button -->
            <div class="flex justify-end">
                <form method="POST" onsubmit="return confirm('Are you sure you want to clear your entire cart?')">
                    <input type="hidden" name="action" value="clear_cart">
                    <button type="submit" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash mr-2"></i>Clear Cart
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Cart Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
                
                <div class="space-y-3 mb-4">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span>₹<?= number_format($cart->getTotalPrice(), 2) ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Items</span>
                        <span><?= $cart->getTotalQuantity() ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Vendors</span>
                        <span><?= count($groupedItems) ?></span>
                    </div>
                </div>
                
                <div class="border-t border-gray-200 pt-4 mb-6">
                    <div class="flex justify-between text-lg font-bold text-gray-900">
                        <span>Total</span>
                        <span>₹<?= number_format($cart->getTotalPrice(), 2) ?></span>
                    </div>
                </div>
                
                <button class="w-full px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors mb-3">
                    <i class="fas fa-lock mr-2"></i>
                    Proceed to Checkout
                </button>
                
                <a href="/Multi-Vendor-Rental-System/public/customer/products.php" 
                   class="block w-full px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Continue Shopping
                </a>
                
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        Orders will be split by vendor for separate processing
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/modern-base.php';
?>
