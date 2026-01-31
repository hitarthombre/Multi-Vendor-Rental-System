<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Variant Model
 * 
 * Represents a specific configuration of a product based on attribute values
 */
class Variant
{
    private string $id;
    private string $productId;
    private string $sku;
    private array $attributeValues;
    private int $quantity;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $productId,
        string $sku,
        array $attributeValues,
        int $quantity,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->productId = $productId;
        $this->sku = $sku;
        $this->attributeValues = $attributeValues;
        $this->quantity = $quantity;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new variant instance with generated ID
     */
    public static function create(
        string $productId,
        string $sku,
        array $attributeValues,
        int $quantity = 1
    ): self {
        $id = UUID::generate();
        
        return new self($id, $productId, $sku, $attributeValues, $quantity);
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getAttributeValues(): array
    {
        return $this->attributeValues;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    // Setters
    public function setSku(string $sku): void
    {
        $this->sku = $sku;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setAttributeValues(array $attributeValues): void
    {
        $this->attributeValues = $attributeValues;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    /**
     * Check if variant has a specific attribute value
     */
    public function hasAttributeValue(string $attributeId, string $attributeValueId): bool
    {
        return isset($this->attributeValues[$attributeId]) && 
               $this->attributeValues[$attributeId] === $attributeValueId;
    }

    /**
     * Get attribute value for a specific attribute
     */
    public function getAttributeValue(string $attributeId): ?string
    {
        return $this->attributeValues[$attributeId] ?? null;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'sku' => $this->sku,
            'attribute_values' => $this->attributeValues,
            'quantity' => $this->quantity,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
