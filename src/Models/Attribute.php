<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Attribute Model
 * 
 * Represents a product attribute (e.g., Color, Size, Brand)
 */
class Attribute
{
    private string $id;
    private string $name;
    private string $type;
    private string $createdAt;

    /**
     * Valid attribute types
     */
    public const TYPE_SELECT = 'Select';
    public const TYPE_TEXT = 'Text';
    public const TYPE_NUMBER = 'Number';

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $name,
        string $type,
        string $createdAt = ''
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new attribute instance with generated ID
     */
    public static function create(
        string $name,
        string $type = self::TYPE_SELECT
    ): self {
        $id = UUID::generate();
        
        if (!self::isValidType($type)) {
            throw new \InvalidArgumentException("Invalid attribute type: {$type}");
        }
        
        return new self($id, $name, $type);
    }

    /**
     * Check if type is valid
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, [
            self::TYPE_SELECT,
            self::TYPE_TEXT,
            self::TYPE_NUMBER
        ], true);
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'created_at' => $this->createdAt
        ];
    }
}
