<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Services\ReportingService;

Session::start();
Middleware::requireAdministrator();

$reportingService = new ReportingService();

// Get date range from query params or default to last 30 days
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get admin report
$report = $reportingService->getAdminReport([
    'start_date' => $startDate,
    'end_date' => $endDate
]);

$pageTitle = 'Platform Reports';
$showNav = true;
$showContainer = true;

ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Platform Reports</h1>
            <p class="text-muted-foreground mt-2">Comprehensive platform-wide analytics and insights</p>
        </div>
        <div class="flex gap-2">
            <button onclick="exportReport('csv')" class="btn btn-outline">
                <i class="fas fa-file-csv mr-2"></i>Export CSV
            </button>
            <button onclick="exportReport('pdf')" class="btn btn-outline">
                <i class="fas fa-file-pdf mr-2"></i>Export PDF
            </button>
            <button onclick="exportReport('excel')" class="btn btn-outline">
                <i class="fas fa-file-excel mr-2"></i>Export Excel
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

    <!-- Platform-Wide Rentals -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Platform-Wide Rentals</h2>
            <p class="text-sm text-muted-foreground">Overview of all rental activity across the platform</p>
        </div>
        <div class="p-6">
            <div class="grid gap-4 md:grid-cols-5">
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600"><?= $report['platform_rentals']['total_orders'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Total Orders</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-green-600"><?= $report['platform_rentals']['active_rentals'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Active Rentals</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-purple-600"><?= $report['platform_rentals']['completed_rentals'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Completed</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-red-600"><?= $report['platform_rentals']['rejected_orders'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Rejected</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-yellow-600"><?= $report['platform_rentals']['pending_approval'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Pending Approval</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Statistics -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Payment Success Rates</h2>
            <p class="text-sm text-muted-foreground">Payment processing performance from verified records</p>
        </div>
        <div class="p-6">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold"><?= $report['payment_stats']['total_payments'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Total Payments</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-green-600"><?= $report['payment_stats']['verified_payments'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Verified</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-red-600"><?= $report['payment_stats']['failed_payments'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Failed</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600">₹<?= number_format($report['payment_stats']['total_revenue'], 2) ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Total Revenue</div>
                </div>
            </div>
            <div class="mt-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">Payment Success Rate</span>
                    <span class="text-sm font-bold"><?= number_format($report['payment_stats']['success_rate'], 1) ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-green-600 h-2.5 rounded-full" style="width: <?= $report['payment_stats']['success_rate'] ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Refund Statistics -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Refund Frequency</h2>
            <p class="text-sm text-muted-foreground">Refund metrics from immutable invoice records</p>
        </div>
        <div class="p-6">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold"><?= $report['refund_stats']['total_refunds'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Total Refunds</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-red-600">₹<?= number_format($report['refund_stats']['total_refunded'], 2) ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Amount Refunded</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold"><?= $report['refund_stats']['total_orders'] ?></div>
                    <div class="text-sm text-muted-foreground mt-1">Total Orders</div>
                </div>
                <div class="text-center p-4 bg-muted/50 rounded-lg">
                    <div class="text-3xl font-bold text-orange-600"><?= number_format($report['refund_stats']['refund_rate'], 1) ?>%</div>
                    <div class="text-sm text-muted-foreground mt-1">Refund Rate</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Activity -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Vendor Activity</h2>
            <p class="text-sm text-muted-foreground">Top performing vendors by order volume</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Products</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Revenue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Approval Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php if (empty($report['vendor_activity'])): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-muted-foreground">
                                No vendor data available for this period
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($report['vendor_activity'] as $vendor): ?>
                            <tr class="hover:bg-muted/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium"><?= htmlspecialchars($vendor['business_name']) ?></div>
                                </td>
                                <td class="px-6 py-4"><?= $vendor['product_count'] ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= $vendor['order_count'] ?> orders
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium">₹<?= number_format($vendor['total_revenue'] ?? 0, 2) ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                                            <div class="bg-green-600 h-2 rounded-full" style="width: <?= round($vendor['approval_rate']) ?>%"></div>
                                        </div>
                                        <span class="text-sm font-medium"><?= number_format($vendor['approval_rate'], 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daily Trends -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Daily Trends</h2>
            <p class="text-sm text-muted-foreground">Day-by-day platform performance</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Revenue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Payments</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Verified</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php if (empty($report['daily_trends'])): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-muted-foreground">
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
                                <td class="px-6 py-4"><?= $trend['payment_count'] ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <?= $trend['verified_payments'] ?> verified
                                    </span>
                                </td>
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
    const url = `/api/admin-reports.php?action=export&format=${format}&start_date=${startDate}&end_date=${endDate}`;
    window.location.href = url;
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/modern-base.php';
?>
