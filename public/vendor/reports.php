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

$pageTitle = 'Reports & Analytics';
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Reports & Analytics</h1>
            <p class="mt-2 text-gray-600">Track your business performance with detailed reports</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="refreshCurrentReport()" 
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>
                Refresh
            </button>
            <button onclick="exportCurrentReport()" 
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
                <i class="fas fa-download mr-2"></i>
                Export Report
            </button>
        </div>
    </div>
</div>

<!-- Report Controls -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Report Type -->
        <div>
            <label for="report-type" class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
            <select id="report-type" onchange="changeReportType()" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="rental_volume">Rental Volume</option>
                <option value="revenue">Revenue Analysis</option>
                <option value="product_performance">Product Performance</option>
            </select>
        </div>

        <!-- Period (for time-based reports) -->
        <div id="period-control">
            <label for="period" class="block text-sm font-medium text-gray-700 mb-2">Period</label>
            <select id="period" onchange="loadCurrentReport()" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly" selected>Monthly</option>
            </select>
        </div>

        <!-- Start Date -->
        <div>
            <label for="start-date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
            <input type="date" id="start-date" onchange="loadCurrentReport()" 
                   value="<?= date('Y-m-01') ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>

        <!-- End Date -->
        <div>
            <label for="end-date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
            <input type="date" id="end-date" onchange="loadCurrentReport()" 
                   value="<?= date('Y-m-t') ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>
    </div>
</div>

<!-- Loading State -->
<div id="loading-state" class="text-center py-12">
    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    <p class="mt-2 text-gray-600">Loading report data...</p>
</div>

<!-- Report Content -->
<div id="report-content" class="hidden">
    <!-- Summary Cards -->
    <div id="summary-cards" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Summary cards will be populated here -->
    </div>

    <!-- Chart Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 id="chart-title" class="text-xl font-bold text-gray-900">Trend Analysis</h2>
            <div class="flex space-x-2">
                <button onclick="toggleChartType('line')" 
                        class="chart-type-btn px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50"
                        data-type="line">Line</button>
                <button onclick="toggleChartType('bar')" 
                        class="chart-type-btn px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 bg-primary-50 border-primary-300"
                        data-type="bar">Bar</button>
            </div>
        </div>
        <div class="h-96">
            <canvas id="trend-chart"></canvas>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h2 id="table-title" class="text-xl font-bold text-gray-900">Detailed Data</h2>
                <span id="table-count" class="text-sm text-gray-500">0 records</span>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead id="table-header" class="bg-gray-50">
                    <!-- Table headers will be populated here -->
                </thead>
                <tbody id="table-body" class="bg-white divide-y divide-gray-200">
                    <!-- Table data will be populated here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Error State -->
<div id="error-state" class="hidden text-center py-12">
    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">Error Loading Report</h3>
    <p id="error-message" class="text-gray-500 mb-6">Unable to load report data. Please try again.</p>
    <button onclick="loadCurrentReport()" 
            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
        <i class="fas fa-retry mr-2"></i>
        Try Again
    </button>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let currentReportType = 'rental_volume';
let currentChart = null;
let currentChartType = 'bar';
let reportData = null;

// Load report on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCurrentReport();
});

// Change report type
function changeReportType() {
    currentReportType = document.getElementById('report-type').value;
    
    // Show/hide period control for product performance report
    const periodControl = document.getElementById('period-control');
    if (currentReportType === 'product_performance') {
        periodControl.style.display = 'none';
    } else {
        periodControl.style.display = 'block';
    }
    
    loadCurrentReport();
}

// Load current report
async function loadCurrentReport() {
    try {
        showLoadingState();
        
        const params = new URLSearchParams({
            type: currentReportType,
            start_date: document.getElementById('start-date').value,
            end_date: document.getElementById('end-date').value
        });
        
        if (currentReportType !== 'product_performance') {
            params.append('period', document.getElementById('period').value);
        }
        
        const response = await fetch(`/Multi-Vendor-Rental-System/public/api/vendor-reports.php?${params}`);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Failed to load report data');
        }
        
        reportData = result.data;
        updateReportDisplay();
        showReportContent();
        
    } catch (error) {
        console.error('Error loading report:', error);
        showErrorState(error.message);
    }
}

