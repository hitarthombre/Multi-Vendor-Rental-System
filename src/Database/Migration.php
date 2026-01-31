<?php

namespace RentalPlatform\Database;

use PDO;

/**
 * Database Migration Manager
 * 
 * Handles database schema migrations with version tracking
 */
class Migration
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct(PDO $db, string $migrationsPath = null)
    {
        $this->db = $db;
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../../database/migrations';
    }

    /**
     * Initialize migrations table
     */
    public function initializeMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
    }

    /**
     * Get list of executed migrations
     * 
     * @return array
     */
    public function getExecutedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get list of pending migrations
     * 
     * @return array
     */
    public function getPendingMigrations(): array
    {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        return array_diff($allMigrations, $executedMigrations);
    }

    /**
     * Get all migration files
     * 
     * @return array
     */
    private function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = scandir($this->migrationsPath);
        $migrations = [];

        foreach ($files as $file) {
            // Support both date-time pattern and simple numeric pattern
            if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.*\.sql$/', $file) || 
                preg_match('/^\d{3}_.*\.sql$/', $file)) {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Run all pending migrations
     * 
     * @return array Array of executed migration names
     */
    public function runPendingMigrations(): array
    {
        $this->initializeMigrationsTable();
        $pendingMigrations = $this->getPendingMigrations();
        $executed = [];

        foreach ($pendingMigrations as $migration) {
            $this->runMigration($migration);
            $executed[] = $migration;
        }

        return $executed;
    }

    /**
     * Run a single migration
     * 
     * @param string $migrationFile
     */
    private function runMigration(string $migrationFile): void
    {
        $filePath = $this->migrationsPath . '/' . $migrationFile;
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Migration file not found: {$migrationFile}");
        }

        $sql = file_get_contents($filePath);
        
        try {
            // Execute migration SQL (without transaction for DDL statements)
            $this->db->exec($sql);
            
            // Record migration
            $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migrationFile]);
            
            echo "Executed migration: {$migrationFile}\n";
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Migration failed: {$migrationFile}\nError: " . $e->getMessage()
            );
        }
    }

    /**
     * Get migration status
     * 
     * @return array
     */
    public function getStatus(): array
    {
        $this->initializeMigrationsTable();
        
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        $status = [];
        foreach ($allMigrations as $migration) {
            $status[] = [
                'migration' => $migration,
                'executed' => in_array($migration, $executedMigrations),
            ];
        }
        
        return $status;
    }
}
