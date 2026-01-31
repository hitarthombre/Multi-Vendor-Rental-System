<?php

namespace RentalPlatform\Services;

use RentalPlatform\Models\PlatformConfig;
use RentalPlatform\Repositories\PlatformConfigRepository;
use RentalPlatform\Services\AuditLogger;
use RentalPlatform\Helpers\UUID;

/**
 * Configuration Service
 * 
 * Manages platform-wide configuration settings
 */
class ConfigurationService
{
    private PlatformConfigRepository $repository;
    private AuditLogger $auditLogger;
    private array $cache = [];

    public function __construct(
        PlatformConfigRepository $repository,
        AuditLogger $auditLogger
    ) {
        $this->repository = $repository;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Get configuration value by key
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        // Check cache first
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $config = $this->repository->findByKey($key);
        $value = $config ? $config->getTypedValue() : $default;
        
        // Cache the value
        $this->cache[$key] = $value;
        
        return $value;
    }

    /**
     * Set configuration value
     * 
     * @param string $key
     * @param mixed $value
     * @param string|null $userId User making the change
     * @return bool
     */
    public function set(string $key, $value, ?string $userId = null): bool
    {
        $config = $this->repository->findByKey($key);
        
        if (!$config) {
            return false;
        }

        if (!$config->isValidValue($value)) {
            return false;
        }

        $oldValue = $config->getTypedValue();
        $config->setTypedValue($value);
        
        $success = $this->repository->update($config);
        
        if ($success) {
            // Clear cache
            unset($this->cache[$key]);
            
            // Log the change
            if ($userId) {
                $this->auditLogger->logConfigChange(
                    $key,
                    $oldValue,
                    $value,
                    $userId
                );
            }
        }
        
        return $success;
    }

    /**
     * Get all configurations grouped by category
     * 
     * @param bool $publicOnly
     * @return array
     */
    public function getAllGrouped(bool $publicOnly = false): array
    {
        $configs = $publicOnly ? 
            $this->repository->findPublic() : 
            $this->repository->findAll();
        
        $grouped = [];
        foreach ($configs as $config) {
            $category = $config->getCategory();
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $config->toArray();
        }
        
        return $grouped;
    }

    /**
     * Get configurations by category
     * 
     * @param string $category
     * @return array
     */
    public function getByCategory(string $category): array
    {
        $configs = $this->repository->findByCategory($category);
        return array_map(fn($config) => $config->toArray(), $configs);
    }

    /**
     * Bulk update configurations
     * 
     * @param array $updates
     * @param string|null $userId
     * @return bool
     */
    public function bulkUpdate(array $updates, ?string $userId = null): bool
    {
        $success = true;
        $changes = [];
        
        foreach ($updates as $key => $value) {
            $config = $this->repository->findByKey($key);
            if ($config) {
                $oldValue = $config->getTypedValue();
                if ($oldValue !== $value) {
                    $changes[$key] = ['old' => $oldValue, 'new' => $value];
                }
            }
        }
        
        $success = $this->repository->bulkUpdate($updates);
        
        if ($success) {
            // Clear cache for updated keys
            foreach (array_keys($updates) as $key) {
                unset($this->cache[$key]);
            }
            
            // Log all changes
            if ($userId) {
                foreach ($changes as $key => $change) {
                    $this->auditLogger->logConfigChange(
                        $key,
                        $change['old'],
                        $change['new'],
                        $userId
                    );
                }
            }
        }
        
        return $success;
    }

    /**
     * Create new configuration
     * 
     * @param string $key
     * @param mixed $value
     * @param string $dataType
     * @param string|null $description
     * @param string $category
     * @param bool $isPublic
     * @param string|null $userId
     * @return bool
     */
    public function create(
        string $key,
        $value,
        string $dataType = PlatformConfig::DATA_TYPE_STRING,
        ?string $description = null,
        string $category = PlatformConfig::CATEGORY_GENERAL,
        bool $isPublic = false,
        ?string $userId = null
    ): bool {
        if ($this->repository->exists($key)) {
            return false;
        }

        $config = new PlatformConfig(
            UUID::generate(),
            $key,
            null,
            $dataType,
            $description,
            $category,
            $isPublic
        );

        if (!$config->isValidValue($value)) {
            return false;
        }

        $config->setTypedValue($value);
        $success = $this->repository->create($config);
        
        if ($success && $userId) {
            $this->auditLogger->logConfigChange(
                $key,
                null,
                $value,
                $userId
            );
        }
        
        return $success;
    }

    /**
     * Delete configuration
     * 
     * @param string $key
     * @param string|null $userId
     * @return bool
     */
    public function delete(string $key, ?string $userId = null): bool
    {
        $config = $this->repository->findByKey($key);
        if (!$config) {
            return false;
        }

        $oldValue = $config->getTypedValue();
        $success = $this->repository->delete($config->getId());
        
        if ($success) {
            unset($this->cache[$key]);
            
            if ($userId) {
                $this->auditLogger->logConfigChange(
                    $key,
                    $oldValue,
                    null,
                    $userId
                );
            }
        }
        
        return $success;
    }

    /**
     * Get all available categories
     * 
     * @return array
     */
    public function getCategories(): array
    {
        return $this->repository->getCategories();
    }

    /**
     * Validate configuration data
     * 
     * @param array $data
     * @return array Validation errors
     */
    public function validateConfigData(array $data): array
    {
        $errors = [];
        
        foreach ($data as $key => $value) {
            $config = $this->repository->findByKey($key);
            
            if (!$config) {
                $errors[$key] = "Configuration key '{$key}' does not exist";
                continue;
            }
            
            if (!$config->isValidValue($value)) {
                $errors[$key] = "Invalid value for '{$key}'. Expected {$config->getDataType()}";
            }
        }
        
        return $errors;
    }

    /**
     * Get verification requirements configuration
     * 
     * @return array
     */
    public function getVerificationConfig(): array
    {
        return [
            'default_required' => $this->get('default_verification_required', false),
            'auto_approve_threshold' => $this->get('auto_approve_threshold', 1000),
            'document_types' => $this->get('verification_document_types', ['ID Proof', 'License']),
        ];
    }

    /**
     * Get rental period configuration
     * 
     * @return array
     */
    public function getRentalPeriodConfig(): array
    {
        return [
            'min_hours' => $this->get('min_rental_hours', 1),
            'max_days' => $this->get('max_rental_days', 365),
            'duration_units' => $this->get('supported_duration_units', ['hourly', 'daily', 'weekly', 'monthly']),
            'advance_booking_days' => $this->get('advance_booking_days', 90),
        ];
    }

    /**
     * Get platform general settings
     * 
     * @return array
     */
    public function getPlatformSettings(): array
    {
        return [
            'name' => $this->get('platform_name', 'Multi-Vendor Rental Platform'),
            'email' => $this->get('platform_email', 'admin@rentalplatform.com'),
            'currency' => $this->get('currency_code', 'INR'),
            'tax_rate' => $this->get('tax_rate', '18.0'),
        ];
    }

    /**
     * Clear configuration cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}