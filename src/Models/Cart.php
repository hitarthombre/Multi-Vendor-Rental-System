<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Cart Model
 * 
 * Represents a customer's shopping cart
 */
class Cart
{
    private string $id;
    private string $customerId;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $customerId,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new cart with generated ID
     */
    public static function create(string $customerId): self
    {
        $id = UUID::generate();
        
        return new self($id, $customerId);
    }

    /**
     * Update the cart's updated timestamp
     */
    public function touch(): void
    {
        $this->updatedAt = date('Y-m-d H:i:s');
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

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customerId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}