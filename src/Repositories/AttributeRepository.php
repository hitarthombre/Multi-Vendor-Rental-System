<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\Attribute;

/**
 * Attribute Repository
 * 
 * Handles database operations for Attribute entities
 */
class AttributeRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new attribute
     * 
     * @param Attribute $attribute
     * @return bool
     * @throws PDOException
     */
    public function create(Attribute $attribute): bool
    {
        $sql = "INSERT INTO attributes (id, name, type, created_at) 
                VALUES (:id, :name, :type, :created_at)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $attribute->getId(),
                ':name' => $attribute->getName(),
                ':type' => $attribute->getType(),
                ':created_at' => $attribute->getCreatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create attribute: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Find attribute by ID
     * 
     * @param string $id
     * @return Attribute|null
     */
    public function findById(string $id): ?Attribute
    {
        $sql = "SELECT * FROM attributes WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find attribute by name
     * 
     * @param string $name
     * @return Attribute|null
     */
    public function findByName(string $name): ?Attribute
    {
        $sql = "SELECT * FROM attributes WHERE name = :name LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':name' => $name]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find all attributes
     * 
     * @return Attribute[]
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM attributes ORDER BY name ASC";
        
        $stmt = $this->db->query($sql);
        
        $attributes = [];
        while ($data = $stmt->fetch()) {
            $attributes[] = $this->hydrate($data);
        }
        
        return $attributes;
    }

    /**
     * Find attributes by type
     * 
     * @param string $type
     * @return Attribute[]
     */
    public function findByType(string $type): array
    {
        $sql = "SELECT * FROM attributes WHERE type = :type ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':type' => $type]);
        
        $attributes = [];
        while ($data = $stmt->fetch()) {
            $attributes[] = $this->hydrate($data);
        }
        
        return $attributes;
    }

    /**
     * Delete attribute
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM attributes WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Check if attribute name exists
     * 
     * @param string $name
     * @return bool
     */
    public function nameExists(string $name): bool
    {
        $sql = "SELECT COUNT(*) FROM attributes WHERE name = :name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':name' => $name]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Hydrate attribute from database row
     * 
     * @param array $data
     * @return Attribute
     */
    private function hydrate(array $data): Attribute
    {
        return new Attribute(
            $data['id'],
            $data['name'],
            $data['type'],
            $data['created_at']
        );
    }
}
