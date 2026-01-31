<?php

namespace RentalPlatform\Repositories;

use PDO;
use DateTime;
use RentalPlatform\Models\AuditLog;

/**
 * AuditLog Repository
 * 
 * Handles database operations for audit log entries.
 * Audit logs are append-only (no updates or deletes).
 */
class AuditLogRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Save an audit log entry to the database
     * 
     * @param AuditLog $auditLog
     * @return bool True on success
     */
    public function save(AuditLog $auditLog): bool
    {
        $sql = "INSERT INTO audit_logs (
            id, user_id, entity_type, entity_id, action,
            old_value, new_value, timestamp, ip_address
        ) VALUES (
            :id, :user_id, :entity_type, :entity_id, :action,
            :old_value, :new_value, :timestamp, :ip_address
        )";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $auditLog->getId(),
            ':user_id' => $auditLog->getUserId(),
            ':entity_type' => $auditLog->getEntityType(),
            ':entity_id' => $auditLog->getEntityId(),
            ':action' => $auditLog->getAction(),
            ':old_value' => $auditLog->getOldValue() ? json_encode($auditLog->getOldValue()) : null,
            ':new_value' => $auditLog->getNewValue() ? json_encode($auditLog->getNewValue()) : null,
            ':timestamp' => $auditLog->getTimestamp()->format('Y-m-d H:i:s'),
            ':ip_address' => $auditLog->getIpAddress()
        ]);
    }

    /**
     * Find an audit log entry by ID
     * 
     * @param string $id
     * @return AuditLog|null
     */
    public function findById(string $id): ?AuditLog
    {
        $sql = "SELECT * FROM audit_logs WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Find all audit logs for a specific user
     * 
     * @param string $userId
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array Array of AuditLog objects
     */
    public function findByUserId(string $userId, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM audit_logs 
                WHERE user_id = :user_id 
                ORDER BY timestamp DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Find all audit logs for a specific entity
     * 
     * @param string $entityType
     * @param string $entityId
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array Array of AuditLog objects
     */
    public function findByEntity(
        string $entityType,
        string $entityId,
        int $limit = 100,
        int $offset = 0
    ): array {
        $sql = "SELECT * FROM audit_logs 
                WHERE entity_type = :entity_type AND entity_id = :entity_id 
                ORDER BY timestamp DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':entity_type', $entityType, PDO::PARAM_STR);
        $stmt->bindValue(':entity_id', $entityId, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Find all audit logs for a specific action
     * 
     * @param string $action
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array Array of AuditLog objects
     */
    public function findByAction(string $action, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM audit_logs 
                WHERE action = :action 
                ORDER BY timestamp DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Find audit logs within a date range
     * 
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array Array of AuditLog objects
     */
    public function findByDateRange(
        DateTime $startDate,
        DateTime $endDate,
        int $limit = 100,
        int $offset = 0
    ): array {
        $sql = "SELECT * FROM audit_logs 
                WHERE timestamp BETWEEN :start_date AND :end_date 
                ORDER BY timestamp DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Find recent audit logs
     * 
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array Array of AuditLog objects
     */
    public function findRecent(int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM audit_logs 
                ORDER BY timestamp DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Search audit logs with multiple filters
     * 
     * @param array $filters Associative array of filters
     *   - user_id: string
     *   - entity_type: string
     *   - entity_id: string
     *   - action: string
     *   - start_date: DateTime
     *   - end_date: DateTime
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array Array of AuditLog objects
     */
    public function search(array $filters, int $limit = 100, int $offset = 0): array
    {
        $conditions = [];
        $params = [];

        if (isset($filters['user_id'])) {
            $conditions[] = "user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (isset($filters['entity_type'])) {
            $conditions[] = "entity_type = :entity_type";
            $params[':entity_type'] = $filters['entity_type'];
        }

        if (isset($filters['entity_id'])) {
            $conditions[] = "entity_id = :entity_id";
            $params[':entity_id'] = $filters['entity_id'];
        }

        if (isset($filters['action'])) {
            $conditions[] = "action = :action";
            $params[':action'] = $filters['action'];
        }

        if (isset($filters['start_date'])) {
            $conditions[] = "timestamp >= :start_date";
            $params[':start_date'] = $filters['start_date']->format('Y-m-d H:i:s');
        }

        if (isset($filters['end_date'])) {
            $conditions[] = "timestamp <= :end_date";
            $params[':end_date'] = $filters['end_date']->format('Y-m-d H:i:s');
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT * FROM audit_logs 
                {$whereClause}
                ORDER BY timestamp DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Count total audit logs (optionally filtered)
     * 
     * @param array $filters Optional filters (same as search method)
     * @return int
     */
    public function count(array $filters = []): int
    {
        $conditions = [];
        $params = [];

        if (isset($filters['user_id'])) {
            $conditions[] = "user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (isset($filters['entity_type'])) {
            $conditions[] = "entity_type = :entity_type";
            $params[':entity_type'] = $filters['entity_type'];
        }

        if (isset($filters['entity_id'])) {
            $conditions[] = "entity_id = :entity_id";
            $params[':entity_id'] = $filters['entity_id'];
        }

        if (isset($filters['action'])) {
            $conditions[] = "action = :action";
            $params[':action'] = $filters['action'];
        }

        if (isset($filters['start_date'])) {
            $conditions[] = "timestamp >= :start_date";
            $params[':start_date'] = $filters['start_date']->format('Y-m-d H:i:s');
        }

        if (isset($filters['end_date'])) {
            $conditions[] = "timestamp <= :end_date";
            $params[':end_date'] = $filters['end_date']->format('Y-m-d H:i:s');
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT COUNT(*) FROM audit_logs {$whereClause}";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Hydrate an AuditLog object from database row
     * 
     * @param array $row
     * @return AuditLog
     */
    private function hydrate(array $row): AuditLog
    {
        return new AuditLog(
            $row['id'],
            $row['user_id'],
            $row['entity_type'],
            $row['entity_id'],
            $row['action'],
            $row['old_value'] ? json_decode($row['old_value'], true) : null,
            $row['new_value'] ? json_decode($row['new_value'], true) : null,
            new DateTime($row['timestamp']),
            $row['ip_address']
        );
    }
}
    /**
     * Find audit logs by action
     */
    public function findByAction(string $action, int $limit = 50): array
    {
        $sql = "SELECT * FROM audit_logs WHERE action = :action ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $logs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs[] = $this->hydrate($row);
        }
        
        return $logs;
    }

    /**
     * Get error statistics
     */
    public function getErrorStatistics(): array
    {
        $sql = "SELECT 
                    JSON_EXTRACT(new_data, '$.error_type') as error_type,
                    COUNT(*) as count,
                    MAX(created_at) as latest_occurrence
                FROM audit_logs 
                WHERE action = 'error_logged' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY JSON_EXTRACT(new_data, '$.error_type')
                ORDER BY count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[] = [
                'error_type' => trim($row['error_type'], '"'),
                'count' => (int)$row['count'],
                'latest_occurrence' => $row['latest_occurrence']
            ];
        }
        
        return $stats;
    }