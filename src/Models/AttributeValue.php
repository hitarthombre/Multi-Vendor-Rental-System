<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * AttributeValue Model
 * 
 * Represents a specific value for an attribute (e.g., "Red" for Color attribute)
 */
class AttributeValue
{
    private string $id;
    private string $attributeId;
    private string $value;
    private string $createdAt;

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $attributeId,
        string $value,
        string $createdAt = ''
    ) {
        $this->id = $id;
        $this->attributeId = $attributeId;
        $this->value = $value;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new attribute value instance with generated ID
     */
    public static function create(
        string $attributeId,
        string $value
    ): self {
        $id = UUID::generate();
        
        return new self($id, $attributeId, $value);
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getAttributeId(): string
    {
        return $this->attributeId;
    }

    public function getValue(): string
    {
        return $this->value;
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
            'attribute_id' => $this->attributeId,
            'value' => $this->value,
            'created_at' => $this->createdAt
        ];
    }
}
