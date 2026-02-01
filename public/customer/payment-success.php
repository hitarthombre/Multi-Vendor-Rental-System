<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;

Session::start();
Middleware::requireAuth();
Middleware::requireCustomer();

$customerId = Session::getUserId();

// Get order IDs from URL
$orderIdsParam = $_GET['orders'] ?? '';
$orderIds = !empty($orderIdsParam) ? explode(',', $orderIdsParam) : [];

// Redirect to dashboard if no orders
if (empty($orderIds)) {
    header('Location: dashboard.php?error=' . urlencode('No orders found'));
    exit;
}

$pageTitle = 'Payment Successful';
$showNav = true;
$showContainer = true;

// Prepare JSON for JavaScript
$orderIdsJson = json_encode($orderIds);

ob_start();
?>

<!-- Success Header -->
<div class="max-w-4xl mx-auto">
    <!-- Success Icon and Message -->
    <div class="text-center mb-8 animate-slide-in">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
            <i class="fas fa-check-circle text-4xl text-green-600"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Payment Successful!</h1>
        <p class="text-lg text-gray-600">Your rental booking has been confirmed</p>
    </div>

    <!-- Loading State -->
    <div id="loading-state" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
        <p class="text-gray-600">Loading your order details...</p>
    </div>

    <!-- Orders Container -->
    <div id="orders-container" class="hidden space-y-6">
        <!-- Orders will be loaded here via JavaScript -->
    </div>

    <!-- Next Steps -->
    <div id="next-steps" class="hidden mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 class="font-semibold text-blue-900 mb-4 flex items-center">
            <i class="fas fa-info-circle mr-2"></i>
            What Happens Next?
        </h3>
        <div class="space-y-3 text-sm text-blue-800">
            <div class="flex items-start">
                <div class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center mr-3 mt-0.5">
                    <span class="text-xs font-bold text-blue-900">1</span>
                </div>
                <div>
                    <p class="font-medium">Vendor Review</p>
                    <p class="text-blue-700">Each vendor will review and approve your order. This typically takes 1-2 business days.</p>
                </div>
            </div>
            <div class="flex items-start">
                <div class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center mr-3 mt-0.5">
                    <span class="text-xs font-bold text-blue-900">2</span>
                </div>
                <div>
                    <p class="font-medium">Document Upload</p>
                    <p class="text-blue-700">You may need to upload required documents (ID proof, address proof) for verification.</p>
                </div>
            </div>
            <div class="flex items-start">
                <div class="flex-shrink-0 w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center mr-3 mt-0.5">
                    <span class="text-xs font-bold text-blue-900">3</span>
                </div>
                <div>
                    <p class="font-medium">Rental Begins</p>
                    <p class="text-blue-700">Once approved, your rental will begin on the scheduled start date.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div id="action-buttons" class="hidden mt-8 flex flex-col sm:flex-row gap-4 justify-center">
        <a href="dashboard.php" 
           class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-center font-semibold">
            <i class="fas fa-tachometer-alt mr-2"></i>
            Go to Dashboard
        </a>
        <a href="../index.php" 
           class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-center font-semibold">
            <i class="fas fa-home mr-2"></i>
            Back to Home
        </a>
    </div>

    <!-- Email Confirmation Notice -->
    <div id="email-notice" class="hidden mt-8 text-center text-sm text-gray-600">
        <i class="fas fa-envelope mr-2"></i>
        Confirmation emails have been sent to your registered email address
    </div>
</div>

<?php
$content = ob_get_clean();

// Build JavaScript with proper escaping
$additionalScripts = '<script>' . "\n";
$additionalScripts .= 'const orderIds = ' . $orderIdsJson . ';' . "\n";
$additionalScripts .= 'const customerId = \'' . $customerId . '\';' . "\n";
$additionalScripts .= <<<'SCRIPT'

// Load order details on page load
document.addEventListener('DOMContentLoaded', async function() {
    await loadOrderDetails();
});