// Update report display
function updateReportDisplay() {
    updateSummaryCards();
    updateChart();
    updateTable();
}

// Update summary cards
function updateSummaryCards() {
    const container = document.getElementById('summary-cards');
    container.innerHTML = '';
    
    const summary = reportData.summary;
    let cards = [];
    
    switch (currentReportType) {
        case 'rental_volume':
            cards = [
                {
                    title: 'Total Orders',
                    value: summary.total_orders,
                    icon: 'fas fa-shopping-cart',
                    color: 'blue'
                },
                {
                    title: 'Completed Orders',
                    value: summary.completed_orders,
                    icon: 'fas fa-check-circle',
                    color: 'green'
                },
                {
                    title: 'Completion Rate',
                    value: summary.completion_rate + '%',
                    icon: 'fas fa-percentage',
                    color: 'purple'
                },
                {
                    title: 'Rejection Rate',
                    value: summary.rejection_rate + '%',
                    icon: 'fas fa-times-circle',
                    color: 'red'
                }
            ];
            break;
            
        case 'revenue':
            cards = [
                {
                    title: 'Gross Revenue',
                    value: '₹' + formatCurrency(summary.gross_revenue),
                    icon: 'fas fa-rupee-sign',
                    color: 'green'
                },
                {
                    title: 'Net Revenue',
                    value: '₹' + formatCurrency(summary.net_revenue),
                    icon: 'fas fa-chart-line',
                    color: 'blue'
                },
                {
                    title: 'Avg Order Value',
                    value: '₹' + formatCurrency(summary.avg_order_value),
                    icon: 'fas fa-calculator',
                    color: 'purple'
                },
                {
                    title: 'Total Refunds',
                    value: '₹' + formatCurrency(summary.total_refunds),
                    icon: 'fas fa-undo',
                    color: 'red'
                }
            ];
            break;
            
        case 'product_performance':
            cards = [
                {
                    title: 'Total Products',
                    value: summary.total_products,
                    icon: 'fas fa-box',
                    color: 'blue'
                },
                {
                    title: 'Products with Orders',
                    value: summary.products_with_orders,
                    icon: 'fas fa-check-circle',
                    color: 'green'
                },
                {
                    title: 'Total Revenue',
                    value: '₹' + formatCurrency(summary.total_revenue),
                    icon: 'fas fa-rupee-sign',
                    color: 'purple'
                },
                {
                    title: 'Avg Order Value',
                    value: '₹' + formatCurrency(summary.avg_order_value),
                    icon: 'fas fa-calculator',
                    color: 'yellow'
                }
            ];
            break;
    }
    
    cards.forEach(card => {
        const cardElement = document.createElement('div');
        cardElement.className = 'bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow';
        cardElement.innerHTML = `
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">${card.title}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">${card.value}</p>
                </div>
                <div class="w-12 h-12 bg-${card.color}-100 rounded-lg flex items-center justify-center">
                    <i class="${card.icon} text-${card.color}-600 text-xl"></i>
                </div>
            </div>
        `;
        container.appendChild(cardElement);
    });
}

