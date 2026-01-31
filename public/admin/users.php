<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Database\Connection;

Session::start();
Middleware::requireAdministrator();

$db = Connection::getInstance();

// Get all users
$stmt = $db->query("
    SELECT u.*, 
           CASE 
               WHEN u.role = 'Vendor' THEN v.business_name
               ELSE NULL
           END as business_name
    FROM users u
    LEFT JOIN vendors v ON u.id = v.user_id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

// Count by role
$roleCount = [
    'Administrator' => 0,
    'Vendor' => 0,
    'Customer' => 0
];
foreach ($users as $user) {
    $roleCount[$user['role']]++;
}

$pageTitle = 'Manage Users';
$showNav = true;
$showContainer = true;
$showFooter = true;

ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Users</h1>
            <p class="text-muted-foreground mt-2">Manage all user accounts</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-4 md:grid-cols-4">
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Total Users</p>
                    <p class="text-2xl font-bold"><?= count($users) ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Administrators</p>
                    <p class="text-2xl font-bold"><?= $roleCount['Administrator'] ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
                    <i class="fas fa-user-shield text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Vendors</p>
                    <p class="text-2xl font-bold"><?= $roleCount['Vendor'] ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-store text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Customers</p>
                    <p class="text-2xl font-bold"><?= $roleCount['Customer'] ?></p>
                </div>
                <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-user text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">All Users</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-primary text-primary-foreground flex items-center justify-center font-semibold">
                                        <?= strtoupper(substr($user['username'], 0, 2)) ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-medium"><?= htmlspecialchars($user['username']) ?></div>
                                        <?php if ($user['business_name']): ?>
                                            <div class="text-sm text-muted-foreground"><?= htmlspecialchars($user['business_name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?= htmlspecialchars($user['email']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $roleColors = [
                                    'Administrator' => 'bg-red-100 text-red-800',
                                    'Vendor' => 'bg-green-100 text-green-800',
                                    'Customer' => 'bg-blue-100 text-blue-800'
                                ];
                                $roleIcons = [
                                    'Administrator' => 'fa-user-shield',
                                    'Vendor' => 'fa-store',
                                    'Customer' => 'fa-user'
                                ];
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $roleColors[$user['role']] ?>">
                                    <i class="fas <?= $roleIcons[$user['role']] ?> mr-1"></i>
                                    <?= $user['role'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">
                                <?= date('M d, Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <a href="/Multi-Vendor-Rental-System/public/admin/user-details.php?id=<?= $user['id'] ?>" 
                                   class="text-primary hover:text-primary/80 text-sm font-medium">
                                    View Details →
                                </a>
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
