<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Category Model
 * 
 * Represents a product category in the system
 */
class Category
{
    private string $id;
    private string $name;
    private string $description;
    private ?string $parentId;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $name,
        string $description,
        ?string $parentId,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->parentId = $parentId;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new category instance with generated ID
     */
    public static function create(
        string $name,
        string $description = '',
        ?string $parentId = null
    ): self {
        $id = UUID::generate();
        
        return new self($id, $name, $description, $parentId);
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
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
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setParentId(?string $parentId): void
    {
        $this->parentId = $parentId;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    /**
     * Check if this is a root category (no parent)
     */
    public function isRootCategory(): bool
    {
        return $this->parentId === null;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'parent_id' => $this->parentId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
