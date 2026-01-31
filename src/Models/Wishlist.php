<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Wishlist Model
 * 
 * Represents a customer's wishlist item
 */
class Wishlist
{
    private string $id;
    private string $customerId;
    private string $productId;
    private string $createdAt;

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $customerId,
        string $productId,
        string $createdAt = ''
    ) {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->productId = $productId;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new wishlist item with generated ID
     */
    public static function create(
        string $customerId,
        string $productId
    ): self {
        $id = UUID::generate();
        
        return new self($id, $customerId, $productId);
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getProductId(): string
    {
        return $this->productId;
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
            'customer_id' => $this->customerId,
            'product_id' => $this->productId,
            'created_at' => $this->createdAt
        ];
    }
}