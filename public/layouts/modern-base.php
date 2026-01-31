<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Rental Platform' ?> - Multi-Vendor Rental Platform</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js for interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Modern Design System -->
    <link rel="stylesheet" href="/Multi-Vendor-Rental-System/public/assets/css/modern-design-system.css">
    
    <!-- Enhanced Animations -->
    <link rel="stylesheet" href="/Multi-Vendor-Rental-System/public/assets/css/enhanced-animations.css">
    
    <!-- Custom Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        border: "hsl(var(--border))",
                        input: "hsl(var(--input))",
                        ring: "hsl(var(--ring))",
                        background: "hsl(var(--background))",
                        foreground: "hsl(var(--foreground))",
                        primary: {
                            DEFAULT: "hsl(var(--primary))",
                            foreground: "hsl(var(--primary-foreground))",
                        },
                        secondary: {
                            DEFAULT: "hsl(var(--secondary))",
                            foreground: "hsl(var(--secondary-foreground))",
                        },
                        destructive: {
                            DEFAULT: "hsl(var(--destructive))",
                            foreground: "hsl(var(--destructive-foreground))",
                        },
                        muted: {
                            DEFAULT: "hsl(var(--muted))",
                            foreground: "hsl(var(--muted-foreground))",
                        },
                        accent: {
                            DEFAULT: "hsl(var(--accent))",
                            foreground: "hsl(var(--accent-foreground))",
                        },
                        popover: {
                            DEFAULT: "hsl(var(--popover))",
                            foreground: "hsl(var(--popover-foreground))",
                        },
                        card: {
                            DEFAULT: "hsl(var(--card))",
                            foreground: "hsl(var(--card-foreground))",
                        },
                    },
                    borderRadius: {
                        lg: "var(--radius)",
                        md: "calc(var(--radius) - 2px)",
                        sm: "calc(var(--radius) - 4px)",
                    },
                    keyframes: {
                        "accordion-down": {
                            from: { height: 0 },
                            to: { height: "var(--radix-accordion-content-height)" },
                        },
                        "accordion-up": {
                            from: { height: "var(--radix-accordion-content-height)" },
                            to: { height: 0 },
                        },
                        "slide-in": {
                            "0%": { transform: "translateY(-10px)", opacity: 0 },
                            "100%": { transform: "translateY(0)", opacity: 1 },
                        },
                        "fade-in": {
                            "0%": { opacity: 0 },
                            "100%": { opacity: 1 },
                        },
                    },
                    animation: {
                        "accordion-down": "accordion-down 0.2s ease-out",
                        "accordion-up": "accordion-up 0.2s ease-out",
                        "slide-in": "slide-in 0.3s ease-out",
                        "fade-in": "fade-in 0.3s ease-out",
                    },
                }
            }
        }
    </script>
    
    <!-- Additional Styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: hsl(var(--muted));
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: hsl(var(--muted-foreground) / 0.3);
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: hsl(var(--muted-foreground) / 0.5);
        }
        
        /* Smooth page transitions */
        body {
            animation: fade-in 0.3s ease-out;
        }
        
        /* Focus visible styles */
        *:focus-visible {
            outline: 2px solid hsl(var(--ring));
            outline-offset: 2px;
        }
    </style>
    
    <?= $additionalHead ?? '' ?>
