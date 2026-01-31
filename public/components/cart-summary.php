<?php
// Cart Summary Sidebar Component
// Include this component in other pages to show cart summary
?>

<!-- Cart Summary Sidebar -->
<div class="bg-white rounded-lg shadow-sm p-4" x-data="cartSummary()" x-init="init()">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Cart</h3>
        <a href="/cart.php" class="text-blue-600 hover:text-blue-800 text-sm">View Cart</a>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
    </div>

    <!-- Empty Cart -->
    <div x-show="!loading && summary.total_items === 0" class="text-center py-4">
        <div class="text-gray-400 mb-2">
            <svg class="mx-auto h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5-6m0 0h15.5M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6" />
            </svg>
        </div>
        <p class="text-sm text-gray-500">Your cart is empty</p>
    </div>

    <!-- Cart Summary -->
    <div x-show="!loading && summary.total_items > 0">
        <div class="space-y-2 mb-4">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Items:</span>
                <span x-text="summary.total_items"></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Vendors:</span>
                <span x-text="summary.vendor_count"></span>
            </div>
            <div class="border-t border-gray-200 pt-2">
                <div class="flex justify-between font-medium">
                    <span>Total:</span>
                    <span x-text="`$${summary.total_amount.toFixed(2)}`"></span>
                </div>
            </div>
        </div>

        <div class="space-y-2">
            <a href="/cart.php" class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded-md hover:bg-blue-700 text-sm font-medium">
                View Cart
            </a>
            <button @click="proceedToCheckout()" class="block w-full bg-green-600 text-white text-center py-2 px-4 rounded-md hover:bg-green-700 text-sm font-medium">
                Checkout
            </button>
        </div>
    </div>
</div>

<script>
function cartSummary() {
    return {
        summary: {
            total_items: 0,
            vendor_count: 0,
            total_amount: 0
        },
        loading: true,

        async init() {
            await this.loadSummary();
            // Refresh summary every 30 seconds
            setInterval(() => this.loadSummary(), 30000);
        },

        async loadSummary() {
            try {
                const response = await fetch('/api/cart.php?action=summary');
                const result = await response.json();
                
                if (result.success) {
                    this.summary = result.data;
                }
            } catch (error) {
                console.error('Error loading cart summary:', error);
            } finally {
                this.loading = false;
            }
        },

        async proceedToCheckout() {
            // Validate cart first
            try {
                const response = await fetch('/api/cart.php?action=validate');
                const result = await response.json();
                
                if (result.success && result.data.valid) {
                    // Redirect to checkout page (to be implemented)
                    window.location.href = '/cart.php';
                } else {
                    const errors = result.data.errors || ['Cart validation failed'];
                    alert('Cannot proceed to checkout:\n' + errors.join('\n'));
                }
            } catch (error) {
                console.error('Error validating cart:', error);
                alert('Error validating cart');
            }
        }
    }
}
</script>