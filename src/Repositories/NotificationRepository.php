<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\Notification;

/**
 * Notification Repository
 * 
 * Handles database operations for Notification entities
 */
class NotificationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new notification
     * 
     * @param Notification $notification
     * @return bool
     * @throws PDOException
     */
    public function create(Notification $notification): bool
    {
        $sql = "INSERT INTO notifications (id, user_id, event_type, subject, body, sent_at, status, created_at, updated_at) 
                VALUES (:id, :user_id, :event_type, :subject, :body, :sent_at, :status, :created_at, :updated_at)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $notification->getId(),
                ':user_id' => $notification->getUserId(),
                ':event_type' => $notification->getEventType(),
                ':subject' => $notification->getSubject(),
                ':body' => $notification->getBody(),
                ':sent_at' => $notification->getSentAt(),
                ':status' => $notification->getStatus(),
                ':created_at' => $notification->getCreatedAt(),
                ':updated_at' => $notification->getUpdatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create notification: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Find notification by ID
     * 
     * @param string $id
     * @return Notification|null
     */
    public function findById(string $id): ?Notification
    {
        $sql = "SELECT * FROM notifications WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Update notification
     * 
     * @param Notification $notification
     * @return bool
     * @throws PDOException
     */
    public function update(Notification $notification): bool
    {
        $sql = "UPDATE notifications 
                SET user_id = :user_id,
                    event_type = :event_type,
                    subject = :subject,
                    body = :body,
                    sent_at = :sent_at,
                    status = :status,
                    updated_at = :updated_at
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $notification->getId(),
                ':user_id' => $notification->getUserId(),
                ':event_type' => $notification->getEventType(),
                ':subject' => $notification->getSubject(),
                ':body' => $notification->getBody(),
                ':sent_at' => $notification->getSentAt(),
                ':status' => $notification->getStatus(),
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to update notification: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Get pending notifications
     * 
     * @param int $limit
     * @return Notification[]
     */
    public function getPendingNotifications(int $limit = 50): array
    {
        $sql = "SELECT * FROM notifications 
                WHERE status = :status 
                ORDER BY created_at ASC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', Notification::STATUS_PENDING, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = [];
        while ($data = $stmt->fetch()) {
            $notifications[] = $this->hydrate($data);
        }
        
        return $notifications;
    }

    /**
     * Get notifications by user ID
     * 
     * @param string $userId
     * @param int $limit
     * @return Notification[]
     */
    public function findByUserId(string $userId, int $limit = 100): array
    {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = [];
        while ($data = $stmt->fetch()) {
            $notifications[] = $this->hydrate($data);
        }
        
        return $notifications;
    }

    /**
     * Get failed notifications for retry (with backoff)
     * Only returns notifications that failed more than the specified minutes ago
     * 
     * @param int $limit
     * @param int $backoffMinutes Minimum minutes since last failure before retry
     * @return Notification[]
     */
    public function getFailedNotifications(int $limit = 20, int $backoffMinutes = 30): array
    {
        $sql = "SELECT * FROM notifications 
                WHERE status = :status 
                AND updated_at < DATE_SUB(NOW(), INTERVAL :backoff_minutes MINUTE)
                ORDER BY created_at ASC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', Notification::STATUS_FAILED, PDO::PARAM_STR);
        $stmt->bindValue(':backoff_minutes', $backoffMinutes, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = [];
        while ($data = $stmt->fetch()) {
            $notifications[] = $this->hydrate($data);
        }
        
        return $notifications;
    }

    /**
     * Delete old notifications
     * 
     * @param int $daysOld
     * @return int Number of deleted notifications
     */
    public function deleteOldNotifications(int $daysOld = 30): int
    {
        $sql = "DELETE FROM notifications 
                WHERE status = :status 
                AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':status' => Notification::STATUS_SENT,
            ':days' => $daysOld
        ]);
        
        return $stmt->rowCount();
    }

    /**
     * Get notification statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count,
                    MIN(created_at) as oldest,
                    MAX(created_at) as newest
                FROM notifications 
                GROUP BY status";
        
        $stmt = $this->db->query($sql);
        $stats = [];
        
        while ($row = $stmt->fetch()) {
            $stats[$row['status']] = [
                'count' => (int)$row['count'],
                'oldest' => $row['oldest'],
                'newest' => $row['newest']
            ];
        }
        
        return $stats;
    }

    /**
     * Get notifications by event type and status
     * 
     * @param string $eventType
     * @param string|null $status
     * @param int $limit
     * @return Notification[]
     */
    public function findByEventType(string $eventType, ?string $status = null, int $limit = 50): array
    {
        if ($status) {
            $sql = "SELECT * FROM notifications 
                    WHERE event_type = :event_type AND status = :status 
                    ORDER BY created_at DESC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':event_type', $eventType, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        } else {
            $sql = "SELECT * FROM notifications 
                    WHERE event_type = :event_type 
                    ORDER BY created_at DESC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':event_type', $eventType, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        $notifications = [];
        while ($data = $stmt->fetch()) {
            $notifications[] = $this->hydrate($data);
        }
        
        return $notifications;
    }

    /**
     * Mark notifications as failed in batch
     * 
     * @param array $notificationIds
     * @return int Number of updated notifications
     */
    public function markAsFailed(array $notificationIds): int
    {
        if (empty($notificationIds)) {
            return 0;
        }
        
        $placeholders = str_repeat('?,', count($notificationIds) - 1) . '?';
        $sql = "UPDATE notifications 
                SET status = ?, updated_at = NOW() 
                WHERE id IN ({$placeholders})";
        
        $stmt = $this->db->prepare($sql);
        $params = [Notification::STATUS_FAILED, ...$notificationIds];
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }

    /**
     * Hydrate notification from database row
     * 
     * @param array $data
     * @return Notification
     */
    private function hydrate(array $data): Notification
    {
        return new Notification(
            $data['id'],
            $data['user_id'],
            $data['event_type'],
            $data['subject'],
            $data['body'],
            $data['sent_at'],
            $data['status'],
            $data['created_at'],
            $data['updated_at']
        );
    }
}