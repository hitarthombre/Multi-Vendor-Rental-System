<?php
session_start();

// For demo purposes, use a hardcoded customer ID
// In a real application, this would come from the session
$customerId = 'demo-customer-123';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Rental Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900">Rental Platform</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="customer/products.php" class="text-gray-600 hover:text-gray-900">Browse Products</a>
                    <a href="wishlist.php" class="text-gray-600 hover:text-gray-900">Wishlist</a>
                    <span class="text-blue-600 font-medium">Cart</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Cart Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="cartManager()">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Cart Items -->
            <div class="flex-1">
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Shopping Cart</h2>
                        <p class="text-sm text-gray-500" x-text="`${cart.summary.total_items} items from ${cart.summary.vendor_count} vendors`"></p>
                    </div>

                    <!-- Loading State -->
                    <div x-show="loading" class="p-6">
                        <div class="animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
                            <div class="space-y-3">
                                <div class="h-20 bg-gray-200 rounded"></div>
                                <div class="h-20 bg-gray-200 rounded"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty Cart -->
                    <div x-show="!loading && cart.items.length === 0" class="p-6 text-center">
                        <div class="text-gray-400 mb-4">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5-6m0 0h15.5M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Your cart is empty</h3>
                        <p class="text-gray-500 mb-4">Start browsing our products to add items to your cart.</p>
                        <a href="customer/products.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Browse Products
                        </a>
                    </div>

                    <!-- Cart Items by Vendor -->
                    <div x-show="!loading && cart.items.length > 0" class="divide-y divide-gray-200">
                        <template x-for="(vendor, vendorId) in cart.vendors" :key="vendorId">
                            <div class="p-6">
                                <!-- Vendor Header -->
                                <div class="flex items-center mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-md font-medium text-gray-900" x-text="vendor.vendor_name"></h3>
                                        <p class="text-sm text-gray-500" x-text="`${vendor.items.length} items • $${vendor.total_amount.toFixed(2)}`"></p>
                                    </div>
                                </div>

                                <!-- Vendor Items -->
                                <div class="space-y-4">
                                    <template x-for="item in vendor.items" :key="item.id">
                                        <div class="flex items-start space-x-4 p-4 bg-gray-50 rounded-lg">
                                            <!-- Product Image -->
                                            <div class="flex-shrink-0">
                                                <img class="h-16 w-16 rounded-lg object-cover" 
                                                     :src="item.product_image || '/api/placeholder/64/64'" 
                                                     :alt="item.product_name">
                                            </div>

                                            <!-- Product Details -->
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-sm font-medium text-gray-900" x-text="item.product_name"></h4>
                                                <p class="text-sm text-gray-500" x-text="item.product_description"></p>
                                                
                                                <!-- Variant Info -->
                                                <div x-show="item.variant_info" class="mt-1">
                                                    <span class="text-xs text-gray-500" x-text="item.variant_info"></span>
                                                </div>

                                                <!-- Rental Period -->
                                                <div class="mt-2 flex items-center text-sm text-gray-600">
                                                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    <span x-text="`${formatDate(item.start_datetime)} - ${formatDate(item.end_datetime)}`"></span>
                                                </div>

                                                <!-- Duration and Price -->
                                                <div class="mt-1 flex items-center justify-between">
                                                    <span class="text-sm text-gray-600" x-text="`${item.duration_value} ${item.duration_unit}`"></span>
                                                    <span class="text-sm font-medium text-gray-900" x-text="`$${item.tentative_price.toFixed(2)} each`"></span>
                                                </div>
                                            </div>

                                            <!-- Quantity and Actions -->
                                            <div class="flex flex-col items-end space-y-2">
                                                <!-- Quantity Controls -->
                                                <div class="flex items-center space-x-2">
                                                    <button @click="updateQuantity(item.id, item.quantity - 1)" 
                                                            class="p-1 rounded-md hover:bg-gray-200"
                                                            :disabled="updating">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                                        </svg>
                                                    </button>
                                                    <span class="px-2 py-1 text-sm font-medium" x-text="item.quantity"></span>
                                                    <button @click="updateQuantity(item.id, item.quantity + 1)" 
                                                            class="p-1 rounded-md hover:bg-gray-200"
                                                            :disabled="updating">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                <!-- Total Price -->
                                                <div class="text-right">
                                                    <div class="text-lg font-semibold text-gray-900" x-text="`$${(item.tentative_price * item.quantity).toFixed(2)}`"></div>
                                                </div>

                                                <!-- Remove Button -->
                                                <button @click="removeItem(item.id)" 
                                                        class="text-red-600 hover:text-red-800 text-sm"
                                                        :disabled="updating">
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Cart Summary Sidebar -->
            <div class="w-full lg:w-80">
                <div class="bg-white rounded-lg shadow-sm sticky top-4">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Order Summary</h3>
                    </div>

                    <div x-show="!loading && cart.items.length > 0" class="p-6">
                        <!-- Summary Details -->
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Items</span>
                                <span x-text="cart.summary.total_items"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Vendors</span>
                                <span x-text="cart.summary.vendor_count"></span>
                            </div>
                            <div class="border-t border-gray-200 pt-3">
                                <div class="flex justify-between">
                                    <span class="text-base font-medium text-gray-900">Total</span>
                                    <span class="text-base font-medium text-gray-900" x-text="`$${cart.summary.total_amount.toFixed(2)}`"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-3">
                            <button @click="proceedToCheckout()" 
                                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700 font-medium"
                                    :disabled="updating">
                                Proceed to Checkout
                            </button>
                            <button @click="clearCart()" 
                                    class="w-full bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300"
                                    :disabled="updating">
                                Clear Cart
                            </button>
                        </div>

                        <!-- Vendor Breakdown -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">By Vendor</h4>
                            <div class="space-y-2">
                                <template x-for="(vendor, vendorId) in cart.vendors" :key="vendorId">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600" x-text="vendor.vendor_name"></span>
                                        <span x-text="`$${vendor.total_amount.toFixed(2)}`"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Empty Cart Summary -->
                    <div x-show="!loading && cart.items.length === 0" class="p-6 text-center text-gray-500">
                        <p>Your cart is empty</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function cartManager() {
            return {
                cart: {
                    items: [],
                    summary: {
                        total_items: 0,
                        vendor_count: 0,
                        total_amount: 0
                    },
                    vendors: {}
                },
                loading: true,
                updating: false,

                async init() {
                    await this.loadCart();
                },

                async loadCart() {
                    try {
                        this.loading = true;
                        const response = await fetch('/Multi-Vendor-Rental-System/public/api/cart.php?action=contents');
                        const result = await response.json();
                        
                        if (result.success) {
                            this.cart = result.data;
                        } else {
                            console.error('Failed to load cart:', result.error);
                        }
                    } catch (error) {
                        console.error('Error loading cart:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                async updateQuantity(cartItemId, newQuantity) {
                    if (this.updating) return;
                    
                    try {
                        this.updating = true;
                        
                        const formData = new FormData();
                        formData.append('action', 'update_quantity');
                        formData.append('cart_item_id', cartItemId);
                        formData.append('quantity', newQuantity);

                        const response = await fetch('/Multi-Vendor-Rental-System/public/api/cart.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            await this.loadCart();
                        } else {
                            alert('Error updating quantity: ' + result.error);
                        }
                    } catch (error) {
                        console.error('Error updating quantity:', error);
                        alert('Error updating quantity');
                    } finally {
                        this.updating = false;
                    }
                },

                async removeItem(cartItemId) {
                    if (this.updating) return;
                    
                    if (!confirm('Are you sure you want to remove this item?')) {
                        return;
                    }
                    
                    try {
                        this.updating = true;
                        
                        const response = await fetch(`/Multi-Vendor-Rental-System/public/api/cart.php?cart_item_id=${cartItemId}`, {
                            method: 'DELETE'
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            await this.loadCart();
                        } else {
                            alert('Error removing item: ' + result.error);
                        }
                    } catch (error) {
                        console.error('Error removing item:', error);
                        alert('Error removing item');
                    } finally {
                        this.updating = false;
                    }
                },

                async clearCart() {
                    if (this.updating) return;
                    
                    if (!confirm('Are you sure you want to clear your cart?')) {
                        return;
                    }
                    
                    try {
                        this.updating = true;
                        
                        const formData = new FormData();
                        formData.append('action', 'clear');

                        const response = await fetch('/Multi-Vendor-Rental-System/public/api/cart.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            await this.loadCart();
                        } else {
                            alert('Error clearing cart: ' + result.error);
                        }
                    } catch (error) {
                        console.error('Error clearing cart:', error);
                        alert('Error clearing cart');
                    } finally {
                        this.updating = false;
                    }
                },

                async proceedToCheckout() {
                    if (this.updating) return;
                    
                    try {
                        this.updating = true;
                        
                        // Validate cart first
                        const response = await fetch('/Multi-Vendor-Rental-System/public/api/cart.php?action=validate');
                        const result = await response.json();
                        
                        if (result.success && result.data.valid) {
                            // Redirect to checkout page (to be implemented)
                            alert('Checkout functionality will be implemented in payment integration tasks');
                        } else {
                            const errors = result.data.errors || ['Cart validation failed'];
                            alert('Cannot proceed to checkout:\n' + errors.join('\n'));
                        }
                    } catch (error) {
                        console.error('Error validating cart:', error);
                        alert('Error validating cart');
                    } finally {
                        this.updating = false;
                    }
                },

                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                }
            }
        }
    </script>
</body>
</html>