// Load order details from API
async function loadOrderDetails() {
    const loadingState = document.getElementById('loading-state');
    const ordersContainer = document.getElementById('orders-container');
    const nextSteps = document.getElementById('next-steps');
    const actionButtons = document.getElementById('action-buttons');
    const emailNotice = document.getElementById('email-notice');
    
    try {
        const orders = [];
        
        // Fetch details for each order
        for (const orderId of orderIds) {
            const response = await fetch(`/Multi-Vendor-Rental-System/public/api/orders.php?action=order_details&order_id=${orderId}`);
            const result = await response.json();
            
            if (result.success) {
                orders.push(result.data);
            } else {
                console.error('Failed to load order:', orderId, result.error);
            }
        }
        
        if (orders.length === 0) {
            throw new Error('No orders could be loaded');
        }
        
        // Display orders
        displayOrders(orders);
        
        // Show other sections
        loadingState.classList.add('hidden');
        ordersContainer.classList.remove('hidden');
        nextSteps.classList.remove('hidden');
        actionButtons.classList.remove('hidden');
        emailNotice.classList.remove('hidden');
        
    } catch (error) {
        console.error('Error loading orders:', error);
        loadingState.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i>
                <p class="text-red-600 mb-4">Failed to load order details</p>
                <a href="dashboard.php" class="text-primary-600 hover:text-primary-700 font-medium">
                    Go to Dashboard
                </a>
            </div>
        `;
    }
}

// Display orders in the container
function displayOrders(ordersData) {
    const container = document.getElementById('orders-container');
    
    ordersData.forEach((orderData, index) => {
        const order = orderData.order;
        const vendor = orderData.vendor;
        const items = orderData.items || [];
        const invoice = orderData.invoice;
        
        const orderCard = createOrderCard(order, vendor, items, invoice, index);
        container.appendChild(orderCard);
    });
}

// Create order card element
function createOrderCard(order, vendor, items, invoice, index) {
    const card = document.createElement('div');
    card.className = 'bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden animate-slide-in';
    card.style.animationDelay = `${index * 0.1}s`;
    
    // Status badge styling
    const statusColors = {
        'Pending_Vendor_Approval': 'bg-yellow-100 text-yellow-800',
        'Auto_Approved': 'bg-green-100 text-green-800',
        'Pending_Documents': 'bg-orange-100 text-orange-800',
        'Active_Rental': 'bg-blue-100 text-blue-800',
        'Completed': 'bg-gray-100 text-gray-800',
        'Cancelled': 'bg-red-100 text-red-800'
    };
    
    const statusColor = statusColors[order.status] || 'bg-gray-100 text-gray-800';
    const statusText = order.status.replace(/_/g, ' ');
    
    card.innerHTML = `
        <div class="bg-gradient-to-r from-primary-50 to-primary-100 px-6 py-4 border-b border-primary-200">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="font-semibold text-gray-900 text-lg">
                        Order #${order.order_number || order.id.substring(0, 8)}
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">
                        <i class="fas fa-store mr-1"></i>
                        ${escapeHtml(vendor?.business_name || 'Unknown Vendor')}
                    </p>
                </div>
                <div class="text-right">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${statusColor}">
                        ${statusText}
                    </span>
                    <p class="text-sm text-gray-600 mt-1">
                        ${formatCurrency(order.total_amount)}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Order Items -->
            <div class="mb-4">
                <h4 class="font-medium text-gray-900 mb-3">Order Items</h4>
                <div class="space-y-2">
                    ${items.map(item => `
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-700">
                                ${escapeHtml(item.product_name || 'Product')} 
                                ${item.quantity > 1 ? `(×${item.quantity})` : ''}
                            </span>
                            <span class="text-gray-600">
                                ${item.start_date ? formatDate(item.start_date) : ''} - 
                                ${item.end_date ? formatDate(item.end_date) : ''}
                            </span>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <!-- Order Details -->
            <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                <div>
                    <p class="text-gray-600">Order Date</p>
                    <p class="font-medium text-gray-900">${formatDateTime(order.created_at)}</p>
                </div>
                <div>
                    <p class="text-gray-600">Payment Status</p>
                    <p class="font-medium text-green-600">
                        <i class="fas fa-check-circle mr-1"></i>
                        Paid
                    </p>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 mt-4 pt-4 border-t border-gray-200">
                <a href="order-details.php?id=${order.id}" 
                   class="flex-1 min-w-[150px] px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-center text-sm font-medium">
                    <i class="fas fa-eye mr-1"></i>
                    View Details
                </a>
                ${invoice ? `
                    <button onclick="downloadInvoice('${order.id}')" 
                            class="flex-1 min-w-[150px] px-4 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium">
                        <i class="fas fa-download mr-1"></i>
                        Download Invoice
                    </button>
                ` : ''}
            </div>
            
            <!-- Status-specific messages -->
            ${order.status === 'Pending_Vendor_Approval' ? `
                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                    <i class="fas fa-clock mr-2"></i>
                    Waiting for vendor approval. You'll be notified once the vendor reviews your order.
                </div>
            ` : ''}
            ${order.status === 'Pending_Documents' ? `
                <div class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg text-sm text-orange-800">
                    <i class="fas fa-file-upload mr-2"></i>
                    Please upload required documents to proceed with your rental.
                    <a href="document-upload.php?order_id=${order.id}" class="font-medium underline ml-1">
                        Upload Now
                    </a>
                </div>
            ` : ''}
        </div>
    `;
    
    return card;
}

// Download invoice
async function downloadInvoice(orderId) {
    try {
        window.location.href = `/Multi-Vendor-Rental-System/public/api/orders.php?action=download_invoice&order_id=${orderId}`;
    } catch (error) {
        console.error('Error downloading invoice:', error);
        toastManager.error('Failed to download invoice');
    }
}

// Helper functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>
SCRIPT;
$additionalScripts .= "\n</script>";

include __DIR__ . '/../layouts/modern-base.php';
?>
