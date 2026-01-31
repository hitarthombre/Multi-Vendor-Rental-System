<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
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
    
    <!-- Custom Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        secondary: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        /* Smooth transitions */
        * {
            transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Animations */
        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
    
    <?= $additionalHead ?? '' ?>
</head>
<body class="h-full">
    <div class="min-h-full">
        <?php if (isset($showNav) && $showNav): ?>
            <!-- Navigation -->
            <?php include __DIR__ . '/../components/navigation.php'; ?>
        <?php endif; ?>
        
        <!-- Main Content -->
        <main class="<?= isset($showNav) && $showNav ? 'py-10' : '' ?>">
            <?php if (isset($showContainer) && $showContainer): ?>
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <?= $content ?? '' ?>
                </div>
            <?php else: ?>
                <?= $content ?? '' ?>
            <?php endif; ?>
        </main>
        
        <?php if (isset($showFooter) && $showFooter): ?>
            <!-- Footer -->
            <?php include __DIR__ . '/../components/footer.php'; ?>
        <?php endif; ?>
    </div>
    
    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>
    
    <!-- Global Scripts -->
    <script>
        // Toast notification function
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            toast.className = `${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 animate-slide-in max-w-sm`;
            toast.innerHTML = `
                <i class="fas ${icons[type]}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
        
        // Confirm dialog
        function confirmAction(message) {
            return confirm(message);
        }
        
        // Loading state
        function setLoading(button, loading = true) {
            if (loading) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
            } else {
                button.disabled = false;
            }
        }
    </script>
    
    <?= $additionalScripts ?? '' ?>
</body>
</html>
