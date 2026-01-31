<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\VendorRepository;

Session::start();
Middleware::requireRole(User::ROLE_VENDOR);

$userId = Session::getUserId();

// Get vendor profile
$vendorRepo = new VendorRepository();
$vendor = $vendorRepo->findByUserId($userId);

if (!$vendor) {
    die('Vendor profile not found. Please contact support.');
}

$pageTitle = 'Financial Dashboard';
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Financial Dashboard</h1>
            <p class="mt-2 text-gray-600">Track your earnings, invoices, and payment status</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="refreshFinancialData()" 
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>
                Refresh
            </button>
            <button onclick="exportFinancialReport()" 
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
                <i class="fas fa-download mr-2"></i>
                Export Report
            </button>
        </div>
    </div>
</div>

<!-- Loading State -->
<div id="loading-state" class="text-center py-12">
    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    <p class="mt-2 text-gray-600">Loading financial data...</p>
</div>

<!-- Financial Summary Cards -->
<div id="financial-content" class="hidden">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Revenue -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p id="total-revenue" class="text-3xl font-bold text-gray-900 mt-2">₹0.00</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-rupee-sign text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-gray-500">All time earnings</span>
            </div>
        </div>

        <!-- Net Revenue -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Net Revenue</p>
                    <p id="net-revenue" class="text-3xl font-bold text-gray-900 mt-2">₹0.00</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-gray-500">After refunds</span>
            </div>
        </div>

        <!-- Total Deposits -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Security Deposits</p>
                    <p id="total-deposits" class="text-3xl font-bold text-gray-900 mt-2">₹0.00</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shield-alt text-yellow-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-gray-500">Collected deposits</span>
            </div>
        </div>

        <!-- Total Refunds -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Refunds</p>
                    <p id="total-refunds" class="text-3xl font-bold text-red-600 mt-2">₹0.00</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-undo text-red-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span id="refund-count" class="text-gray-500">0 refunds processed</span>
            </div>
        </div>
    </div>

    <!-- Order Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Completed Orders -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Completed Orders</p>
                    <p id="completed-orders" class="text-2xl font-bold text-green-600 mt-2">0</p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Active Orders -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Rentals</p>
                    <p id="active-orders" class="text-2xl font-bold text-blue-600 mt-2">0</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-play-circle text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
                    <p id="pending-orders" class="text-2xl font-bold text-yellow-600 mt-2">0</p>
                </div>
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs for Different Views -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button onclick="switchTab('invoices')" 
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        data-tab="invoices">
                    <i class="fas fa-file-invoice mr-2"></i>
                    Recent Invoices
                </button>
                <button onclick="switchTab('payments')" 
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        data-tab="payments">
                    <i class="fas fa-credit-card mr-2"></i>
                    Recent Payments
                </button>
                <button onclick="switchTab('refunds')" 
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        data-tab="refunds">
                    <i class="fas fa-undo mr-2"></i>
                    Recent Refunds
                </button>
            </nav>
        </div>

        <!-- Invoices Tab -->
        <div id="invoices-tab" class="tab-content">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Invoices</h3>
                    <span id="invoice-count" class="text-sm text-gray-500">0 invoices</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="invoices-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Invoices will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payments Tab -->
        <div id="payments-tab" class="tab-content hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Payments</h3>
                    <span id="payment-count" class="text-sm text-gray-500">0 payments</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody id="payments-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Payments will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Refunds Tab -->
        <div id="refunds-tab" class="tab-content hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Refunds</h3>
                    <span id="refunds-count" class="text-sm text-gray-500">0 refunds</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Refund ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody id="refunds-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Refunds will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Error State -->
<div id="error-state" class="hidden text-center py-12">
    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">Error Loading Financial Data</h3>
    <p id="error-message" class="text-gray-500 mb-6">Unable to load financial information. Please try again.</p>
    <button onclick="loadFinancialData()" 
            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
        <i class="fas fa-retry mr-2"></i>
        Try Again
    </button>
</div>

<script>
let currentTab = 'invoices';
let financialData = null;

// Load financial data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadFinancialData();
});

// Load financial data from API
async function loadFinancialData() {
    try {
        showLoadingState();
        
        const response = await fetch('/Multi-Vendor-Rental-System/public/api/vendor-financial.php');
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Failed to load financial data');
        }
        
        financialData = result.data;
        updateFinancialSummary();
        updateTables();
        showFinancialContent();
        
    } catch (error) {
        console.error('Error loading financial data:', error);
        showErrorState(error.message);
    }
}

// Update financial summary cards
function updateFinancialSummary() {
    const summary = financialData.summary;
    
    document.getElementById('total-revenue').textContent = `₹${formatCurrency(summary.total_revenue)}`;
    document.getElementById('net-revenue').textContent = `₹${formatCurrency(summary.net_revenue)}`;
    document.getElementById('total-deposits').textContent = `₹${formatCurrency(summary.total_deposits)}`;
    document.getElementById('total-refunds').textContent = `₹${formatCurrency(summary.total_refunds)}`;
    document.getElementById('refund-count').textContent = `${summary.refund_count} refunds processed`;
    
    document.getElementById('completed-orders').textContent = summary.completed_orders;
    document.getElementById('active-orders').textContent = summary.active_orders;
    document.getElementById('pending-orders').textContent = summary.pending_orders;
    
    document.getElementById('invoice-count').textContent = `${summary.total_invoices} invoices`;
}

