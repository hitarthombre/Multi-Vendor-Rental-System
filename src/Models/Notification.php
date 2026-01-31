<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Notification Model
 * 
 * Represents an email notification in the system
 */
class Notification
{
    private string $id;
    private string $userId;
    private string $eventType;
    private string $subject;
    private string $body;
    private ?string $sentAt;
    private string $status;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Notification status constants
     */
    public const STATUS_PENDING = 'Pending';
    public const STATUS_SENT = 'Sent';
    public const STATUS_FAILED = 'Failed';

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $userId,
        string $eventType,
        string $subject,
        string $body,
        ?string $sentAt = null,
        string $status = self::STATUS_PENDING,
        string $createdAt = '',
        string $updatedAt = ''
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->eventType = $eventType;
        $this->subject = $subject;
        $this->body = $body;
        $this->sentAt = $sentAt;
        $this->status = $status;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new notification instance
     */
    public static function create(
        string $userId,
        string $eventType,
        string $subject,
        string $body
    ): self {
        $id = UUID::generate();
        
        return new self($id, $userId, $eventType, $subject, $body);
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(): void
    {
        $this->status = self::STATUS_SENT;
        $this->sentAt = date('Y-m-d H:i:s');
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed(): void
    {
        $this->status = self::STATUS_FAILED;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    /**
     * Check if notification is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if notification was sent
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if notification failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
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

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getSentAt(): ?string
    {
        return $this->sentAt;
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

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'event_type' => $this->eventType,
            'subject' => $this->subject,
            'body' => $this->body,
            'sent_at' => $this->sentAt,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}