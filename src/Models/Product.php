<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Product Model
 * 
 * Represents a rental product in the system
 */
class Product
{
    private string $id;
    private string $vendorId;
    private string $name;
    private string $description;
    private ?string $categoryId;
    private array $images;
    private bool $verificationRequired;
    private float $securityDeposit;
    private ?string $depositDescription;
    private string $status;
    private string $productType;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Valid product statuses
     */
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_INACTIVE = 'Inactive';
    public const STATUS_DELETED = 'Deleted';

    /**
     * Valid product types
     */
    public const TYPE_RENTAL = 'rental';
    public const TYPE_SERVICE = 'service';

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $vendorId,
        string $name,
        string $description,
        ?string $categoryId,
        array $images,
        bool $verificationRequired,
        float $securityDeposit,
        ?string $depositDescription,
        string $status,
        string $productType,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->vendorId = $vendorId;
        $this->name = $name;
        $this->description = $description;
        $this->categoryId = $categoryId;
        $this->images = $images;
        $this->verificationRequired = $verificationRequired;
        $this->securityDeposit = $securityDeposit;
        $this->depositDescription = $depositDescription;
        $this->status = $status;
        $this->productType = $productType;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new product instance with generated ID
     */
    public static function create(
        string $vendorId,
        string $name,
        string $description,
        ?string $categoryId = null,
        array $images = [],
        bool $verificationRequired = false,
        float $securityDeposit = 0.00,
        ?string $depositDescription = null,
        string $status = self::STATUS_ACTIVE,
        string $productType = self::TYPE_RENTAL
    ): self {
        $id = UUID::generate();
        
        return new self(
            $id,
            $vendorId,
            $name,
            $description,
            $categoryId,
            $images,
            $verificationRequired,
            $securityDeposit,
            $depositDescription,
            $status,
            $productType
        );
    }

    /**
     * Check if status is valid
     */
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_DELETED
        ], true);
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getVendorId(): string
    {
        return $this->vendorId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function isVerificationRequired(): bool
    {
        return $this->verificationRequired;
    }

    public function getSecurityDeposit(): float
    {
        return $this->securityDeposit;
    }

    public function getDepositDescription(): ?string
    {
        return $this->depositDescription;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getProductType(): string
    {
        return $this->productType;
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

    public function setCategoryId(?string $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setImages(array $images): void
    {
        $this->images = $images;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setVerificationRequired(bool $verificationRequired): void
    {
        $this->verificationRequired = $verificationRequired;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setSecurityDeposit(float $securityDeposit): void
    {
        $this->securityDeposit = $securityDeposit;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setDepositDescription(?string $depositDescription): void
    {
        $this->depositDescription = $depositDescription;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setStatus(string $status): void
    {
        if (!self::isValidStatus($status)) {
            throw new \InvalidArgumentException("Invalid product status: {$status}");
        }
        $this->status = $status;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setProductType(string $productType): void
    {
        if (!self::isValidProductType($productType)) {
            throw new \InvalidArgumentException("Invalid product type: {$productType}");
        }
        $this->productType = $productType;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    /**
     * Check if product type is valid
     */
    public static function isValidProductType(string $productType): bool
    {
        return in_array($productType, [
            self::TYPE_RENTAL,
            self::TYPE_SERVICE
        ], true);
    }

    /**
     * Check if product is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if product is a rental product
     */
    public function isRentalProduct(): bool
    {
        return $this->productType === self::TYPE_RENTAL;
    }

    /**
     * Check if product is a service product
     */
    public function isServiceProduct(): bool
    {
        return $this->productType === self::TYPE_SERVICE;
    }

    /**
     * Check if product belongs to a specific vendor
     */
    public function belongsToVendor(string $vendorId): bool
    {
        return $this->vendorId === $vendorId;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendorId,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->categoryId,
            'images' => $this->images,
            'verification_required' => $this->verificationRequired,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
