<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;

Session::start();
Middleware::requireRole(User::ROLE_CUSTOMER);

$username = Session::getUsername();
$customerId = Session::getUserId(); // Get the actual customer ID

$pageTitle = 'Customer Dashboard';
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Welcome Message -->
<?php if (isset($_GET['welcome'])): ?>
    <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-lg animate-slide-in">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">Welcome to RentalHub!</h3>
                <p class="mt-1 text-sm text-green-700">Your account has been created successfully. Start browsing products to rent.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Welcome back, <?= htmlspecialchars($username) ?>!</h1>
    <p class="mt-2 text-gray-600">Manage your rentals and discover amazing products from trusted vendors.</p>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <a href="/Multi-Vendor-Rental-System/public/customer/products.php" 
       class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-all group">
        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-blue-200 transition-colors">
            <i class="fas fa-search text-blue-600 text-xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Browse Products</h3>
        <p class="text-gray-600 text-sm">Explore our wide range of rental products</p>
    </a>

    <a href="/Multi-Vendor-Rental-System/public/cart.php" 
       class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-all group">
        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-green-200 transition-colors">
            <i class="fas fa-shopping-cart text-green-600 text-xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">My Cart</h3>
        <p class="text-gray-600 text-sm">View items in your shopping cart</p>
    </a>

    <a href="#my-orders" onclick="scrollToOrders()" 
       class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-all group">
        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-purple-200 transition-colors">
            <i class="fas fa-list text-purple-600 text-xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">My Orders</h3>
        <p class="text-gray-600 text-sm">Track your rental orders</p>
    </a>

    <a href="/Multi-Vendor-Rental-System/public/wishlist.php" 
       class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-all group">
        <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-pink-200 transition-colors">
            <i class="fas fa-heart text-pink-600 text-xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Wishlist</h3>
        <p class="text-gray-600 text-sm">View your saved products</p>
    </a>
</div>

<!-- My Orders Section -->
<div id="my-orders" class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-900">My Orders</h2>
            <button onclick="refreshOrders()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                <i class="fas fa-sync-alt mr-1"></i> Refresh
            </button>
        </div>
    </div>
    
    <div id="orders-container" class="p-6">
        <div class="text-center py-8">
            <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-600">Loading your orders...</p>
        </div>
    </div>
</div>

<script src="https://cdn.tailwindcss.com"></script>
<script>
let orders = [];

// Load orders on page load
document.addEventListener('DOMContentLoaded', function() {
    loadOrders();
});

async function loadOrders() {
    try {
        const response = await fetch('/Multi-Vendor-Rental-System/public/api/orders.php?action=customer_orders');
        const data = await response.json();
        
        if (data.success) {
            orders = data.data;
            displayOrders();
        } else {
            showError('Failed to load orders: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        showError('Error loading orders: ' + error.message);
    }
}

function displayOrders() {
    const container = document.getElementById('orders-container');
    
    if (orders.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-shopping-bag text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No orders yet</h3>
                <p class="text-gray-600 mb-4">Start browsing products to place your first rental order.</p>
                <a href="/Multi-Vendor-Rental-System/public/customer/products.php" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    Browse Products
                </a>
            </div>
        `;
        return;
    }
    
    const ordersHtml = orders.map(order => `
        <div class="border border-gray-200 rounded-lg p-6 mb-4 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">${escapeHtml(order.order_number)}</h3>
                    <p class="text-sm text-gray-600">Order placed: ${formatDate(order.created_at)}</p>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getStatusClasses(order.status)}">
                        <i class="${getStatusIcon(order.status)} mr-1"></i>
                        ${getStatusLabel(order.status)}
                    </span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <p class="text-sm font-medium text-gray-900">Total Amount</p>
                    <p class="text-lg font-semibold text-green-600">₹${parseFloat(order.total_amount).toFixed(2)}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Security Deposit</p>
                    <p class="text-sm text-gray-600">₹${parseFloat(order.deposit_amount || 0).toFixed(2)}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Payment Status</p>
                    <p class="text-sm text-green-600">
                        <i class="fas fa-check-circle mr-1"></i>
                        Verified
                    </p>
                </div>
            </div>
            
            <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                <div class="text-sm text-gray-600">
                    Order ID: ${escapeHtml(order.id)}
                </div>
                <div class="flex space-x-3">
                    <button onclick="viewOrderDetails('${order.id}')" 
                            class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-eye mr-2"></i>
                        View Details
                    </button>
                    ${order.status === 'Active_Rental' || order.status === 'Completed' ? `
                        <button onclick="downloadInvoice('${order.id}')" 
                                class="inline-flex items-center px-3 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-download mr-2"></i>
                            Download Invoice
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = ordersHtml;
}

function getStatusClasses(status) {
    const statusClasses = {
        'Payment_Successful': 'bg-blue-100 text-blue-800',
        'Pending_Vendor_Approval': 'bg-yellow-100 text-yellow-800',
        'Auto_Approved': 'bg-green-100 text-green-800',
        'Active_Rental': 'bg-green-100 text-green-800',
        'Completed': 'bg-gray-100 text-gray-800',
        'Rejected': 'bg-red-100 text-red-800',
        'Refunded': 'bg-purple-100 text-purple-800'
    };
    return statusClasses[status] || 'bg-gray-100 text-gray-800';
}

function getStatusIcon(status) {
    const statusIcons = {
        'Payment_Successful': 'fas fa-credit-card',
        'Pending_Vendor_Approval': 'fas fa-clock',
        'Auto_Approved': 'fas fa-check',
        'Active_Rental': 'fas fa-play-circle',
        'Completed': 'fas fa-check-circle',
        'Rejected': 'fas fa-times-circle',
        'Refunded': 'fas fa-undo'
    };
    return statusIcons[status] || 'fas fa-question-circle';
}

function getStatusLabel(status) {
    const statusLabels = {
        'Payment_Successful': 'Payment Successful',
        'Pending_Vendor_Approval': 'Pending Approval',
        'Auto_Approved': 'Auto Approved',
        'Active_Rental': 'Active Rental',
        'Completed': 'Completed',
        'Rejected': 'Rejected',
        'Refunded': 'Refunded'
    };
    return statusLabels[status] || status;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function scrollToOrders() {
    document.getElementById('my-orders').scrollIntoView({ behavior: 'smooth' });
}

async function refreshOrders() {
    await loadOrders();
}

function viewOrderDetails(orderId) {
    window.location.href = `order-details.php?id=${encodeURIComponent(orderId)}`;
}

function downloadInvoice(orderId) {
    // Try to download invoice, but handle errors gracefully
    const url = `../api/orders.php?action=download_invoice&order_id=${encodeURIComponent(orderId)}`;
    
    // Open in new window
    const win = window.open(url, '_blank');
    
    // Check if popup was blocked
    if (!win) {
        alert('Please allow popups to download the invoice');
    }
}

function showError(message) {
    const container = document.getElementById('orders-container');
    container.innerHTML = `
        <div class="text-center py-8">
            <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
            <p class="text-red-600">${escapeHtml(message)}</p>
            <button onclick="loadOrders()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Try Again
            </button>
        </div>
    `;
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>
