<?php

namespace RentalPlatform\Models;

use DateTime;

/**
 * AuditLog Model
 * 
 * Represents an audit log entry for tracking sensitive actions in the system.
 * Audit logs are immutable records that track who did what, when, and what changed.
 * 
 * Requirements:
 * - 1.7: Log administrator privileged actions with timestamp
 * - 12.4: Log order status transitions with timestamp and actor
 * - 18.7: Log admin actions for audit purposes
 * - 21.6: Log all sensitive actions
 */
class AuditLog
{
    private string $id;
    private ?string $userId;
    private string $entityType;
    private string $entityId;
    private string $action;
    private ?array $oldValue;
    private ?array $newValue;
    private DateTime $timestamp;
    private string $ipAddress;

    /**
     * Create a new AuditLog instance
     * 
     * @param string $id Unique identifier (UUID)
     * @param string|null $userId User who performed the action (null for system actions)
     * @param string $entityType Type of entity affected (e.g., "Order", "Payment", "User")
     * @param string $entityId ID of the affected entity
     * @param string $action Action performed (e.g., "status_change", "approval", "refund")
     * @param array|null $oldValue Previous state (JSON-serializable)
     * @param array|null $newValue New state (JSON-serializable)
     * @param DateTime $timestamp When the action occurred
     * @param string $ipAddress IP address of the actor
     */
    public function __construct(
        string $id,
        ?string $userId,
        string $entityType,
        string $entityId,
        string $action,
        ?array $oldValue,
        ?array $newValue,
        DateTime $timestamp,
        string $ipAddress
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->action = $action;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
        $this->timestamp = $timestamp;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Create a new audit log entry
     * 
     * @param string|null $userId User who performed the action
     * @param string $entityType Type of entity affected
     * @param string $entityId ID of the affected entity
     * @param string $action Action performed
     * @param array|null $oldValue Previous state
     * @param array|null $newValue New state
     * @param string|null $ipAddress IP address (defaults to current request IP)
     * @return self
     */
    public static function create(
        ?string $userId,
        string $entityType,
        string $entityId,
        string $action,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $ipAddress = null
    ): self {
        $id = \RentalPlatform\Helpers\UUID::generate();
        $timestamp = new DateTime();
        
        // Get IP address from request if not provided
        if ($ipAddress === null) {
            $ipAddress = self::getClientIpAddress();
        }

        return new self(
            $id,
            $userId,
            $entityType,
            $entityId,
            $action,
            $oldValue,
            $newValue,
            $timestamp,
            $ipAddress
        );
    }

    /**
     * Get client IP address from request
     * 
     * @return string
     */
    private static function getClientIpAddress(): string
    {
        // Check for IP address in various headers (for proxy/load balancer scenarios)
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                // Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0'; // Default if no IP found
    }

    // Getters

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getOldValue(): ?array
    {
        return $this->oldValue;
    }

    public function getNewValue(): ?array
    {
        return $this->newValue;
    }

    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * Convert audit log to array representation
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'action' => $this->action,
            'old_value' => $this->oldValue,
            'new_value' => $this->newValue,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s'),
            'ip_address' => $this->ipAddress
        ];
    }

    /**
     * Get a human-readable description of the audit log entry
     * 
     * @return string
     */
    public function getDescription(): string
    {
        $userPart = $this->userId ? "User {$this->userId}" : "System";
        return "{$userPart} performed '{$this->action}' on {$this->entityType} {$this->entityId}";
    }

    /**
     * Check if this audit log has a change (old value differs from new value)
     * 
     * @return bool
     */
    public function hasChange(): bool
    {
        return $this->oldValue !== $this->newValue;
    }

    /**
     * Get the changes between old and new values
     * 
     * @return array Array of changed fields with old and new values
     */
    public function getChanges(): array
    {
        if (!$this->hasChange()) {
            return [];
        }

        $changes = [];
        $oldValue = $this->oldValue ?? [];
        $newValue = $this->newValue ?? [];

        // Find all keys from both arrays
        $allKeys = array_unique(array_merge(array_keys($oldValue), array_keys($newValue)));

        foreach ($allKeys as $key) {
            $old = $oldValue[$key] ?? null;
            $new = $newValue[$key] ?? null;

            if ($old !== $new) {
                $changes[$key] = [
                    'old' => $old,
                    'new' => $new
                ];
            }
        }

        return $changes;
    }
}
