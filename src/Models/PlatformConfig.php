<?php

namespace RentalPlatform\Models;

/**
 * Platform Configuration Model
 * 
 * Represents platform-wide configuration settings
 */
class PlatformConfig
{
    public const DATA_TYPE_STRING = 'string';
    public const DATA_TYPE_INTEGER = 'integer';
    public const DATA_TYPE_BOOLEAN = 'boolean';
    public const DATA_TYPE_JSON = 'json';

    public const CATEGORY_GENERAL = 'general';
    public const CATEGORY_VERIFICATION = 'verification';
    public const CATEGORY_RENTAL_PERIOD = 'rental_period';
    public const CATEGORY_FINANCIAL = 'financial';
    public const CATEGORY_NOTIFICATIONS = 'notifications';
    public const CATEGORY_UPLOADS = 'uploads';

    private string $id;
    private string $configKey;
    private ?string $configValue;
    private string $dataType;
    private ?string $description;
    private string $category;
    private bool $isPublic;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(
        string $id,
        string $configKey,
        ?string $configValue,
        string $dataType = self::DATA_TYPE_STRING,
        ?string $description = null,
        string $category = self::CATEGORY_GENERAL,
        bool $isPublic = false,
        ?\DateTime $createdAt = null,
        ?\DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->configKey = $configKey;
        $this->configValue = $configValue;
        $this->dataType = $dataType;
        $this->description = $description;
        $this->category = $category;
        $this->isPublic = $isPublic;
        $this->createdAt = $createdAt ?? new \DateTime();
        $this->updatedAt = $updatedAt ?? new \DateTime();
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    public function getConfigValue(): ?string
    {
        return $this->configValue;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    // Setters
    public function setConfigValue(?string $configValue): void
    {
        $this->configValue = $configValue;
        $this->updatedAt = new \DateTime();
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->updatedAt = new \DateTime();
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
        $this->updatedAt = new \DateTime();
    }

    public function setIsPublic(bool $isPublic): void
    {
        $this->isPublic = $isPublic;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get typed value based on data type
     * 
     * @return mixed
     */
    public function getTypedValue()
    {
        if ($this->configValue === null) {
            return null;
        }

        switch ($this->dataType) {
            case self::DATA_TYPE_BOOLEAN:
                return filter_var($this->configValue, FILTER_VALIDATE_BOOLEAN);
            
            case self::DATA_TYPE_INTEGER:
                return (int) $this->configValue;
            
            case self::DATA_TYPE_JSON:
                return json_decode($this->configValue, true);
            
            case self::DATA_TYPE_STRING:
            default:
                return $this->configValue;
        }
    }

    /**
     * Set typed value and convert to string for storage
     * 
     * @param mixed $value
     */
    public function setTypedValue($value): void
    {
        switch ($this->dataType) {
            case self::DATA_TYPE_BOOLEAN:
                $this->configValue = $value ? 'true' : 'false';
                break;
            
            case self::DATA_TYPE_INTEGER:
                $this->configValue = (string) (int) $value;
                break;
            
            case self::DATA_TYPE_JSON:
                $this->configValue = json_encode($value);
                break;
            
            case self::DATA_TYPE_STRING:
            default:
                $this->configValue = (string) $value;
                break;
        }
        
        $this->updatedAt = new \DateTime();
    }

    /**
     * Validate value against data type
     * 
     * @param mixed $value
     * @return bool
     */
    public function isValidValue($value): bool
    {
        switch ($this->dataType) {
            case self::DATA_TYPE_BOOLEAN:
                return is_bool($value) || in_array(strtolower((string) $value), ['true', 'false', '1', '0']);
            
            case self::DATA_TYPE_INTEGER:
                return is_numeric($value);
            
            case self::DATA_TYPE_JSON:
                if (is_array($value) || is_object($value)) {
                    return true;
                }
                if (is_string($value)) {
                    json_decode($value);
                    return json_last_error() === JSON_ERROR_NONE;
                }
                return false;
            
            case self::DATA_TYPE_STRING:
            default:
                return is_string($value) || is_numeric($value);
        }
    }

    /**
     * Convert to array representation
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'config_key' => $this->configKey,
            'config_value' => $this->configValue,
            'typed_value' => $this->getTypedValue(),
            'data_type' => $this->dataType,
            'description' => $this->description,
            'category' => $this->category,
            'is_public' => $this->isPublic,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}