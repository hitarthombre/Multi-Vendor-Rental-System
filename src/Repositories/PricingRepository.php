<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\Pricing;

/**
 * Pricing Repository
 * 
 * Handles database operations for Pricing entities
 */
class PricingRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new pricing rule
     * 
     * @param Pricing $pricing
     * @return bool
     * @throws PDOException
     */
    public function create(Pricing $pricing): bool
    {
        $sql = "INSERT INTO pricing (id, product_id, variant_id, duration_unit, price_per_unit, minimum_duration, created_at, updated_at) 
                VALUES (:id, :product_id, :variant_id, :duration_unit, :price_per_unit, :minimum_duration, :created_at, :updated_at)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $pricing->getId(),
                ':product_id' => $pricing->getProductId(),
                ':variant_id' => $pricing->getVariantId(),
                ':duration_unit' => $pricing->getDurationUnit(),
                ':price_per_unit' => $pricing->getPricePerUnit(),
                ':minimum_duration' => $pricing->getMinimumDuration(),
                ':created_at' => $pricing->getCreatedAt(),
                ':updated_at' => $pricing->getUpdatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create pricing: " . $e->getMessage());
        }
    }

    /**
     * Find pricing by ID
     * 
     * @param string $id
     * @return Pricing|null
     */
    public function findById(string $id): ?Pricing
    {
        $sql = "SELECT * FROM pricing WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        return $this->hydrate($row);
    }

    /**
     * Find all pricing rules for a product
     * 
     * @param string $productId
     * @return Pricing[]
     */
    public function findByProductId(string $productId): array
    {
        $sql = "SELECT * FROM pricing WHERE product_id = :product_id AND variant_id IS NULL ORDER BY duration_unit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $pricingRules = [];
        foreach ($rows as $row) {
            $pricingRules[] = $this->hydrate($row);
        }
        
        return $pricingRules;
    }

    /**
     * Find all pricing rules for a variant
     * 
     * @param string $variantId
     * @return Pricing[]
     */
    public function findByVariantId(string $variantId): array
    {
        $sql = "SELECT * FROM pricing WHERE variant_id = :variant_id ORDER BY duration_unit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':variant_id' => $variantId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $pricingRules = [];
        foreach ($rows as $row) {
            $pricingRules[] = $this->hydrate($row);
        }
        
        return $pricingRules;
    }

    /**
     * Find all pricing rules for a product/variant combination
     * 
     * @param string $productId
     * @param string|null $variantId
     * @return Pricing[]
     */
    public function findByProductAndVariant(string $productId, ?string $variantId): array
    {
        if ($variantId) {
            $sql = "SELECT * FROM pricing WHERE product_id = :product_id AND variant_id = :variant_id ORDER BY duration_unit";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':product_id' => $productId,
                ':variant_id' => $variantId
            ]);
        } else {
            $sql = "SELECT * FROM pricing WHERE product_id = :product_id AND variant_id IS NULL ORDER BY duration_unit";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':product_id' => $productId]);
        }
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $pricingRules = [];
        foreach ($rows as $row) {
            $pricingRules[] = $this->hydrate($row);
        }
        
        return $pricingRules;
    }

    /**
     * Find pricing by product/variant and duration unit
     * 
     * @param string $productId
     * @param string|null $variantId
     * @param string $durationUnit
     * @return Pricing|null
     */
    public function findByProductAndDuration(string $productId, ?string $variantId, string $durationUnit): ?Pricing
    {
        if ($variantId) {
            $sql = "SELECT * FROM pricing WHERE product_id = :product_id AND variant_id = :variant_id AND duration_unit = :duration_unit LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':product_id' => $productId,
                ':variant_id' => $variantId,
                ':duration_unit' => $durationUnit
            ]);
        } else {
            $sql = "SELECT * FROM pricing WHERE product_id = :product_id AND variant_id IS NULL AND duration_unit = :duration_unit LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':product_id' => $productId,
                ':duration_unit' => $durationUnit
            ]);
        }
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        return $this->hydrate($row);
    }

    /**
     * Update pricing rule
     * 
     * @param Pricing $pricing
     * @return bool
     * @throws PDOException
     */
    public function update(Pricing $pricing): bool
    {
        $sql = "UPDATE pricing 
                SET price_per_unit = :price_per_unit,
                    minimum_duration = :minimum_duration,
                    updated_at = :updated_at
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':price_per_unit' => $pricing->getPricePerUnit(),
                ':minimum_duration' => $pricing->getMinimumDuration(),
                ':updated_at' => $pricing->getUpdatedAt(),
                ':id' => $pricing->getId()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to update pricing: " . $e->getMessage());
        }
    }

    /**
     * Delete pricing rule
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM pricing WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Delete all pricing rules for a product
     * 
     * @param string $productId
     * @return bool
     */
    public function deleteByProductId(string $productId): bool
    {
        $sql = "DELETE FROM pricing WHERE product_id = :product_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':product_id' => $productId]);
    }

    /**
     * Delete all pricing rules for a variant
     * 
     * @param string $variantId
     * @return bool
     */
    public function deleteByVariantId(string $variantId): bool
    {
        $sql = "DELETE FROM pricing WHERE variant_id = :variant_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':variant_id' => $variantId]);
    }

    /**
     * Hydrate pricing object from database row
     * 
     * @param array $row
     * @return Pricing
     */
    private function hydrate(array $row): Pricing
    {
        return new Pricing(
            $row['id'],
            $row['product_id'],
            $row['variant_id'],
            $row['duration_unit'],
            (float)$row['price_per_unit'],
            (int)$row['minimum_duration'],
            $row['created_at'],
            $row['updated_at']
        );
    }
}
