#!/usr/bin/env php
<?php
/**
 * Schema Verification Script
 * 
 * Verifies that all required tables and columns exist
 */

require_once __DIR__ . '/src/Database/Connection.php';

use RentalPlatform\Database\Connection;

echo "Multi-Vendor Rental Platform - Schema Verification\n";
echo str_repeat('=', 80) . "\n\n";

try {
    $db = Connection::getInstance();
    
    // Define expected tables
    $expectedTables = [
        'users',
        'vendors',
        'categories',
        'products',
        'attributes',
        'attribute_values',
        'variants',
        'pricing',
        'rental_periods',
        'carts',
        'cart_items',
        'payments',
        'orders',
        'order_items',
        'inventory_locks',
        'documents',
        'invoices',
        'invoice_line_items',
        'refunds',
        'audit_logs',
        'notifications',
        'migrations',
    ];
    
    echo "Checking tables...\n";
    $missingTables = [];
    
    foreach ($expectedTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ {$table}\n";
        } else {
            echo "  ✗ {$table} (MISSING)\n";
            $missingTables[] = $table;
        }
    }
    
    echo "\n";
    
    if (empty($missingTables)) {
        echo "✓ All " . count($expectedTables) . " tables exist!\n\n";
        
        // Show sample table structure
        echo "Sample Table Structure (users):\n";
        echo str_repeat('-', 80) . "\n";
        $stmt = $db->query("DESCRIBE users");
        printf("%-20s %-30s %-10s %-10s\n", "Field", "Type", "Null", "Key");
        echo str_repeat('-', 80) . "\n";
        while ($row = $stmt->fetch()) {
            printf(
                "%-20s %-30s %-10s %-10s\n",
                $row['Field'],
                $row['Type'],
                $row['Null'],
                $row['Key']
            );
        }
        echo "\n";
        
        // Show sample table structure for orders
        echo "Sample Table Structure (orders):\n";
        echo str_repeat('-', 80) . "\n";
        $stmt = $db->query("DESCRIBE orders");
        printf("%-20s %-30s %-10s %-10s\n", "Field", "Type", "Null", "Key");
        echo str_repeat('-', 80) . "\n";
        while ($row = $stmt->fetch()) {
            printf(
                "%-20s %-30s %-10s %-10s\n",
                $row['Field'],
                $row['Type'],
                $row['Null'],
                $row['Key']
            );
        }
        echo "\n";
        
        // Count foreign keys
        $stmt = $db->query("
            SELECT COUNT(*) as fk_count 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = 'rental_platform' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        $result = $stmt->fetch();
        echo "Foreign Key Constraints: {$result['fk_count']}\n";
        
        // Count indexes
        $stmt = $db->query("
            SELECT COUNT(DISTINCT INDEX_NAME) as idx_count 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = 'rental_platform'
            AND INDEX_NAME != 'PRIMARY'
        ");
        $result = $stmt->fetch();
        echo "Indexes (excluding PRIMARY): {$result['idx_count']}\n";
        
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "✓ Schema verification complete! Database is ready.\n";
        
    } else {
        echo "✗ Missing tables: " . implode(', ', $missingTables) . "\n";
        echo "Run migrations to create missing tables: php migrate.php\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
