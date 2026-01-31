<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\Variant;

/**
 * Variant Repository
 * 
 * Handles database operations for Variant entities
 */
class VariantRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new variant
     * 
     * @param Variant $variant
     * @return bool
     * @throws PDOException
     */
    public function create(Variant $variant): bool
    {
        $sql = "INSERT INTO variants (id, product_id, sku, attribute_values, quantity, created_at, updated_at) 
                VALUES (:id, :product_id, :sku, :attribute_values, :quantity, :created_at, :updated_at)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $variant->getId(),
                ':product_id' => $variant->getProductId(),
                ':sku' => $variant->getSku(),
                ':attribute_values' => json_encode($variant->getAttributeValues()),
                ':quantity' => $variant->getQuantity(),
                ':created_at' => $variant->getCreatedAt(),
                ':updated_at' => $variant->getUpdatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create variant: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Find variant by ID
     * 
     * @param string $id
     * @return Variant|null
     */
    public function findById(string $id): ?Variant
    {
        $sql = "SELECT * FROM variants WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find variant by SKU
     * 
     * @param string $sku
     * @return Variant|null
     */
    public function findBySku(string $sku): ?Variant
    {
        $sql = "SELECT * FROM variants WHERE sku = :sku LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':sku' => $sku]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find all variants for a specific product
     * 
     * @param string $productId
     * @return Variant[]
     */
    public function findByProductId(string $productId): array
    {
        $sql = "SELECT * FROM variants WHERE product_id = :product_id ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        
        $variants = [];
        while ($data = $stmt->fetch()) {
            $variants[] = $this->hydrate($data);
        }
        
        return $variants;
    }

    /**
     * Find all variants
     * 
     * @return Variant[]
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM variants ORDER BY created_at DESC";
        
        $stmt = $this->db->query($sql);
        
        $variants = [];
        while ($data = $stmt->fetch()) {
            $variants[] = $this->hydrate($data);
        }
        
        return $variants;
    }

    /**
     * Update variant
     * 
     * @param Variant $variant
     * @return bool
     * @throws PDOException
     */
    public function update(Variant $variant): bool
    {
        $sql = "UPDATE variants 
                SET sku = :sku, 
                    attribute_values = :attribute_values, 
                    quantity = :quantity,
                    updated_at = :updated_at
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $variant->getId(),
                ':sku' => $variant->getSku(),
                ':attribute_values' => json_encode($variant->getAttributeValues()),
                ':quantity' => $variant->getQuantity(),
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to update variant: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Delete variant
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM variants WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Delete all variants for a specific product
     * 
     * @param string $productId
     * @return bool
     */
    public function deleteByProductId(string $productId): bool
    {
        $sql = "DELETE FROM variants WHERE product_id = :product_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':product_id' => $productId]);
    }

    /**
     * Check if SKU exists
     * 
     * @param string $sku
     * @return bool
     */
    public function skuExists(string $sku): bool
    {
        $sql = "SELECT COUNT(*) FROM variants WHERE sku = :sku";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':sku' => $sku]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Count variants for a specific product
     * 
     * @param string $productId
     * @return int
     */
    public function countByProductId(string $productId): int
    {
        $sql = "SELECT COUNT(*) FROM variants WHERE product_id = :product_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Find variant by product ID and attribute values
     * 
     * @param string $productId
     * @param array $attributeValues
     * @return Variant|null
     */
    public function findByProductIdAndAttributes(string $productId, array $attributeValues): ?Variant
    {
        // Get all variants for the product
        $variants = $this->findByProductId($productId);
        
        // Sort attribute values for comparison
        ksort($attributeValues);
        
        // Find matching variant
        foreach ($variants as $variant) {
            $variantAttributes = $variant->getAttributeValues();
            ksort($variantAttributes);
            
            if ($variantAttributes === $attributeValues) {
                return $variant;
            }
        }
        
        return null;
    }

    /**
     * Hydrate variant from database row
     * 
     * @param array $data
     * @return Variant
     */
    private function hydrate(array $data): Variant
    {
        $attributeValues = json_decode($data['attribute_values'], true) ?? [];
        
        return new Variant(
            $data['id'],
            $data['product_id'],
            $data['sku'],
            $attributeValues,
            (int)$data['quantity'],
            $data['created_at'],
            $data['updated_at']
        );
    }
}
