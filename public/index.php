<?php
require_once __DIR__ . '/../vendor/autoload.php';
use RentalPlatform\Auth\Session;

Session::start();
$isLoggedIn = Session::isAuthenticated();
$user = $isLoggedIn ? Session::getUser() : null;

$pageTitle = 'Home';
$showNav = true;
$showContainer = false;
$showFooter = true;

ob_start();
?>

<!-- Hero Section -->
<div class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-blue-900 overflow-hidden particles-bg">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
        <div class="text-center">
            <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold text-white tracking-tight animate-slide-down-fade">
                Rent Anything, Anytime
                <span class="block text-blue-200 mt-2 animate-slide-up-fade stagger-1">From Trusted Vendors</span>
            </h1>
            <p class="mt-6 max-w-2xl mx-auto text-xl text-blue-100 animate-scale-in stagger-2">
                The ultimate marketplace for time-based rentals. Whether you're a customer looking to rent or a vendor wanting to list your products, we've got you covered.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center animate-bounce-in stagger-3">
                <?php if (!$isLoggedIn): ?>
                    <a href="/Multi-Vendor-Rental-System/public/register.php" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-base font-medium rounded-lg text-blue-700 bg-white hover:bg-gray-50 shadow-xl hover:shadow-2xl hover-scale ripple-effect">
                        <i class="fas fa-user-plus mr-2"></i>
                        Get Started Free
                    </a>
                    <a href="/Multi-Vendor-Rental-System/public/login.php" class="inline-flex items-center justify-center px-8 py-4 border-2 border-white text-base font-medium rounded-lg text-white hover:bg-white hover:text-blue-700 hover-scale transition-all duration-200">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Sign In
                    </a>
                <?php else: ?>
                    <a href="/Multi-Vendor-Rental-System/public/<?= strtolower($user['role']) ?>/dashboard.php" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-base font-medium rounded-lg text-blue-700 bg-white hover:bg-gray-50 shadow-xl hover:shadow-2xl hover-scale ripple-effect">
                        <i class="fas fa-th-large mr-2"></i>
                        Go to Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Wave Divider -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="rgb(249, 250, 251)"/>
        </svg>
    </div>
</div>

<!-- Features Section -->
<div class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Why Choose RentalHub?</h2>
            <p class="mt-4 text-xl text-gray-600">Everything you need for seamless rental experiences</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl hover-slide-up animate-slide-up-fade stagger-1">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 animate-float">
                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Secure & Trusted</h3>
                <p class="text-gray-600">All vendors are verified. Your payments are protected. Rent with confidence.</p>
            </div>
            
            <!-- Feature 2 -->
            <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl hover-slide-up animate-slide-up-fade stagger-2">
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-6 animate-float" style="animation-delay: 0.5s;">
                    <i class="fas fa-clock text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Flexible Rentals</h3>
                <p class="text-gray-600">Hourly, daily, weekly, or monthly. Choose the rental period that works for you.</p>
            </div>
            
            <!-- Feature 3 -->
            <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl hover-slide-up animate-slide-up-fade stagger-3">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 animate-float" style="animation-delay: 1s;">
                    <i class="fas fa-search text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Easy Discovery</h3>
                <p class="text-gray-600">Find exactly what you need with powerful search and filtering options.</p>
            </div>
        </div>
    </div>
</div>

<!-- How It Works -->
<div class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">How It Works</h2>
            <p class="mt-4 text-xl text-gray-600">Get started in three simple steps</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
            <!-- Step 1 -->
            <div class="text-center animate-scale-in stagger-1">
                <div class="relative inline-block">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold shadow-lg animate-pulse-glow">
                        1
                    </div>
                    <div class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full border-4 border-white animate-glow-pulse"></div>
                </div>
                <h3 class="mt-6 text-xl font-bold text-gray-900">Create Account</h3>
                <p class="mt-3 text-gray-600">Sign up as a customer or vendor in seconds. It's completely free!</p>
            </div>
            
            <!-- Step 2 -->
            <div class="text-center animate-scale-in stagger-2">
                <div class="relative inline-block">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold shadow-lg animate-pulse-glow" style="animation-delay: 0.5s;">
                        2
                    </div>
                    <div class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full border-4 border-white animate-glow-pulse" style="animation-delay: 0.5s;"></div>
                </div>
                <h3 class="mt-6 text-xl font-bold text-gray-900">Browse or List</h3>
                <p class="mt-3 text-gray-600">Customers browse products. Vendors list their rental items with pricing.</p>
            </div>
            
            <!-- Step 3 -->
            <div class="text-center animate-scale-in stagger-3">
                <div class="relative inline-block">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold shadow-lg animate-pulse-glow" style="animation-delay: 1s;">
                        3
                    </div>
                    <div class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full border-4 border-white animate-glow-pulse" style="animation-delay: 1s;"></div>
                </div>
                <h3 class="mt-6 text-xl font-bold text-gray-900">Rent & Earn</h3>
                <p class="mt-3 text-gray-600">Complete secure transactions and enjoy seamless rental experiences.</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="bg-gradient-to-r from-blue-600 to-blue-800 py-16 gradient-animated">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4 animate-bounce-in">Ready to Get Started?</h2>
        <p class="text-xl text-blue-100 mb-8 animate-slide-up-fade stagger-1">Join thousands of users already renting and earning on RentalHub</p>
        <?php if (!$isLoggedIn): ?>
            <a href="/Multi-Vendor-Rental-System/public/register.php" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-base font-medium rounded-lg text-blue-700 bg-white hover:bg-gray-50 shadow-xl hover:shadow-2xl hover-scale ripple-effect animate-scale-in stagger-2">
                <i class="fas fa-rocket mr-2"></i>
                Start Your Journey
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Section -->
<div class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div class="animate-scale-in stagger-1">
                <div class="text-4xl font-bold text-blue-600 animate-glow-pulse">1000+</div>
                <div class="mt-2 text-gray-600">Active Products</div>
            </div>
            <div class="animate-scale-in stagger-2">
                <div class="text-4xl font-bold text-blue-600 animate-glow-pulse" style="animation-delay: 0.3s;">500+</div>
                <div class="mt-2 text-gray-600">Trusted Vendors</div>
            </div>
            <div class="animate-scale-in stagger-3">
                <div class="text-4xl font-bold text-blue-600 animate-glow-pulse" style="animation-delay: 0.6s;">5000+</div>
                <div class="mt-2 text-gray-600">Happy Customers</div>
            </div>
            <div class="animate-scale-in stagger-4">
                <div class="text-4xl font-bold text-blue-600 animate-glow-pulse" style="animation-delay: 0.9s;">99%</div>
                <div class="mt-2 text-gray-600">Satisfaction Rate</div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/modern-base.php';
?>
