<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Services\AdminAnalyticsService;

Session::start();
Middleware::requireAdministrator();

$analyticsService = new AdminAnalyticsService();

// Get analytics data
$overview = $analyticsService->getPlatformOverview();
$vendorActivity = $analyticsService->getVendorActivityStats();
$paymentTrends = $analyticsService->getPaymentTrends(30);
$rentalTrends = $analyticsService->getRentalTrends(30);
$refundStats = $analyticsService->getRefundStats();

$pageTitle = 'Platform Analytics';
$showNav = true;
$showContainer = true;

ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold tracking-tight">Platform Analytics</h1>
        <p class="text-muted-foreground mt-2">Comprehensive platform performance metrics and insights</p>
    </div>

    <!-- Key Metrics -->
    <div class="grid gap-4 md:grid-cols-4">
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Total Revenue</p>
                    <p class="text-2xl font-bold">₹<?= number_format($overview['total_revenue'], 2) ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Total Orders</p>
                    <p class="text-2xl font-bold"><?= number_format($overview['total_orders']) ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Payment Success Rate</p>
                    <p class="text-2xl font-bold">
                        <?= $overview['total_payments'] > 0 ? round(($overview['verified_payments'] / $overview['total_payments']) * 100, 1) : 0 ?>%
                    </p>
                </div>
                <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-check-circle text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Refund Rate</p>
                    <p class="text-2xl font-bold"><?= number_format($refundStats['refund_rate'], 1) ?>%</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
                    <i class="fas fa-undo text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid gap-6 md:grid-cols-2">
        <!-- Payment Trends -->
        <div class="card">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold">Payment Trends (Last 30 Days)</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <?php foreach (array_slice($paymentTrends, 0, 10) as $trend): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="text-sm font-medium"><?= date('M d', strtotime($trend['date'])) ?></div>
                                <div class="text-xs text-muted-foreground">
                                    <?= $trend['verified_count'] ?> verified, <?= $trend['failed_count'] ?> failed
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold">₹<?= number_format($trend['daily_revenue'], 2) ?></div>
                                <div class="text-xs text-muted-foreground"><?= $trend['payment_count'] ?> payments</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Rental Trends -->
        <div class="card">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold">Rental Trends (Last 30 Days)</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <?php foreach (array_slice($rentalTrends, 0, 10) as $trend): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="text-sm font-medium"><?= date('M d', strtotime($trend['date'])) ?></div>
                                <div class="text-xs text-muted-foreground">
                                    <?= $trend['active_count'] ?> active, <?= $trend['completed_count'] ?> completed
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold"><?= $trend['order_count'] ?> orders</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Activity -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Top Vendor Activity</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Products</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Total Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Completed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Pending</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php foreach ($vendorActivity as $vendor): ?>
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium"><?= htmlspecialchars($vendor['business_name']) ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm"><?= $vendor['product_count'] ?></td>
                            <td class="px-6 py-4 text-sm font-medium"><?= $vendor['order_count'] ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <?= $vendor['completed_orders'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <?= $vendor['pending_orders'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Refund Statistics -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Recent Refunds</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php foreach ($refundStats['recent_refunds'] as $refund): ?>
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium"><?= htmlspecialchars($refund['order_number']) ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($refund['customer_name']) ?></td>
                            <td class="px-6 py-4 text-sm font-medium">₹<?= number_format($refund['amount'], 2) ?></td>
                            <td class="px-6 py-4">
                                <?php
                                $statusColors = [
                                    'Completed' => 'bg-green-100 text-green-800',
                                    'In_Progress' => 'bg-yellow-100 text-yellow-800',
                                    'Initiated' => 'bg-blue-100 text-blue-800',
                                    'Failed' => 'bg-red-100 text-red-800'
                                ];
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$refund['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                    <?= htmlspecialchars($refund['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">
                                <?= date('M d, Y', strtotime($refund['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/modern-base.php';
?>
