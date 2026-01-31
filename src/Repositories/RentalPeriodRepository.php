<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\RentalPeriod;
use DateTime;

/**
 * RentalPeriod Repository
 * 
 * Handles database operations for RentalPeriod entities
 */
class RentalPeriodRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new rental period
     * 
     * @param RentalPeriod $rentalPeriod
     * @return bool
     * @throws PDOException
     */
    public function create(RentalPeriod $rentalPeriod): bool
    {
        $sql = "INSERT INTO rental_periods (id, start_datetime, end_datetime, duration_value, duration_unit) 
                VALUES (?, ?, ?, ?, ?)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $rentalPeriod->getId(),
                $rentalPeriod->getStartDateTimeString(),
                $rentalPeriod->getEndDateTimeString(),
                $rentalPeriod->getDurationValue(),
                $rentalPeriod->getDurationUnit()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create rental period: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Find rental period by ID
     * 
     * @param string $id
     * @return RentalPeriod|null
     */
    public function findById(string $id): ?RentalPeriod
    {
        $sql = "SELECT * FROM rental_periods WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $data = $stmt->fetch();
        
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Update rental period
     * 
     * @param RentalPeriod $rentalPeriod
     * @return bool
     */
    public function update(RentalPeriod $rentalPeriod): bool
    {
        $sql = "UPDATE rental_periods 
                SET start_datetime = ?, end_datetime = ?, duration_value = ?, duration_unit = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $rentalPeriod->getStartDateTimeString(),
            $rentalPeriod->getEndDateTimeString(),
            $rentalPeriod->getDurationValue(),
            $rentalPeriod->getDurationUnit(),
            $rentalPeriod->getId()
        ]);
    }

    /**
     * Delete rental period
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM rental_periods WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Find overlapping rental periods for a product/variant
     * 
     * @param string $productId
     * @param string|null $variantId
     * @param RentalPeriod $period
     * @return RentalPeriod[]
     */
    public function findOverlapping(string $productId, ?string $variantId, RentalPeriod $period): array
    {
        $sql = "SELECT rp.* FROM rental_periods rp
                INNER JOIN inventory_locks il ON rp.id = il.rental_period_id
                WHERE il.product_id = ? 
                AND (il.variant_id = ? OR (il.variant_id IS NULL AND ? IS NULL))
                AND il.released_at IS NULL
                AND rp.start_datetime < ? 
                AND rp.end_datetime > ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $productId,
            $variantId,
            $variantId,
            $period->getEndDateTimeString(),
            $period->getStartDateTimeString()
        ]);

        $periods = [];
        while ($data = $stmt->fetch()) {
            $periods[] = $this->hydrate($data);
        }

        return $periods;
    }

    /**
     * Validate rental period
     * 
     * @param RentalPeriod $period
     * @return array Array of validation errors
     */
    public function validate(RentalPeriod $period): array
    {
        $errors = [];

        if (!$period->isValid()) {
            $errors[] = 'End date must be after start date';
        }

        if ($period->getStartDateTime() < new DateTime()) {
            $errors[] = 'Start date cannot be in the past';
        }

        if ($period->getDurationValue() < 1) {
            $errors[] = 'Duration must be at least 1 ' . strtolower($period->getDurationUnit());
        }

        return $errors;
    }

    /**
     * Hydrate rental period from database row
     * 
     * @param array $data
     * @return RentalPeriod
     */
    private function hydrate(array $data): RentalPeriod
    {
        return new RentalPeriod(
            $data['id'],
            new DateTime($data['start_datetime']),
            new DateTime($data['end_datetime']),
            (int)$data['duration_value'],
            $data['duration_unit']
        );
    }
}