<?php
use RentalPlatform\Auth\Session;

// Get current user from session
$currentUser = Session::isAuthenticated() ? Session::getUser() : null;
$userRole = $currentUser ? $currentUser['role'] : null;
?>

<nav class="bg-white shadow-sm" x-data="{ mobileMenuOpen: false, userMenuOpen: false }">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <!-- Logo and primary navigation -->
            <div class="flex">
                <!-- Logo -->
                <div class="flex flex-shrink-0 items-center">
                    <a href="/Multi-Vendor-Rental-System/public/index.php" class="flex items-center space-x-2">
                        <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                            <i class="fas fa-store text-white text-xl"></i>
                        </div>
                        <span class="text-xl font-bold text-gray-900">RentalHub</span>
                    </a>
                </div>
                
                <!-- Desktop navigation -->
                <div class="hidden sm:ml-8 sm:flex sm:space-x-4">
                    <?php if ($userRole === 'Vendor'): ?>
                        <a href="/Multi-Vendor-Rental-System/public/vendor/dashboard.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-900 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-th-large mr-2"></i>Dashboard
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/vendor/products.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-box mr-2"></i>Products
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/vendor/orders.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-shopping-bag mr-2"></i>Orders
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/vendor/analytics.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-chart-line mr-2"></i>Analytics
                        </a>
                    <?php elseif ($userRole === 'Customer'): ?>
                        <a href="/Multi-Vendor-Rental-System/public/customer/browse.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-900 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-search mr-2"></i>Browse
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/customer/cart.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-shopping-cart mr-2"></i>Cart
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/customer/orders.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-list mr-2"></i>My Orders
                        </a>
                    <?php elseif ($userRole === 'Administrator'): ?>
                        <a href="/Multi-Vendor-Rental-System/public/admin/dashboard.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-900 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/admin/users.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-users mr-2"></i>Users
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/admin/vendors.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-store mr-2"></i>Vendors
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/admin/categories.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-folder mr-2"></i>Categories
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/admin/audit-logs.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-clipboard-list mr-2"></i>Audit Logs
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/admin/settings.php" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-primary-500 hover:text-primary-600">
                            <i class="fas fa-cog mr-2"></i>Settings
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right side navigation -->
            <div class="hidden sm:ml-6 sm:flex sm:items-center space-x-4">
                <?php if ($currentUser): ?>
                    <!-- Notifications -->
                    <button type="button" class="relative rounded-full bg-white p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        <span class="sr-only">View notifications</span>
                        <i class="fas fa-bell text-xl"></i>
                        <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
                    </button>
                    
                    <!-- Profile dropdown -->
                    <div class="relative" @click.away="userMenuOpen = false">
                        <button @click="userMenuOpen = !userMenuOpen" type="button" class="flex items-center space-x-3 rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            <span class="sr-only">Open user menu</span>
                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-semibold">
                                <?= strtoupper(substr($currentUser['username'], 0, 2)) ?>
                            </div>
                            <span class="hidden lg:block text-sm font-medium text-gray-700"><?= htmlspecialchars($currentUser['username']) ?></span>
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </button>
                        
                        <!-- Dropdown menu -->
                        <div x-show="userMenuOpen" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                            <div class="py-1">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($currentUser['username']) ?></p>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($currentUser['email'] ?? '') ?></p>
                                    <span class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-800 mt-1">
                                        <?= htmlspecialchars($userRole) ?>
                                    </span>
                                </div>
                                <a href="/Multi-Vendor-Rental-System/public/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2 text-gray-400"></i>Your Profile
                                </a>
                                <a href="/Multi-Vendor-Rental-System/public/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cog mr-2 text-gray-400"></i>Settings
                                </a>
                                <a href="/Multi-Vendor-Rental-System/public/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Sign out
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/Multi-Vendor-Rental-System/public/login.php" class="text-sm font-medium text-gray-700 hover:text-primary-600">
                        Sign in
                    </a>
                    <a href="/Multi-Vendor-Rental-System/public/register.php" class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        Get Started
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Mobile menu button -->
            <div class="flex items-center sm:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500">
                    <span class="sr-only">Open main menu</span>
                    <i class="fas" :class="mobileMenuOpen ? 'fa-times' : 'fa-bars'"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mobile menu -->
    <div x-show="mobileMenuOpen" x-cloak class="sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
            <?php if ($userRole === 'Vendor'): ?>
                <a href="/Multi-Vendor-Rental-System/public/vendor/dashboard.php" class="block border-l-4 border-primary-500 bg-primary-50 py-2 pl-3 pr-4 text-base font-medium text-primary-700">Dashboard</a>
                <a href="/Multi-Vendor-Rental-System/public/vendor/products.php" class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800">Products</a>
                <a href="/Multi-Vendor-Rental-System/public/vendor/orders.php" class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800">Orders</a>
            <?php endif; ?>
        </div>
        
        <?php if ($currentUser): ?>
            <div class="border-t border-gray-200 pb-3 pt-4">
                <div class="flex items-center px-4">
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-semibold">
                        <?= strtoupper(substr($currentUser['username'], 0, 2)) ?>
                    </div>
                    <div class="ml-3">
                        <div class="text-base font-medium text-gray-800"><?= htmlspecialchars($currentUser['username']) ?></div>
                        <div class="text-sm font-medium text-gray-500"><?= htmlspecialchars($currentUser['email'] ?? '') ?></div>
                    </div>
                </div>
                <div class="mt-3 space-y-1">
                    <a href="/Multi-Vendor-Rental-System/public/profile.php" class="block px-4 py-2 text-base font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800">Your Profile</a>
                    <a href="/Multi-Vendor-Rental-System/public/settings.php" class="block px-4 py-2 text-base font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800">Settings</a>
                    <a href="/Multi-Vendor-Rental-System/public/logout.php" class="block px-4 py-2 text-base font-medium text-red-600 hover:bg-red-50">Sign out</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>
