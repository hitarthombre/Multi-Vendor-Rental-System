<?php
/**
 * Storage Setup Script
 * 
 * Creates necessary directories and sets proper permissions
 */

require_once 'config/storage.php';

echo "=== Storage Setup Script ===\n\n";

try {
    $config = require 'config/storage.php';
    $basePath = __DIR__;
    
    // Directories to create
    $directories = [
        'storage',
        'storage/private',
        'storage/private/documents',
        'storage/temp',
        'storage/backups',
        'public/storage',
        'public/storage/products',
        'public/storage/avatars',
        'public/storage/vendors',
        'logs',
    ];
    
    echo "Creating storage directories...\n";
    
    foreach ($directories as $dir) {
        $fullPath = $basePath . '/' . $dir;
        
        if (!is_dir($fullPath)) {
            if (mkdir($fullPath, 0755, true)) {
                echo "✓ Created: $dir\n";
            } else {
                echo "✗ Failed to create: $dir\n";
            }
        } else {
            echo "✓ Already exists: $dir\n";
        }
    }
    
    // Create .htaccess for private directories
    echo "\nSecuring private directories...\n";
    
    $privateHtaccess = "# Deny all access to private storage\nOrder Deny,Allow\nDeny from all\n";
    
    $privateDirs = [
        'storage/private',
        'storage/private/documents',
        'storage/temp',
        'storage/backups',
        'logs',
    ];
    
    foreach ($privateDirs as $dir) {
        $htaccessPath = $basePath . '/' . $dir . '/.htaccess';
        if (file_put_contents($htaccessPath, $privateHtaccess)) {
            echo "✓ Secured: $dir\n";
        } else {
            echo "✗ Failed to secure: $dir\n";
        }
    }
    
    // Create index.php files to prevent directory listing
    echo "\nPreventing directory listing...\n";
    
    $indexContent = "<?php\n// Directory access denied\nheader('HTTP/1.0 403 Forbidden');\nexit('Access denied');\n";
    
    $allDirs = array_merge($directories, $privateDirs);
    
    foreach (array_unique($allDirs) as $dir) {
        $indexPath = $basePath . '/' . $dir . '/index.php';
        if (!file_exists($indexPath)) {
            if (file_put_contents($indexPath, $indexContent)) {
                echo "✓ Protected: $dir\n";
            } else {
                echo "✗ Failed to protect: $dir\n";
            }
        }
    }
    
    // Create storage configuration summary
    echo "\n=== Storage Configuration Summary ===\n";
    echo "Document Storage: " . $config['documents']['path'] . "\n";
    echo "Max Document Size: " . ($config['documents']['max_size'] / 1024 / 1024) . "MB\n";
    echo "Allowed Document Types: " . implode(', ', $config['documents']['allowed_types']) . "\n";
    echo "Product Images: " . $config['product_images']['path'] . "\n";
    echo "Max Image Size: " . ($config['product_images']['max_size'] / 1024 / 1024) . "MB\n";
    echo "Image Quality: " . $config['product_images']['quality'] . "%\n";
    echo "Backup Enabled: " . ($config['backup']['enabled'] ? 'Yes' : 'No') . "\n";
    echo "Backup Retention: " . $config['backup']['retention'] . " days\n";
    
    // Test write permissions
    echo "\n=== Testing Write Permissions ===\n";
    
    $testDirs = [
        'storage/temp',
        'public/storage/products',
        'logs',
    ];
    
    foreach ($testDirs as $dir) {
        $testFile = $basePath . '/' . $dir . '/test_write.tmp';
        if (file_put_contents($testFile, 'test')) {
            unlink($testFile);
            echo "✓ Write permission OK: $dir\n";
        } else {
            echo "✗ Write permission FAILED: $dir\n";
        }
    }
    
    echo "\n✓ Storage setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Storage setup failed: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check directory permissions (should be 755)\n";
    echo "2. Ensure web server has write access\n";
    echo "3. Verify disk space availability\n";
}

echo "\n=== Setup Complete ===\n";