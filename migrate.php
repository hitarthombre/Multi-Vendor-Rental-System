#!/usr/bin/env php
<?php
/**
 * Database Migration Runner
 * 
 * Usage:
 *   php migrate.php          - Run all pending migrations
 *   php migrate.php status   - Show migration status
 */

require_once __DIR__ . '/src/Database/Connection.php';
require_once __DIR__ . '/src/Database/Migration.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Database\Migration;

try {
    $db = Connection::getInstance();
    $migration = new Migration($db);
    
    $command = $argv[1] ?? 'migrate';
    
    switch ($command) {
        case 'status':
            echo "Migration Status:\n";
            echo str_repeat('-', 80) . "\n";
            
            $status = $migration->getStatus();
            
            if (empty($status)) {
                echo "No migrations found.\n";
            } else {
                foreach ($status as $item) {
                    $statusText = $item['executed'] ? '[✓] Executed' : '[ ] Pending';
                    echo sprintf("%s  %s\n", $statusText, $item['migration']);
                }
            }
            
            echo str_repeat('-', 80) . "\n";
            break;
            
        case 'migrate':
        default:
            echo "Running database migrations...\n";
            echo str_repeat('-', 80) . "\n";
            
            $executed = $migration->runPendingMigrations();
            
            if (empty($executed)) {
                echo "No pending migrations to execute.\n";
            } else {
                echo "\nSuccessfully executed " . count($executed) . " migration(s).\n";
            }
            
            echo str_repeat('-', 80) . "\n";
            echo "Migration complete!\n";
            break;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
