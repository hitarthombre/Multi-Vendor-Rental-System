<?php
// Simple test page to verify modern UI is working
$pageTitle = 'Test Modern UI';
$showNav = true;
$showContainer = true;
$showFooter = true;

ob_start();
?>

<div class="space-y-8">
    <h1 class="text-4xl font-bold text-gray-900 animate-slide-down-fade">Modern UI Test Page</h1>
    
    <div class="card p-6 animate-scale-in">
        <h2 class="text-2xl font-bold mb-4">Card Component</h2>
        <p class="text-muted-foreground">If you can see this styled card, the modern UI is working!</p>
    </div>
    
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 hover-slide-up animate-slide-up-fade stagger-1">
            <div class="text-3xl font-bold text-primary-600 animate-glow-pulse">100+</div>
            <div class="text-sm text-gray-600">Test Stat 1</div>
        </div>
        <div class="card p-4 hover-slide-up animate-slide-up-fade stagger-2">
            <div class="text-3xl font-bold text-primary-600 animate-glow-pulse">200+</div>
            <div class="text-sm text-gray-600">Test Stat 2</div>
        </div>
        <div class="card p-4 hover-slide-up animate-slide-up-fade stagger-3">
            <div class="text-3xl font-bold text-primary-600 animate-glow-pulse">300+</div>
            <div class="text-sm text-gray-600">Test Stat 3</div>
        </div>
    </div>
    
    <div class="space-x-4">
        <button class="btn-modern btn-primary hover-scale ripple-effect">Primary Button</button>
        <button class="btn-modern btn-secondary">Secondary Button</button>
        <button class="btn-modern btn-outline">Outline Button</button>
    </div>
    
    <div class="alert alert-default">
        <i class="fas fa-info-circle"></i>
        <div>
            <h4 class="font-semibold">Information</h4>
            <p class="text-sm">This is a test alert to verify styling is working.</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/modern-base.php';
?>
