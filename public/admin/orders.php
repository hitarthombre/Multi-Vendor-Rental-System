<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Database\Connection;
use RentalPlatform\Services\AdminAnalyticsService;

Session::start();
Middleware::requireAdministrator();

$db = Connection::getInstance();
$analyticsService = new AdminAnalyticsService();

// Get order flow statistics
$orderFlowStats = $analyticsService->getOrderFlowStats();

// Get all orders with details
$stmt = $db->query("
    SELECT o.*, 
           u.username as customer_name,
           v.business_name as vendor_name,
           p.status as payment_status
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    JOIN vendors v ON o.vendor_id = v.id
    LEFT JOIN payments p ON o.payment_id = p.id
    ORDER BY o.created_at DESC
    LIMIT 100
");
$orders = $stmt->fetchAll();

$pageTitle = 'Order Monitoring';
$showNav = true;
$showContainer = true;

ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold tracking-tight">Order Monitoring</h1>
        <p class="text-muted-foreground mt-2">Monitor order flows and identify bottlenecks</p>
    </div>

    <!-- Order Flow Stats -->
    <div class="grid gap-4 md:grid-cols-4">
        <?php foreach ($orderFlowStats['orders_by_status'] as $statusStat): ?>
            <?php
            $statusColors = [
                'Payment_Successful' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-check'],
                'Pending_Vendor_Approval' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'icon' => 'fa-clock'],
                'Auto_Approved' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-check-double'],
                'Active_Rental' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-play'],
                'Completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'fa-check-circle'],
                'Rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-times'],
                'Refunded' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'icon' => 'fa-undo']
            ];
            $colors = $statusColors[$statusStat['status']] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'fa-question'];
            ?>
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground"><?= str_replace('_', ' ', $statusStat['status']) ?></p>
                        <p class="text-2xl font-bold"><?= $statusStat['count'] ?></p>
                    </div>
                    <div class="h-12 w-12 rounded-full <?= $colors['bg'] ?> flex items-center justify-center">
                        <i class="fas <?= $colors['icon'] ?> <?= $colors['text'] ?> text-xl"></i>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottleneck Alert -->
    <?php if ($orderFlowStats['delayed_approvals'] > 0): ?>
        <div class="card bg-yellow-50 border-yellow-200 p-6">
            <div class="flex items-start gap-4">
                <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-yellow-900">Approval Bottleneck Detected</h3>
                    <p class="text-yellow-800 mt-1">
                        <?= $orderFlowStats['delayed_approvals'] ?> order(s) have been pending vendor approval for more than 24 hours.
                        Consider sending reminders to vendors.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Average Time by Status -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Average Time in Each Status</h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php foreach ($orderFlowStats['avg_time_by_status'] as $timeStat): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="text-sm font-medium"><?= str_replace('_', ' ', $timeStat['status']) ?></div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold"><?= round($timeStat['avg_hours'], 1) ?> hours</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold">Recent Orders</h2>
            <div class="flex gap-2">
                <select id="status-filter" class="px-3 py-2 border rounded-lg text-sm" onchange="filterOrders()">
                    <option value="">All Statuses</option>
                    <option value="Pending_Vendor_Approval">Pending Approval</option>
                    <option value="Active_Rental">Active Rentals</option>
                    <option value="Completed">Completed</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Order #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Time in Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $statusColors = [
                            'Payment_Successful' => 'bg-blue-100 text-blue-800',
                            'Pending_Vendor_Approval' => 'bg-yellow-100 text-yellow-800',
                            'Auto_Approved' => 'bg-green-100 text-green-800',
                            'Active_Rental' => 'bg-purple-100 text-purple-800',
                            'Completed' => 'bg-green-100 text-green-800',
                            'Rejected' => 'bg-red-100 text-red-800',
                            'Refunded' => 'bg-orange-100 text-orange-800'
                        ];
                        $hoursInStatus = round((strtotime('now') - strtotime($order['updated_at'])) / 3600, 1);
                        ?>
                        <tr class="hover:bg-muted/50 transition-colors order-row" data-status="<?= $order['status'] ?>">
                            <td class="px-6 py-4">
                                <div class="font-medium font-mono text-sm"><?= htmlspecialchars($order['order_number']) ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($order['vendor_name']) ?></td>
                            <td class="px-6 py-4 text-sm font-medium">₹<?= number_format($order['total_amount'], 2) ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                    <?= str_replace('_', ' ', $order['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">
                                <?= date('M d, Y H:i', strtotime($order['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="<?= $hoursInStatus > 24 ? 'text-red-600 font-bold' : 'text-muted-foreground' ?>">
                                    <?= $hoursInStatus ?> hrs
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterOrders() {
    const filter = document.getElementById('status-filter').value;
    const rows = document.querySelectorAll('.order-row');
    
    rows.forEach(row => {
        if (filter === '' || row.dataset.status === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/modern-base.php';
?>