// Update all tables
function updateTables() {
    updateInvoicesTable();
    updatePaymentsTable();
    updateRefundsTable();
}

// Update invoices table
function updateInvoicesTable() {
    const tbody = document.getElementById('invoices-table-body');
    tbody.innerHTML = '';
    
    if (financialData.recent_invoices.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-file-invoice text-4xl mb-4 text-gray-300"></i>
                    <p>No invoices found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    financialData.recent_invoices.forEach(invoice => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 transition-colors';
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${invoice.invoice_number}</div>
                <div class="text-sm text-gray-500">ID: ${invoice.id.substring(0, 8)}...</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${invoice.order_number}</div>
                <div class="text-sm text-gray-500">${getOrderStatusBadge(invoice.order_status)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${invoice.customer_name}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">₹${formatCurrency(invoice.total_amount)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${getInvoiceStatusBadge(invoice.status)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${formatDate(invoice.created_at)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="downloadInvoice('${invoice.id}')" 
                        class="text-primary-600 hover:text-primary-900">
                    <i class="fas fa-download"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Update payments table
function updatePaymentsTable() {
    const tbody = document.getElementById('payments-table-body');
    tbody.innerHTML = '';
    
    if (financialData.recent_payments.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-credit-card text-4xl mb-4 text-gray-300"></i>
                    <p>No payments found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    financialData.recent_payments.forEach(payment => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 transition-colors';
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${payment.id.substring(0, 8)}...</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${payment.order_number}</div>
                <div class="text-sm text-gray-500">${getOrderStatusBadge(payment.order_status)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${payment.customer_name}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">₹${formatCurrency(payment.amount)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${getPaymentStatusBadge(payment.status)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${formatDate(payment.created_at)}
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Update refunds table
function updateRefundsTable() {
    const tbody = document.getElementById('refunds-table-body');
    tbody.innerHTML = '';
    
    if (financialData.recent_refunds.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-undo text-4xl mb-4 text-gray-300"></i>
                    <p>No refunds found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    financialData.recent_refunds.forEach(refund => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 transition-colors';
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${refund.id.substring(0, 8)}...</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${refund.order_number}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${refund.customer_name}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-red-600">₹${formatCurrency(refund.amount)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${refund.reason}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${getRefundStatusBadge(refund.status)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${formatDate(refund.created_at)}
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Switch between tabs
function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-primary-500', 'text-primary-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    document.querySelector(`[data-tab="${tabName}"]`).classList.remove('border-transparent', 'text-gray-500');
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('border-primary-500', 'text-primary-600');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    document.getElementById(`${tabName}-tab`).classList.remove('hidden');
    currentTab = tabName;
}

// Utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function getInvoiceStatusBadge(status) {
    const badges = {
        'Draft': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Draft</span>',
        'Finalized': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Finalized</span>'
    };
    return badges[status] || `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">${status}</span>`;
}

function getPaymentStatusBadge(status) {
    const badges = {
        'created': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Created</span>',
        'authorized': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Authorized</span>',
        'captured': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Captured</span>',
        'failed': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>'
    };
    return badges[status] || `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">${status}</span>`;
}

function getRefundStatusBadge(status) {
    const badges = {
        'pending': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>',
        'processing': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Processing</span>',
        'completed': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Completed</span>',
        'failed': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>'
    };
    return badges[status] || `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">${status}</span>`;
}

function getOrderStatusBadge(status) {
    const badges = {
        'Payment_Successful': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Paid</span>',
        'Pending_Vendor_Approval': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>',
        'Auto_Approved': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Auto Approved</span>',
        'Active_Rental': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>',
        'Completed': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Completed</span>',
        'Rejected': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>',
        'Refunded': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Refunded</span>'
    };
    return badges[status] || `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">${status}</span>`;
}

// State management functions
function showLoadingState() {
    document.getElementById('loading-state').classList.remove('hidden');
    document.getElementById('financial-content').classList.add('hidden');
    document.getElementById('error-state').classList.add('hidden');
}

function showFinancialContent() {
    document.getElementById('loading-state').classList.add('hidden');
    document.getElementById('financial-content').classList.remove('hidden');
    document.getElementById('error-state').classList.add('hidden');
    
    // Set default tab
    switchTab('invoices');
}

function showErrorState(message) {
    document.getElementById('loading-state').classList.add('hidden');
    document.getElementById('financial-content').classList.add('hidden');
    document.getElementById('error-state').classList.remove('hidden');
    document.getElementById('error-message').textContent = message;
}

// Action functions
function refreshFinancialData() {
    loadFinancialData();
}

function exportFinancialReport() {
    // TODO: Implement export functionality
    alert('Export functionality will be implemented in a future update.');
}

function downloadInvoice(invoiceId) {
    // TODO: Implement invoice download
    alert('Invoice download functionality will be implemented in a future update.');
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>