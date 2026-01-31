<footer class="border-t bg-background">
    <div class="container mx-auto px-4 py-8 md:py-12">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
            <!-- Brand -->
            <div class="space-y-4">
                <div class="flex items-center space-x-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                    </div>
                    <span class="font-bold text-lg gradient-text">RentalHub</span>
                </div>
                <p class="text-sm text-muted-foreground">
                    The ultimate marketplace for time-based rentals. Rent anything, anytime, from trusted vendors.
                </p>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h3 class="font-semibold mb-4">Platform</h3>
                <ul class="space-y-2 text-sm text-muted-foreground">
                    <li><a href="/Multi-Vendor-Rental-System/public/" class="hover:text-foreground transition-colors">Home</a></li>
                    <li><a href="/Multi-Vendor-Rental-System/public/customer/products.php" class="hover:text-foreground transition-colors">Browse Products</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">How It Works</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">Pricing</a></li>
                </ul>
            </div>
            
            <!-- Support -->
            <div>
                <h3 class="font-semibold mb-4">Support</h3>
                <ul class="space-y-2 text-sm text-muted-foreground">
                    <li><a href="#" class="hover:text-foreground transition-colors">Help Center</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">Contact Us</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">Terms of Service</a></li>
                    <li><a href="#" class="hover:text-foreground transition-colors">Privacy Policy</a></li>
                </ul>
            </div>
            
            <!-- Connect -->
            <div>
                <h3 class="font-semibold mb-4">Connect</h3>
                <div class="flex gap-3">
                    <a href="#" class="flex h-9 w-9 items-center justify-center rounded-md border hover:bg-accent hover:text-accent-foreground transition-colors">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="flex h-9 w-9 items-center justify-center rounded-md border hover:bg-accent hover:text-accent-foreground transition-colors">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="flex h-9 w-9 items-center justify-center rounded-md border hover:bg-accent hover:text-accent-foreground transition-colors">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="flex h-9 w-9 items-center justify-center rounded-md border hover:bg-accent hover:text-accent-foreground transition-colors">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="mt-8 border-t pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-sm text-muted-foreground">
                © <?= date('Y') ?> RentalHub. All rights reserved.
            </p>
            <div class="flex gap-4 text-sm text-muted-foreground">
                <a href="#" class="hover:text-foreground transition-colors">Terms</a>
                <a href="#" class="hover:text-foreground transition-colors">Privacy</a>
                <a href="#" class="hover:text-foreground transition-colors">Cookies</a>
            </div>
        </div>
    </div>
</footer>