</head>
<body class="min-h-screen bg-background font-sans antialiased">
    <div class="relative flex min-h-screen flex-col">
        <?php if (isset($showNav) && $showNav): ?>
            <!-- Modern Navigation -->
            <?php include __DIR__ . '/../components/modern-navigation.php'; ?>
        <?php endif; ?>
        
        <!-- Main Content -->
        <main class="flex-1">
            <?php if (isset($showContainer) && $showContainer): ?>
                <div class="container mx-auto px-4 py-6 md:px-6 lg:px-8">
                    <?= $content ?? '' ?>
                </div>
            <?php else: ?>
                <?= $content ?? '' ?>
            <?php endif; ?>
        </main>
        
        <?php if (isset($showFooter) && $showFooter): ?>
            <!-- Modern Footer -->
            <?php include __DIR__ . '/../components/modern-footer.php'; ?>
        <?php endif; ?>
    </div>
    
    <!-- Toast Notifications Container -->
    <div id="toast-container" 
         class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 w-full max-w-sm pointer-events-none"
         x-data="{ toasts: [] }">
    </div>
    
    <!-- Global Scripts -->
    <script>
        // Modern Toast Notification System
        class ToastManager {
            constructor() {
                this.container = document.getElementById('toast-container');
                this.toasts = [];
            }
            
            show(message, type = 'success', duration = 5000) {
                const id = Date.now();
                const toast = this.createToast(id, message, type);
                
                this.container.appendChild(toast);
                this.toasts.push(id);
                
                // Animate in
                setTimeout(() => toast.classList.add('translate-x-0', 'opacity-100'), 10);
                
                // Auto remove
                if (duration > 0) {
                    setTimeout(() => this.remove(id), duration);
                }
                
                return id;
            }
            
            createToast(id, message, type) {
                const toast = document.createElement('div');
                toast.id = `toast-${id}`;
                toast.className = 'pointer-events-auto transform translate-x-full opacity-0 transition-all duration-300 ease-out';
                
                const styles = {
                    success: 'bg-green-50 border-green-200 text-green-900',
                    error: 'bg-red-50 border-red-200 text-red-900',
                    warning: 'bg-yellow-50 border-yellow-200 text-yellow-900',
                    info: 'bg-blue-50 border-blue-200 text-blue-900'
                };
                
                const icons = {
                    success: '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
                    error: '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
                    warning: '<svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                    info: '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                };
                
                toast.innerHTML = `
                    <div class="flex items-start gap-3 p-4 rounded-lg border shadow-lg ${styles[type]}">
                        <div class="flex-shrink-0 mt-0.5">
                            ${icons[type]}
                        </div>
                        <div class="flex-1 text-sm font-medium">
                            ${message}
                        </div>
                        <button onclick="toastManager.remove(${id})" 
                                class="flex-shrink-0 ml-2 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                `;
                
                return toast;
            }
            
            remove(id) {
                const toast = document.getElementById(`toast-${id}`);
                if (toast) {
                    toast.classList.remove('translate-x-0', 'opacity-100');
                    toast.classList.add('translate-x-full', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }
                this.toasts = this.toasts.filter(t => t !== id);
            }
            
            success(message, duration) {
                return this.show(message, 'success', duration);
            }
            
            error(message, duration) {
                return this.show(message, 'error', duration);
            }
            
            warning(message, duration) {
                return this.show(message, 'warning', duration);
            }
            
            info(message, duration) {
                return this.show(message, 'info', duration);
            }
        }
        
        // Initialize toast manager
        const toastManager = new ToastManager();
        
        // Legacy support
        function showToast(message, type = 'success') {
            toastManager.show(message, type);
        }
        
        // Confirm dialog with modern styling
        function confirmAction(message, title = 'Confirm Action') {
            return confirm(message);
        }
        
        // Loading state helper
        function setLoading(element, loading = true) {
            if (loading) {
                element.disabled = true;
                element.dataset.originalContent = element.innerHTML;
                element.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading...
                `;
            } else {
                element.disabled = false;
                if (element.dataset.originalContent) {
                    element.innerHTML = element.dataset.originalContent;
                }
            }
        }
        
        // Auto-show toasts from URL parameters
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('success')) {
                toastManager.success(urlParams.get('success') || 'Operation completed successfully');
            }
            
            if (urlParams.has('error')) {
                toastManager.error(urlParams.get('error') || 'An error occurred');
            }
            
            if (urlParams.has('warning')) {
                toastManager.warning(urlParams.get('warning'));
            }
            
            if (urlParams.has('info')) {
                toastManager.info(urlParams.get('info'));
            }
        });
    </script>
    
    <?= $additionalScripts ?? '' ?>
</body>
</html>
