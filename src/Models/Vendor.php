<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Vendor Model
 * 
 * Represents a vendor business profile in the system
 */
class Vendor
{
    private string $id;
    private string $userId;
    private string $businessName;
    private string $legalName;
    private ?string $taxId;
    private ?string $logo;
    private ?string $brandColor;
    private ?string $contactEmail;
    private ?string $contactPhone;
    private string $status;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Valid vendor statuses
     */
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_SUSPENDED = 'Suspended';
    public const STATUS_PENDING = 'Pending';

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $userId,
        string $businessName,
        string $legalName,
        ?string $taxId = null,
        ?string $logo = null,
        ?string $brandColor = null,
        ?string $contactEmail = null,
        ?string $contactPhone = null,
        string $status = self::STATUS_ACTIVE,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->businessName = $businessName;
        $this->legalName = $legalName;
        $this->taxId = $taxId;
        $this->logo = $logo;
        $this->brandColor = $brandColor;
        $this->contactEmail = $contactEmail;
        $this->contactPhone = $contactPhone;
        $this->status = $status;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new vendor instance with generated ID
     */
    public static function create(
        string $userId,
        string $businessName,
        string $legalName,
        ?string $taxId = null,
        ?string $contactEmail = null,
        ?string $contactPhone = null
    ): self {
        $id = UUID::generate();
        
        return new self(
            $id,
            $userId,
            $businessName,
            $legalName,
            $taxId,
            null, // logo
            '#3b82f6', // default brand color
            $contactEmail,
            $contactPhone,
            self::STATUS_ACTIVE
        );
    }

    /**
     * Check if status is valid
     */
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, [
            self::STATUS_ACTIVE,
            self::STATUS_SUSPENDED,
            self::STATUS_PENDING
        ], true);
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getBusinessName(): string
    {
        return $this->businessName;
    }

    public function getLegalName(): string
    {
        return $this->legalName;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function getBrandColor(): ?string
    {
        return $this->brandColor;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function getStatus(): string
    {
        return $this->status;
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
    public function setBusinessName(string $businessName): void
    {
        $this->businessName = $businessName;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setLegalName(string $legalName): void
    {
        $this->legalName = $legalName;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setTaxId(?string $taxId): void
    {
        $this->taxId = $taxId;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setBrandColor(?string $brandColor): void
    {
        $this->brandColor = $brandColor;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setContactEmail(?string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setContactPhone(?string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function setStatus(string $status): void
    {
        if (!self::isValidStatus($status)) {
            throw new \InvalidArgumentException("Invalid vendor status: {$status}");
        }
        $this->status = $status;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    /**
     * Check if vendor is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if vendor is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if vendor is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'business_name' => $this->businessName,
            'legal_name' => $this->legalName,
            'tax_id' => $this->taxId,
            'logo' => $this->logo,
            'brand_color' => $this->brandColor,
            'contact_email' => $this->contactEmail,
            'contact_phone' => $this->contactPhone,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
