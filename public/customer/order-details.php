<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;

Session::start();
Middleware::requireRole(User::ROLE_CUSTOMER);

$username = Session::getUsername();
$customerId = Session::getUserId();
$orderId = $_GET['id'] ?? '';

if (empty($orderId)) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Order Details';
$showNav = true;
$showContainer = true;

ob_start();
?>

<div class="mb-6">
    <a href="dashboard.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-2"></i>
        Back to Dashboard
    </a>
</div>

<div id="order-details-container">
    <div class="text-center py-12">
        <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
        <p class="text-gray-600">Loading order details...</p>
    </div>
</div>

<script src="https://cdn.tailwindcss.com"></script>
<script>
const orderId = '<?= htmlspecialchars($orderId) ?>';

document.addEventListener('DOMContentLoaded', function() {
    loadOrderDetails();
});

async function loadOrderDetails() {
    try {
        const response = await fetch(`/Multi-Vendor-Rental-System/public/api/orders.php?action=order_details&order_id=${encodeURIComponent(orderId)}`);
        const data = await response.json();
        
        if (data.success) {
            displayOrderDetails(data.data);
        } else {
            showError('Failed to load order details: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        showError('Error loading order details: ' + error.message);
    }
}

function displayOrderDetails(orderData) {
    const { order, items, summary } = orderData;
    
    const container = document.getElementById('order-details-container');
    container.innerHTML = `
        <!-- Order Header -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">${escapeHtml(order.order_number)}</h1>
                    <p class="text-gray-600">Order placed: ${formatDate(order.created_at)}</p>
                </div>
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium ${getStatusClasses(order.status)}">
                    <i class="${getStatusIcon(order.status)} mr-2"></i>
                    ${getStatusLabel(order.status)}
                </span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-sm font-medium text-gray-900 mb-1">Total Amount</p>
                    <p class="text-xl font-semibold text-green-600">₹${parseFloat(order.total_amount).toFixed(2)}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 mb-1">Security Deposit</p>
                    <p class="text-lg font-semibold text-blue-600">₹${parseFloat(order.deposit_amount || 0).toFixed(2)}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 mb-1">Payment Status</p>
                    <p class="text-sm text-green-600">
                        <i class="fas fa-check-circle mr-1"></i>
                        Verified
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900 mb-1">Order ID</p>
                    <p class="text-sm text-gray-600 font-mono">${escapeHtml(order.id)}</p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Items</h2>
            <div class="space-y-4">
                ${items.map(item => `
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900">${escapeHtml(item.product_name || 'Product')}</h3>
                                <p class="text-sm text-gray-600 mt-1">Quantity: ${item.quantity}</p>
                                <p class="text-sm text-gray-600">Unit Price: ₹${parseFloat(item.unit_price).toFixed(2)}</p>
                                ${item.rental_start_date ? `
                                    <p class="text-sm text-gray-600">
                                        Rental Period: ${formatDate(item.rental_start_date)} - ${formatDate(item.rental_end_date)}
                                    </p>
                                ` : ''}
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">₹${parseFloat(item.total_price).toFixed(2)}</p>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>

        <!-- Pricing Breakdown -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Pricing Breakdown</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-medium">₹${parseFloat(summary?.subtotal || order.total_amount - order.deposit_amount).toFixed(2)}</span>
                </div>
                ${order.deposit_amount > 0 ? `
                    <div class="flex justify-between">
                        <span class="text-gray-600">Security Deposit</span>
                        <span class="font-medium text-blue-600">₹${parseFloat(order.deposit_amount).toFixed(2)}</span>
                    </div>
                ` : ''}
                <div class="border-t border-gray-200 pt-3">
                    <div class="flex justify-between">
                        <span class="text-lg font-semibold text-gray-900">Total Amount</span>
                        <span class="text-lg font-semibold text-green-600">₹${parseFloat(order.total_amount).toFixed(2)}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Actions</h2>
            <div class="flex flex-wrap gap-3">
                ${order.status === 'Active_Rental' || order.status === 'Completed' ? `
                    <button onclick="downloadInvoice('${order.id}')" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Download Invoice
                    </button>
                ` : ''}
                
                ${order.status === 'Pending_Vendor_Approval' ? `
                    <button onclick="uploadDocuments('${order.id}')" 
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-upload mr-2"></i>
                        Upload Documents
                    </button>
                ` : ''}
                
                <button onclick="contactSupport('${order.id}')" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-headset mr-2"></i>
                    Contact Support
                </button>
            </div>
        </div>

        <!-- Status Information -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                <div>
                    <h3 class="text-sm font-medium text-blue-900">Order Status Information</h3>
                    <p class="text-sm text-blue-700 mt-1">${getStatusDescription(order.status)}</p>
                </div>
            </div>
        </div>
    `;
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

function getStatusDescription(status) {
    const descriptions = {
        'Payment_Successful': 'Your payment has been processed successfully. The order is being prepared.',
        'Pending_Vendor_Approval': 'Your order is waiting for vendor approval. You may need to upload verification documents.',
        'Auto_Approved': 'Your order has been automatically approved and will be activated soon.',
        'Active_Rental': 'Your rental is currently active. Enjoy your rental period!',
        'Completed': 'Your rental has been completed successfully. Thank you for choosing our service.',
        'Rejected': 'Your order has been rejected by the vendor. A refund will be processed.',
        'Refunded': 'Your order has been refunded. The amount will be credited to your account.'
    };
    return descriptions[status] || 'Order status information not available.';
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

function downloadInvoice(orderId) {
    window.open(`../api/orders.php?action=download_invoice&order_id=${encodeURIComponent(orderId)}`, '_blank');
}

function uploadDocuments(orderId) {
    window.location.href = `document-upload.php?order_id=${encodeURIComponent(orderId)}`;
}

function contactSupport(orderId) {
    alert('Support contact feature will be implemented soon. Order ID: ' + orderId);
}

function showError(message) {
    const container = document.getElementById('order-details-container');
    container.innerHTML = `
        <div class="text-center py-12">
            <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
            <p class="text-red-600 mb-4">${escapeHtml(message)}</p>
            <button onclick="loadOrderDetails()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
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