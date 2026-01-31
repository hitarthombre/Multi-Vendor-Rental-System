<?php

namespace RentalPlatform\Models;

use DateTime;

/**
 * Cart Model
 * 
 * Represents a shopping cart for a customer
 */
class Cart
{
    private string $id;
    private string $customerId;
    private array $items; // Array of CartItem objects
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        string $id,
        string $customerId,
        array $items = [],
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->items = $items;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
    }

    /**
     * Create a new cart
     */
    public static function create(string $customerId): self
    {
        return new self(
            \RentalPlatform\Helpers\UUID::generate(),
            $customerId
        );
    }

    /**
     * Add item to cart
     */
    public function addItem(CartItem $item): void
    {
        $this->items[] = $item;
        $this->updatedAt = new DateTime();
    }

    /**
     * Remove item from cart
     */
    public function removeItem(string $itemId): void
    {
        $this->items = array_filter($this->items, function($item) use ($itemId) {
            return $item->getId() !== $itemId;
        });
        $this->items = array_values($this->items); // Re-index array
        $this->updatedAt = new DateTime();
    }

    /**
     * Update item quantity
     */
    public function updateItemQuantity(string $itemId, int $quantity): void
    {
        foreach ($this->items as $item) {
            if ($item->getId() === $itemId) {
                $item->setQuantity($quantity);
                $this->updatedAt = new DateTime();
                break;
            }
        }
    }

    /**
     * Get item by ID
     */
    public function getItem(string $itemId): ?CartItem
    {
        foreach ($this->items as $item) {
            if ($item->getId() === $itemId) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Clear all items from cart
     */
    public function clear(): void
    {
        $this->items = [];
        $this->updatedAt = new DateTime();
    }

    /**
     * Get total number of items
     */
    public function getItemCount(): int
    {
        return count($this->items);
    }

    /**
     * Get total quantity of all items
     */
    public function getTotalQuantity(): int
    {
        return array_reduce($this->items, function($total, $item) {
            return $total + $item->getQuantity();
        }, 0);
    }

    /**
     * Calculate total price
     */
    public function getTotalPrice(): float
    {
        return array_reduce($this->items, function($total, $item) {
            return $total + $item->getSubtotal();
        }, 0.0);
    }

    /**
     * Group items by vendor
     */
    public function groupByVendor(): array
    {
        $grouped = [];
        foreach ($this->items as $item) {
            $vendorId = $item->getVendorId();
            if (!isset($grouped[$vendorId])) {
                $grouped[$vendorId] = [];
            }
            $grouped[$vendorId][] = $item;
        }
        return $grouped;
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

    public function getItems(): array
    {
        return $this->items;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    // Setters
    public function setItems(array $items): void
    {
        $this->items = $items;
        $this->updatedAt = new DateTime();
    }
}
