<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\Vendor;

/**
 * Vendor Repository
 * 
 * Handles database operations for Vendor entities
 */
class VendorRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new vendor
     * 
     * @param Vendor $vendor
     * @return bool
     * @throws PDOException
     */
    public function create(Vendor $vendor): bool
    {
        $sql = "INSERT INTO vendors (
                    id, user_id, business_name, legal_name, tax_id, 
                    logo, brand_color, contact_email, contact_phone, 
                    status, created_at, updated_at
                ) VALUES (
                    :id, :user_id, :business_name, :legal_name, :tax_id,
                    :logo, :brand_color, :contact_email, :contact_phone,
                    :status, :created_at, :updated_at
                )";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $vendor->getId(),
                ':user_id' => $vendor->getUserId(),
                ':business_name' => $vendor->getBusinessName(),
                ':legal_name' => $vendor->getLegalName(),
                ':tax_id' => $vendor->getTaxId(),
                ':logo' => $vendor->getLogo(),
                ':brand_color' => $vendor->getBrandColor(),
                ':contact_email' => $vendor->getContactEmail(),
                ':contact_phone' => $vendor->getContactPhone(),
                ':status' => $vendor->getStatus(),
                ':created_at' => $vendor->getCreatedAt(),
                ':updated_at' => $vendor->getUpdatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create vendor: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Find vendor by ID
     * 
     * @param string $id
     * @return Vendor|null
     */
    public function findById(string $id): ?Vendor
    {
        $sql = "SELECT * FROM vendors WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find vendor by user ID
     * 
     * @param string $userId
     * @return Vendor|null
     */
    public function findByUserId(string $userId): ?Vendor
    {
        $sql = "SELECT * FROM vendors WHERE user_id = :user_id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Update vendor
     * 
     * @param Vendor $vendor
     * @return bool
     * @throws PDOException
     */
    public function update(Vendor $vendor): bool
    {
        $sql = "UPDATE vendors 
                SET business_name = :business_name,
                    legal_name = :legal_name,
                    tax_id = :tax_id,
                    logo = :logo,
                    brand_color = :brand_color,
                    contact_email = :contact_email,
                    contact_phone = :contact_phone,
                    status = :status,
                    updated_at = :updated_at
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $vendor->getId(),
                ':business_name' => $vendor->getBusinessName(),
                ':legal_name' => $vendor->getLegalName(),
                ':tax_id' => $vendor->getTaxId(),
                ':logo' => $vendor->getLogo(),
                ':brand_color' => $vendor->getBrandColor(),
                ':contact_email' => $vendor->getContactEmail(),
                ':contact_phone' => $vendor->getContactPhone(),
                ':status' => $vendor->getStatus(),
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to update vendor: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Delete vendor
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM vendors WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get all vendors by status
     * 
     * @param string $status
     * @return Vendor[]
     */
    public function findByStatus(string $status): array
    {
        $sql = "SELECT * FROM vendors WHERE status = :status ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':status' => $status]);
        
        $vendors = [];
        while ($data = $stmt->fetch()) {
            $vendors[] = $this->hydrate($data);
        }
        
        return $vendors;
    }

    /**
     * Get all vendors
     * 
     * @return Vendor[]
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM vendors ORDER BY created_at DESC";
        
        $stmt = $this->db->query($sql);
        
        $vendors = [];
        while ($data = $stmt->fetch()) {
            $vendors[] = $this->hydrate($data);
        }
        
        return $vendors;
    }

    /**
     * Check if user already has a vendor profile
     * 
     * @param string $userId
     * @return bool
     */
    public function userHasVendorProfile(string $userId): bool
    {
        $sql = "SELECT COUNT(*) FROM vendors WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Hydrate vendor from database row
     * 
     * @param array $data
     * @return Vendor
     */
    private function hydrate(array $data): Vendor
    {
        return new Vendor(
            $data['id'],
            $data['user_id'],
            $data['business_name'],
            $data['legal_name'],
            $data['tax_id'],
            $data['logo'],
            $data['brand_color'],
            $data['contact_email'],
            $data['contact_phone'],
            $data['status'],
            $data['created_at'],
            $data['updated_at']
        );
    }
}
