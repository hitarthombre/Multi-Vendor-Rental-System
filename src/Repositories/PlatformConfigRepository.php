<?php

namespace RentalPlatform\Repositories;

use PDO;
use RentalPlatform\Models\PlatformConfig;
use RentalPlatform\Helpers\UUID;

/**
 * Platform Configuration Repository
 * 
 * Handles database operations for platform configuration
 */
class PlatformConfigRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find configuration by key
     * 
     * @param string $configKey
     * @return PlatformConfig|null
     */
    public function findByKey(string $configKey): ?PlatformConfig
    {
        $stmt = $this->db->prepare("
            SELECT * FROM platform_config 
            WHERE config_key = ?
        ");
        
        $stmt->execute([$configKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        return $this->mapRowToModel($row);
    }

    /**
     * Get all configurations
     * 
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->db->query("
            SELECT * FROM platform_config 
            ORDER BY category, config_key
        ");
        
        $configs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $configs[] = $this->mapRowToModel($row);
        }
        
        return $configs;
    }

    /**
     * Get configurations by category
     * 
     * @param string $category
     * @return array
     */
    public function findByCategory(string $category): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM platform_config 
            WHERE category = ?
            ORDER BY config_key
        ");
        
        $stmt->execute([$category]);
        
        $configs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $configs[] = $this->mapRowToModel($row);
        }
        
        return $configs;
    }

    /**
     * Get public configurations only
     * 
     * @return array
     */
    public function findPublic(): array
    {
        $stmt = $this->db->query("
            SELECT * FROM platform_config 
            WHERE is_public = TRUE
            ORDER BY category, config_key
        ");
        
        $configs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $configs[] = $this->mapRowToModel($row);
        }
        
        return $configs;
    }

    /**
     * Create new configuration
     * 
     * @param PlatformConfig $config
     * @return bool
     */
    public function create(PlatformConfig $config): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO platform_config (
                id, config_key, config_value, data_type, description, 
                category, is_public, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $config->getId(),
            $config->getConfigKey(),
            $config->getConfigValue(),
            $config->getDataType(),
            $config->getDescription(),
            $config->getCategory(),
            $config->isPublic() ? 1 : 0,
            $config->getCreatedAt()->format('Y-m-d H:i:s'),
            $config->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update existing configuration
     * 
     * @param PlatformConfig $config
     * @return bool
     */
    public function update(PlatformConfig $config): bool
    {
        $stmt = $this->db->prepare("
            UPDATE platform_config 
            SET config_value = ?, description = ?, category = ?, 
                is_public = ?, updated_at = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $config->getConfigValue(),
            $config->getDescription(),
            $config->getCategory(),
            $config->isPublic() ? 1 : 0,
            $config->getUpdatedAt()->format('Y-m-d H:i:s'),
            $config->getId(),
        ]);
    }

    /**
     * Update configuration value by key
     * 
     * @param string $configKey
     * @param mixed $value
     * @return bool
     */
    public function updateValueByKey(string $configKey, $value): bool
    {
        $config = $this->findByKey($configKey);
        if (!$config) {
            return false;
        }
        
        if (!$config->isValidValue($value)) {
            return false;
        }
        
        $config->setTypedValue($value);
        return $this->update($config);
    }

    /**
     * Delete configuration
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM platform_config WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get configuration value by key with default
     * 
     * @param string $configKey
     * @param mixed $default
     * @return mixed
     */
    public function getValue(string $configKey, $default = null)
    {
        $config = $this->findByKey($configKey);
        return $config ? $config->getTypedValue() : $default;
    }

    /**
     * Check if configuration key exists
     * 
     * @param string $configKey
     * @return bool
     */
    public function exists(string $configKey): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM platform_config WHERE config_key = ?
        ");
        $stmt->execute([$configKey]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get all categories
     * 
     * @return array
     */
    public function getCategories(): array
    {
        $stmt = $this->db->query("
            SELECT DISTINCT category 
            FROM platform_config 
            ORDER BY category
        ");
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Bulk update configurations
     * 
     * @param array $updates Array of ['key' => 'value'] pairs
     * @return bool
     */
    public function bulkUpdate(array $updates): bool
    {
        $this->db->beginTransaction();
        
        try {
            foreach ($updates as $key => $value) {
                if (!$this->updateValueByKey($key, $value)) {
                    throw new \Exception("Failed to update configuration: {$key}");
                }
            }
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Map database row to model
     * 
     * @param array $row
     * @return PlatformConfig
     */
    private function mapRowToModel(array $row): PlatformConfig
    {
        return new PlatformConfig(
            $row['id'],
            $row['config_key'],
            $row['config_value'],
            $row['data_type'],
            $row['description'],
            $row['category'],
            (bool) $row['is_public'],
            new \DateTime($row['created_at']),
            new \DateTime($row['updated_at'])
        );
    }
}