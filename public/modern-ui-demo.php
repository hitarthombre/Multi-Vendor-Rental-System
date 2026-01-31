<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Auth\Session;

Session::start();

$pageTitle = 'Modern UI Demo';
$showNav = true;
$showContainer = true;
$showFooter = true;

ob_start();
?>

<!-- Hero Section -->
<div class="mb-8 animate-fade-in">
    <h1 class="text-4xl font-bold gradient-text mb-2">Modern UI Design System</h1>
    <p class="text-muted-foreground">Shadcn-inspired components for the Multi-Vendor Rental Platform</p>
</div>

<!-- Buttons Section -->
<div class="card p-6 mb-6 animate-slide-in">
    <h2 class="text-2xl font-semibold mb-4">Buttons</h2>
    <div class="flex flex-wrap gap-3">
        <button class="btn-modern btn-primary">Primary Button</button>
        <button class="btn-modern btn-secondary">Secondary Button</button>
        <button class="btn-modern btn-outline">Outline Button</button>
        <button class="btn-modern btn-ghost">Ghost Button</button>
        <button class="btn-modern btn-destructive">Destructive Button</button>
        <button class="btn-modern btn-primary" disabled>Disabled Button</button>
    </div>
    
    <h3 class="text-lg font-semibold mt-6 mb-3">Buttons with Icons</h3>
    <div class="flex flex-wrap gap-3">
        <button class="btn-modern btn-primary">
            <i class="fas fa-plus mr-2"></i>
            Add New
        </button>
        <button class="btn-modern btn-secondary">
            <i class="fas fa-save mr-2"></i>
            Save
        </button>
        <button class="btn-modern btn-outline">
            <i class="fas fa-download mr-2"></i>
            Download
        </button>
        <button class="btn-modern btn-destructive">
            <i class="fas fa-trash mr-2"></i>
            Delete
        </button>
    </div>
</div>

<!-- Cards Section -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="card p-6 animate-slide-in" style="animation-delay: 0.1s;">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Basic Card</h3>
            <span class="badge badge-default">New</span>
        </div>
        <p class="text-muted-foreground text-sm mb-4">
            This is a basic card component with a clean design and subtle shadow.
        </p>
        <button class="btn-modern btn-primary w-full">Action</button>
    </div>
    
    <div class="card hover-lift p-6 animate-slide-in" style="animation-delay: 0.2s;">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Hover Card</h3>
            <span class="badge badge-secondary">Popular</span>
        </div>
        <p class="text-muted-foreground text-sm mb-4">
            This card has a hover effect that lifts it up with a shadow.
        </p>
        <button class="btn-modern btn-outline w-full">Learn More</button>
    </div>
    
    <div class="card p-6 bg-primary text-primary-foreground animate-slide-in" style="animation-delay: 0.3s;">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Colored Card</h3>
            <span class="badge bg-white text-primary">Pro</span>
        </div>
        <p class="text-primary-foreground/80 text-sm mb-4">
            Cards can have different background colors for emphasis.
        </p>
        <button class="btn-modern bg-white text-primary hover:bg-white/90 w-full">Get Started</button>
    </div>
</div>

<!-- Badges Section -->
<div class="card p-6 mb-6">
    <h2 class="text-2xl font-semibold mb-4">Badges</h2>
    <div class="flex flex-wrap gap-3">
        <span class="badge badge-default">Default</span>
        <span class="badge badge-secondary">Secondary</span>
        <span class="badge badge-outline">Outline</span>
        <span class="badge badge-destructive">Destructive</span>
        <span class="badge badge-default">
            <i class="fas fa-check mr-1"></i>
            With Icon
        </span>
    </div>
</div>

<!-- Alerts Section -->
<div class="space-y-4 mb-6">
    <div class="alert alert-default">
        <i class="fas fa-info-circle text-xl"></i>
        <div>
            <h4 class="font-semibold">Information</h4>
            <p class="text-sm mt-1">This is an informational alert message.</p>
        </div>
    </div>
    
    <div class="alert alert-destructive">
        <i class="fas fa-exclamation-triangle text-xl"></i>
        <div>
            <h4 class="font-semibold">Error</h4>
            <p class="text-sm mt-1">Something went wrong. Please try again.</p>
        </div>
    </div>