// Update chart
function updateChart() {
    const ctx = document.getElementById('trend-chart').getContext('2d');
    
    if (currentChart) {
        currentChart.destroy();
    }
    
    // Don't show chart if no data
    if (!reportData.data || reportData.data.length === 0) {
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        ctx.font = '16px Arial';
        ctx.fillStyle = '#9CA3AF';
        ctx.textAlign = 'center';
        ctx.fillText('No data available for chart', ctx.canvas.width / 2, ctx.canvas.height / 2);
        return;
    }
    
    let chartData, chartOptions;
    
    switch (currentReportType) {
        case 'rental_volume':
            chartData = {
                labels: reportData.data.map(item => item.period),
                datasets: [
                    {
                        label: 'Total Orders',
                        data: reportData.data.map(item => item.total_orders),
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 2
                    },
                    {
                        label: 'Completed Orders',
                        data: reportData.data.map(item => item.completed_orders),
                        backgroundColor: 'rgba(34, 197, 94, 0.5)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 2
                    }
                ]
            };
            break;
            
        case 'revenue':
            chartData = {
                labels: reportData.data.map(item => item.period),
                datasets: [
                    {
                        label: 'Gross Revenue (₹)',
                        data: reportData.data.map(item => item.gross_revenue),
                        backgroundColor: 'rgba(34, 197, 94, 0.5)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 2
                    },
                    {
                        label: 'Net Revenue (₹)',
                        data: reportData.data.map(item => item.net_revenue),
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 2
                    }
                ]
            };
            break;
            
        case 'product_performance':
            // For product performance, show top 10 products by revenue
            const topProducts = reportData.data.slice(0, 10);
            chartData = {
                labels: topProducts.map(item => item.product_name),
                datasets: [
                    {
                        label: 'Total Revenue (₹)',
                        data: topProducts.map(item => item.total_revenue),
                        backgroundColor: 'rgba(147, 51, 234, 0.5)',
                        borderColor: 'rgb(147, 51, 234)',
                        borderWidth: 2
                    }
                ]
            };
            break;
    }
    
    chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    };
    
    currentChart = new Chart(ctx, {
        type: currentChartType,
        data: chartData,
        options: chartOptions
    });
}

// Update table
function updateTable() {
    const header = document.getElementById('table-header');
    const body = document.getElementById('table-body');
    const count = document.getElementById('table-count');
    
    header.innerHTML = '';
    body.innerHTML = '';
    
    let headers = [];
    
    switch (currentReportType) {
        case 'rental_volume':
            headers = ['Period', 'Total Orders', 'Completed', 'Active', 'Rejected', 'Pending', 'Completion Rate', 'Avg Duration'];
            break;
        case 'revenue':
            headers = ['Period', 'Orders', 'Gross Revenue', 'Net Revenue', 'Deposits', 'Refunds', 'Avg Order Value'];
            break;
        case 'product_performance':
            headers = ['Product', 'Orders', 'Revenue', 'Completion Rate', 'Avg Duration', 'Unique Customers'];
            break;
    }
    
    // Create header row
    const headerRow = document.createElement('tr');
    headers.forEach(header => {
        const th = document.createElement('th');
        th.className = 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
        th.textContent = header;
        headerRow.appendChild(th);
    });
    header.appendChild(headerRow);
    
    // Create data rows
    reportData.data.forEach(item => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 transition-colors';
        
        let cells = [];
        
        switch (currentReportType) {
            case 'rental_volume':
                cells = [
                    item.period,
                    item.total_orders,
                    item.completed_orders,
                    item.active_orders,
                    item.rejected_orders,
                    item.pending_orders,
                    item.completion_rate + '%',
                    (item.avg_rental_duration_days || 0) + ' days'
                ];
                break;
            case 'revenue':
                cells = [
                    item.period,
                    item.total_orders,
                    '₹' + formatCurrency(item.gross_revenue),
                    '₹' + formatCurrency(item.net_revenue),
                    '₹' + formatCurrency(item.total_deposits),
                    '₹' + formatCurrency(item.total_refunds),
                    '₹' + formatCurrency(item.avg_order_value)
                ];
                break;
            case 'product_performance':
                cells = [
                    item.product_name,
                    item.total_orders,
                    '₹' + formatCurrency(item.total_revenue),
                    item.completion_rate + '%',
                    (item.avg_rental_duration_days || 0) + ' days',
                    item.unique_customers
                ];
                break;
        }
        
        cells.forEach(cellData => {
            const td = document.createElement('td');
            td.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-900';
            td.textContent = cellData;
            row.appendChild(td);
        });
        
        body.appendChild(row);
    });
    
    // Show empty state if no data
    if (reportData.data.length === 0) {
        const row = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = headers.length;
        td.className = 'px-6 py-12 text-center text-gray-500';
        td.innerHTML = `
            <div class="flex flex-col items-center">
                <i class="fas fa-chart-bar text-4xl mb-4 text-gray-300"></i>
                <p class="text-lg font-medium">No data available</p>
                <p class="text-sm">Try adjusting your date range or filters</p>
            </div>
        `;
        row.appendChild(td);
        body.appendChild(row);
    }
    
    count.textContent = `${reportData.data.length} records`;
}

