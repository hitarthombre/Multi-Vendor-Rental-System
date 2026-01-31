<?php

namespace RentalPlatform\Models;

use DateTime;

/**
 * InventoryLock Model
 * 
 * Represents a time-based lock on inventory to prevent double-booking
 */
class InventoryLock
{
    private string $id;
    private string $variantId;
    private string $orderId;
    private DateTime $startDate;
    private DateTime $endDate;
    private string $status; // 'active', 'released'
    private DateTime $createdAt;
    private ?DateTime $releasedAt;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RELEASED = 'released';

    public function __construct(
        string $id,
        string $variantId,
        string $orderId,
        DateTime $startDate,
        DateTime $endDate,
        string $status = self::STATUS_ACTIVE,
        ?DateTime $createdAt = null,
        ?DateTime $releasedAt = null
    ) {
        $this->id = $id;
        $this->variantId = $variantId;
        $this->orderId = $orderId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->releasedAt = $releasedAt;
    }

    /**
     * Create a new inventory lock
     */
    public static function create(
        string $variantId,
        string $orderId,
        DateTime $startDate,
        DateTime $endDate
    ): self {
        return new self(
            \RentalPlatform\Helpers\UUID::generate(),
            $variantId,
            $orderId,
            $startDate,
            $endDate,
            self::STATUS_ACTIVE
        );
    }

    /**
     * Check if this lock overlaps with a given time period
     */
    public function overlaps(DateTime $startDate, DateTime $endDate): bool
    {
        // Two periods overlap if:
        // - One starts before the other ends AND
        // - One ends after the other starts
        return $this->startDate < $endDate && $this->endDate > $startDate;
    }

    /**
     * Release this lock
     */
    public function release(): void
    {
        $this->status = self::STATUS_RELEASED;
        $this->releasedAt = new DateTime();
    }

    /**
     * Check if lock is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getVariantId(): string
    {
        return $this->variantId;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getReleasedAt(): ?DateTime
    {
        return $this->releasedAt;
    }
}