</div>

<!-- Form Elements Section -->
<div class="card p-6 mb-6">
    <h2 class="text-2xl font-semibold mb-4">Form Elements</h2>
    <div class="space-y-4 max-w-md">
        <div>
            <label class="block text-sm font-medium mb-2">Text Input</label>
            <input type="text" class="input-modern" placeholder="Enter text...">
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Email Input</label>
            <input type="email" class="input-modern" placeholder="email@example.com">
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Disabled Input</label>
            <input type="text" class="input-modern" placeholder="Disabled" disabled>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Textarea</label>
            <textarea class="input-modern min-h-[100px]" placeholder="Enter description..."></textarea>
        </div>
    </div>
</div>

<!-- Special Effects Section -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="glass p-6 rounded-lg">
        <h3 class="text-lg font-semibold mb-2">Glass Morphism</h3>
        <p class="text-sm text-muted-foreground">
            This card has a glass morphism effect with backdrop blur.
        </p>
    </div>
    
    <div class="card p-6">
        <h3 class="text-lg font-semibold mb-2 gradient-text">Gradient Text</h3>
        <p class="text-sm text-muted-foreground">
            Text can have beautiful gradient effects for emphasis.
        </p>
    </div>
</div>

<!-- Toast Demo Section -->
<div class="card p-6 mb-6">
    <h2 class="text-2xl font-semibold mb-4">Toast Notifications</h2>
    <p class="text-muted-foreground mb-4">Click the buttons to see toast notifications in action</p>
    <div class="flex flex-wrap gap-3">
        <button class="btn-modern btn-primary" onclick="toastManager.success('Operation completed successfully!')">
            Success Toast
        </button>
        <button class="btn-modern btn-destructive" onclick="toastManager.error('An error occurred!')">
            Error Toast
        </button>
        <button class="btn-modern btn-secondary" onclick="toastManager.warning('Please review this warning')">
            Warning Toast
        </button>
        <button class="btn-modern btn-outline" onclick="toastManager.info('Here is some information')">
            Info Toast
        </button>
    </div>
</div>

<!-- Loading States Section -->
<div class="card p-6 mb-6">
    <h2 class="text-2xl font-semibold mb-4">Loading States</h2>
    <div class="flex flex-wrap gap-3">
        <button class="btn-modern btn-primary" onclick="demoLoading(this)">
            Click to Load
        </button>
        <div class="flex items-center gap-2">
            <div class="spinner"></div>
            <span class="text-sm text-muted-foreground">Loading...</span>
        </div>
    </div>
</div>

<!-- Grid Layout Demo -->
<div class="mb-6">
    <h2 class="text-2xl font-semibold mb-4">Responsive Grid</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php for ($i = 1; $i <= 8; $i++): ?>
            <div class="card p-4 text-center hover-lift">
                <div class="w-12 h-12 bg-primary text-primary-foreground rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-star"></i>
                </div>
                <h4 class="font-semibold mb-1">Item <?= $i ?></h4>
                <p class="text-sm text-muted-foreground">Grid item example</p>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Color Palette -->
<div class="card p-6">
    <h2 class="text-2xl font-semibold mb-4">Color Palette</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <div class="h-20 rounded-lg bg-primary mb-2"></div>
            <p class="text-sm font-medium">Primary</p>
        </div>
        <div>
            <div class="h-20 rounded-lg bg-secondary mb-2"></div>
            <p class="text-sm font-medium">Secondary</p>
        </div>
        <div>
            <div class="h-20 rounded-lg bg-accent mb-2"></div>
            <p class="text-sm font-medium">Accent</p>
        </div>
        <div>
            <div class="h-20 rounded-lg bg-destructive mb-2"></div>
            <p class="text-sm font-medium">Destructive</p>
        </div>
    </div>
</div>

<script>
function demoLoading(button) {
    setLoading(button, true);
    setTimeout(() => {
        setLoading(button, false);
        toastManager.success('Loading complete!');
    }, 2000);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/modern-base.php';
?>