// Toggle chart type
function toggleChartType(type) {
    currentChartType = type;
    
    // Update button states
    document.querySelectorAll('.chart-type-btn').forEach(btn => {
        btn.classList.remove('bg-primary-50', 'border-primary-300');
        btn.classList.add('border-gray-300');
    });
    
    document.querySelector(`[data-type="${type}"]`).classList.add('bg-primary-50', 'border-primary-300');
    document.querySelector(`[data-type="${type}"]`).classList.remove('border-gray-300');
    
    updateChart();
}

// Utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

// State management functions
function showLoadingState() {
    document.getElementById('loading-state').classList.remove('hidden');
    document.getElementById('report-content').classList.add('hidden');
    document.getElementById('error-state').classList.add('hidden');
}

function showReportContent() {
    document.getElementById('loading-state').classList.add('hidden');
    document.getElementById('report-content').classList.remove('hidden');
    document.getElementById('error-state').classList.add('hidden');
}

function showErrorState(message) {
    document.getElementById('loading-state').classList.add('hidden');
    document.getElementById('report-content').classList.add('hidden');
    document.getElementById('error-state').classList.remove('hidden');
    document.getElementById('error-message').textContent = message;
}

// Action functions
function refreshCurrentReport() {
    loadCurrentReport();
}

function exportCurrentReport() {
    if (!reportData || !reportData.data) {
        alert('No data to export. Please load a report first.');
        return;
    }
    
    let csvContent = '';
    let headers = [];
    
    // Define headers based on report type
    switch (currentReportType) {
        case 'rental_volume':
            headers = ['Period', 'Total Orders', 'Completed', 'Active', 'Rejected', 'Pending', 'Completion Rate (%)', 'Avg Duration (days)'];
            break;
        case 'revenue':
            headers = ['Period', 'Orders', 'Gross Revenue', 'Net Revenue', 'Deposits', 'Refunds', 'Avg Order Value'];
            break;
        case 'product_performance':
            headers = ['Product', 'Orders', 'Revenue', 'Completion Rate (%)', 'Avg Duration (days)', 'Unique Customers'];
            break;
    }
    
    // Add headers to CSV
    csvContent += headers.join(',') + '\n';
    
    // Add data rows
    reportData.data.forEach(item => {
        let row = [];
        
        switch (currentReportType) {
            case 'rental_volume':
                row = [
                    `"${item.period}"`,
                    item.total_orders,
                    item.completed_orders,
                    item.active_orders,
                    item.rejected_orders,
                    item.pending_orders,
                    item.completion_rate,
                    item.avg_rental_duration_days || 0
                ];
                break;
            case 'revenue':
                row = [
                    `"${item.period}"`,
                    item.total_orders,
                    item.gross_revenue,
                    item.net_revenue,
                    item.total_deposits,
                    item.total_refunds,
                    item.avg_order_value
                ];
                break;
            case 'product_performance':
                row = [
                    `"${item.product_name}"`,
                    item.total_orders,
                    item.total_revenue,
                    item.completion_rate,
                    item.avg_rental_duration_days || 0,
                    item.unique_customers
                ];
                break;
        }
        
        csvContent += row.join(',') + '\n';
    });
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    
    const reportName = currentReportType.replace('_', '-');
    const dateRange = `${document.getElementById('start-date').value}_to_${document.getElementById('end-date').value}`;
    link.setAttribute('download', `${reportName}-report-${dateRange}.csv`);
    
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>