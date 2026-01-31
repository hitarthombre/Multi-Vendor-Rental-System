<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Services\ReportingService;
use RentalPlatform\Repositories\VendorRepository;

Session::start();
Middleware::requireVendor();

$userId = Session::get('user_id');
$vendorRepo = new VendorRepository();
$vendor = $vendorRepo->findByUserId($userId);

if (!$vendor) {
    header('Location: /index.php');
    exit;
}

$reportingService = new ReportingService();

// Get date range from query params or default to last 30 days
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Validate access
if (!$reportingService->validateReportAccess($userId, 'Vendor', $vendor->getId())) {
    http_response_code(403);
    die('Access denied');
}

// Get vendor report
$report = $reportingService->getVendorReport($vendor->getId(), [
    'start_date' => $startDate,
    'end_date' => $endDate
]);

$pageTitle = 'Vendor Reports';
$showNav = true;
$showContainer = true;

ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Reports & Analytics</h1>
            <p class="text-muted-foreground mt-2">Performance insights for your rental business</p>
        </div>
        <div class="flex gap-2">
            <button onclick="exportReport('csv')" class="btn btn-outline">
                <i class="fas fa-file-csv mr-2"></i>Export CSV
            </button>
            <button onclick="exportReport('pdf')" class="btn btn-outline">
                <i class="fas fa-file-pdf mr-2"></i>Export PDF
            </button>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card p-6">
        <form method="GET" class="flex items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium mb-2">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" 
                       class="input w-full" required>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium mb-2">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" 
                       class="input w-full" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter mr-2"></i>Apply Filter
            </button>
        </form>
    </div>

    <!-- Rental Volume -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Rental Volume</h2>
            <p class="text-sm text-muted-foreground">Overview of your rental activity</p>
        </div>
        <div class="p-6">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600"><?= $report['rental_volume']['total_orders'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Total Orders</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-green-600"><?= $report['rental_volume']['active_rentals'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Active Rentals</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-purple-600"><?= $report['rental_volume']['completed_rentals'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Completed</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-red-600"><?= $report['rental_volume']['rejected_orders'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Rejected</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Summary -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Revenue Summary</h2>
            <p class="text-sm text-muted-foreground">Financial performance from verified payments</p>
        </div>
        <div class="p-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-green-600">₹<?= number_format($report['revenue']['total_revenue'] ?? 0, 2) ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Total Revenue</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600">₹<?= number_format($report['revenue']['avg_order_value'] ?? 0, 2) ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Avg Order Value</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-purple-600"><?= $report['revenue']['unique_customers'] ?? 0 ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Unique Customers</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Performance -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Product Performance</h2>
            <p class="text-sm text-muted-foreground">Top performing products by order count</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php if (empty($report['product_performance'])): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-muted-foreground">
                                No product data available for this period
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($report['product_performance'] as $product): ?>
                            <tr class="hover:bg-muted/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium"><?= htmlspecialchars($product['name']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= $product['order_count'] ?> orders
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium">
                                    ₹<?= number_format($product['total_revenue'] ?? 0, 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Approval Statistics -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Approval Statistics</h2>
            <p class="text-sm text-muted-foreground">Order approval performance metrics</p>
        </div>
        <div class="p-6">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold"><?= $report['approval_stats']['total_requiring_approval'] ?? 0 ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Total Requiring Approval</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-green-600"><?= $report['approval_stats']['approved'] ?? 0 ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Approved</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-red-600"><?= $report['approval_stats']['rejected'] ?? 0 ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Rejected</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600">
                        <?php 
                        $avgTime = $report['approval_stats']['avg_approval_time_hours'] ?? 0;
                        echo $avgTime ? round($avgTime, 1) . 'h' : 'N/A';
                        ?>
                    </div>
                    <div class="text-sm text-muted-foreground mt-1">Avg Approval Time</div>
                </div>
            </div>
            <?php 
            $total = $report['approval_stats']['total_requiring_approval'] ?? 0;
            $approved = $report['approval_stats']['approved'] ?? 0;
            $approvalRate = $total > 0 ? round(($approved / $total) * 100, 1) : 0;
            ?>
            <div class="mt-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">Approval Rate</span>
                    <span class="text-sm font-bold"><?= $approvalRate ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-green-600 h-2.5 rounded-full" style="width: <?= $approvalRate ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Trends -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Daily Trends</h2>
            <p class="text-sm text-muted-foreground">Day-by-day performance breakdown</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php if (empty($report['daily_trends'])): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-muted-foreground">
                                No data available for this period
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($report['daily_trends'] as $trend): ?>
                            <tr class="hover:bg-muted/50 transition-colors">
                                <td class="px-6 py-4 font-medium">
                                    <?= date('M d, Y', strtotime($trend['date'])) ?>
                                </td>
                                <td class="px-6 py-4"><?= $trend['order_count'] ?></td>
                                <td class="px-6 py-4 font-medium">₹<?= number_format($trend['daily_revenue'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function exportReport(format) {
    const startDate = '<?= htmlspecialchars($startDate) ?>';
    const endDate = '<?= htmlspecialchars($endDate) ?>';
    const url = `/api/vendor-reports.php?action=export&format=${format}&start_date=${startDate}&end_date=${endDate}`;
    window.location.href = url;
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/modern-base.php';
?>
