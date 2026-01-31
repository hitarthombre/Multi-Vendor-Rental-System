<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\AttributeValue;

/**
 * AttributeValue Repository
 * 
 * Handles database operations for AttributeValue entities
 */
class AttributeValueRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new attribute value
     * 
     * @param AttributeValue $attributeValue
     * @return bool
     * @throws PDOException
     */
    public function create(AttributeValue $attributeValue): bool
    {
        $sql = "INSERT INTO attribute_values (id, attribute_id, value, created_at) 
                VALUES (:id, :attribute_id, :value, :created_at)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $attributeValue->getId(),
                ':attribute_id' => $attributeValue->getAttributeId(),
                ':value' => $attributeValue->getValue(),
                ':created_at' => $attributeValue->getCreatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create attribute value: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Find attribute value by ID
     * 
     * @param string $id
     * @return AttributeValue|null
     */
    public function findById(string $id): ?AttributeValue
    {
        $sql = "SELECT * FROM attribute_values WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find all attribute values for a specific attribute
     * 
     * @param string $attributeId
     * @return AttributeValue[]
     */
    public function findByAttributeId(string $attributeId): array
    {
        $sql = "SELECT * FROM attribute_values WHERE attribute_id = :attribute_id ORDER BY value ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':attribute_id' => $attributeId]);
        
        $values = [];
        while ($data = $stmt->fetch()) {
            $values[] = $this->hydrate($data);
        }
        
        return $values;
    }

    /**
     * Find attribute value by attribute ID and value
     * 
     * @param string $attributeId
     * @param string $value
     * @return AttributeValue|null
     */
    public function findByAttributeIdAndValue(string $attributeId, string $value): ?AttributeValue
    {
        $sql = "SELECT * FROM attribute_values WHERE attribute_id = :attribute_id AND value = :value LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':attribute_id' => $attributeId, ':value' => $value]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find all attribute values
     * 
     * @return AttributeValue[]
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM attribute_values ORDER BY attribute_id, value ASC";
        
        $stmt = $this->db->query($sql);
        
        $values = [];
        while ($data = $stmt->fetch()) {
            $values[] = $this->hydrate($data);
        }
        
        return $values;
    }

    /**
     * Delete attribute value
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM attribute_values WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Delete all attribute values for a specific attribute
     * 
     * @param string $attributeId
     * @return bool
     */
    public function deleteByAttributeId(string $attributeId): bool
    {
        $sql = "DELETE FROM attribute_values WHERE attribute_id = :attribute_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':attribute_id' => $attributeId]);
    }

    /**
     * Check if attribute value exists
     * 
     * @param string $attributeId
     * @param string $value
     * @return bool
     */
    public function valueExists(string $attributeId, string $value): bool
    {
        $sql = "SELECT COUNT(*) FROM attribute_values WHERE attribute_id = :attribute_id AND value = :value";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':attribute_id' => $attributeId, ':value' => $value]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Count attribute values for a specific attribute
     * 
     * @param string $attributeId
     * @return int
     */
    public function countByAttributeId(string $attributeId): int
    {
        $sql = "SELECT COUNT(*) FROM attribute_values WHERE attribute_id = :attribute_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':attribute_id' => $attributeId]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Hydrate attribute value from database row
     * 
     * @param array $data
     * @return AttributeValue
     */
    private function hydrate(array $data): AttributeValue
    {
        return new AttributeValue(
            $data['id'],
            $data['attribute_id'],
            $data['value'],
            $data['created_at']
        );
    }
}
