<?php
use RentalPlatform\Auth\Session;

$isLoggedIn = Session::isAuthenticated();
$user = $isLoggedIn ? Session::getUser() : null;
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>

<header class="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
    <nav class="container mx-auto px-4" x-data="{ mobileMenuOpen: false, userMenuOpen: false }">
        <div class="flex h-16 items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center gap-6">
                <a href="/Multi-Vendor-Rental-System/public/" class="flex items-center space-x-2 group">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground group-hover:scale-110 transition-transform">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                    </div>
                    <span class="hidden font-bold text-xl sm:inline-block gradient-text">RentalHub</span>
                </a>
                
                <!-- Desktop Navigation -->
                <?php if ($isLoggedIn): ?>
                    <div class="hidden md:flex md:gap-1">
                        <?php if ($user['role'] === 'Vendor'): ?>
                            <a href="/Multi-Vendor-Rental-System/public/vendor/dashboard.php" 
                               class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 <?= str_contains($currentPath, '/vendor/dashboard') ? 'bg-accent' : '' ?>">
                                <i class="fas fa-th-large mr-2"></i>
                                Dashboard
                            </a>
                            <a href="/Multi-Vendor-Rental-System/public/vendor/products.php" 
                               class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 <?= str_contains($currentPath, '/vendor/products') ? 'bg-accent' : '' ?>">
                                <i class="fas fa-box mr-2"></i>
                                Products
                            </a>
                        <?php elseif ($user['role'] === 'Customer'): ?>
                            <a href="/Multi-Vendor-Rental-System/public/customer/dashboard.php" 
                               class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 <?= str_contains($currentPath, '/customer/dashboard') ? 'bg-accent' : '' ?>">
                                <i class="fas fa-th-large mr-2"></i>
                                Dashboard
                            </a>
                            <a href="/Multi-Vendor-Rental-System/public/customer/products.php" 
                               class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 <?= str_contains($currentPath, '/customer/products') ? 'bg-accent' : '' ?>">
                                <i class="fas fa-search mr-2"></i>
                                Browse
                            </a>
                        <?php elseif ($user['role'] === 'Administrator'): ?>
                            <a href="/Multi-Vendor-Rental-System/public/admin/dashboard.php" 
                               class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 <?= str_contains($currentPath, '/admin/dashboard') ? 'bg-accent' : '' ?>">
                                <i class="fas fa-th-large mr-2"></i>
                                Dashboard
                            </a>
                            <a href="/Multi-Vendor-Rental-System/public/admin/users.php" 
                               class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 <?= str_contains($currentPath, '/admin/users') ? 'bg-accent' : '' ?>">
                                <i class="fas fa-users mr-2"></i>
                                Users
                            </a>
                            <a href="/Multi-Vendor-Rental-System/public/admin/vendors.php" 
                               class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 <?= str_contains($currentPath, '/admin/vendors') ? 'bg-accent' : '' ?>">
                                <i class="fas fa-store mr-2"></i>
                                Vendors
                            </a>
                            <a href="/Multi-Vendor-Rental-System/public/admin/categories.php" 
                               class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 <?= str_contains($currentPath, '/admin/categories') ? 'bg-accent' : '' ?>">
                                <i class="fas fa-folder mr-2"></i>
                                Categories
                            </a>
                            <a href="/Multi-Vendor-Rental-System/public/admin/audit-logs.php" 
                               class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 <?= str_contains($currentPath, '/admin/audit-logs') ? 'bg-accent' : '' ?>">
                                <i class="fas fa-history mr-2"></i>
                                Audit Logs
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Side -->
            <div class="flex items-center gap-2">
                <?php if ($isLoggedIn): ?>
                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" 
                                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2 gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-semibold">
                                <?= strtoupper(substr($user['username'], 0, 2)) ?>
                            </div>
                            <span class="hidden md:inline-block"><?= htmlspecialchars($user['username']) ?></span>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-56 origin-top-right rounded-md border bg-popover p-1 shadow-lg"
                             x-cloak>
                            <div class="px-2 py-1.5 text-sm font-semibold">
                                <div class="text-xs text-muted-foreground">Signed in as</div>
                                <div class="truncate"><?= htmlspecialchars($user['email']) ?></div>
                            </div>
                            <div class="h-px bg-border my-1"></div>
                            <a href="/Multi-Vendor-Rental-System/public/profile.php" 
                               class="flex items-center rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground cursor-pointer">
                                <i class="fas fa-user mr-2 w-4"></i>
                                Profile
                            </a>
                            <div class="h-px bg-border my-1"></div>
                            <a href="/Multi-Vendor-Rental-System/public/logout.php" 
                               class="flex items-center rounded-sm px-2 py-1.5 text-sm text-destructive hover:bg-destructive hover:text-destructive-foreground cursor-pointer">
                                <i class="fas fa-sign-out-alt mr-2 w-4"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Guest Actions -->
                    <a href="/Multi-Vendor-Rental-System/public/login.php" 
                       class="btn-ghost btn-modern">
                        Sign In
                    </a>
                    <a href="/Multi-Vendor-Rental-System/public/register.php" 
                       class="btn-primary btn-modern">
                        Get Started
                    </a>
                <?php endif; ?>
                
                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-9 w-9 md:hidden">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <?php if ($isLoggedIn): ?>
            <div x-show="mobileMenuOpen" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 class="md:hidden border-t py-4"
                 x-cloak>
                <div class="space-y-1">
                    <?php if ($user['role'] === 'Vendor'): ?>
                        <a href="/Multi-Vendor-Rental-System/public/vendor/dashboard.php" 
                           class="flex items-center rounded-md px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground">
                            <i class="fas fa-th-large mr-3 w-4"></i>
                            Dashboard
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/vendor/products.php" 
                           class="flex items-center rounded-md px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground">
                            <i class="fas fa-box mr-3 w-4"></i>
                            Products
                        </a>
                    <?php elseif ($user['role'] === 'Customer'): ?>
                        <a href="/Multi-Vendor-Rental-System/public/customer/dashboard.php" 
                           class="flex items-center rounded-md px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground">
                            <i class="fas fa-th-large mr-3 w-4"></i>
                            Dashboard
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/customer/products.php" 
                           class="flex items-center rounded-md px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground">
                            <i class="fas fa-search mr-3 w-4"></i>
                            Browse Products
                        </a>
                    <?php elseif ($user['role'] === 'Administrator'): ?>
                        <a href="/Multi-Vendor-Rental-System/public/admin/dashboard.php" 
                           class="flex items-center rounded-md px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground">
                            <i class="fas fa-th-large mr-3 w-4"></i>
                            Dashboard
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/admin/users.php" 
                           class="flex items-center rounded-md px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground">
                            <i class="fas fa-users mr-3 w-4"></i>
                            Users
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/admin/vendors.php" 
                           class="flex items-center rounded-md px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground">
                            <i class="fas fa-store mr-3 w-4"></i>
                            Vendors
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/admin/categories.php" 
                           class="flex items-center rounded-md px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground">
                            <i class="fas fa-folder mr-3 w-4"></i>
                            Categories
                        </a>
                        <a href="/Multi-Vendor-Rental-System/public/admin/audit-logs.php" 
                           class="flex items-center rounded-md px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground">
                            <i class="fas fa-history mr-3 w-4"></i>
                            Audit Logs
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </nav>
</header>
