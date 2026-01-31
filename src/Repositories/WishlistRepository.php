<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\Wishlist;

/**
 * Wishlist Repository
 * 
 * Handles database operations for Wishlist entities
 */
class WishlistRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Add product to wishlist
     * 
     * @param Wishlist $wishlist
     * @return bool
     * @throws PDOException
     */
    public function create(Wishlist $wishlist): bool
    {
        // Check if item already exists
        if ($this->exists($wishlist->getCustomerId(), $wishlist->getProductId())) {
            return false; // Already in wishlist
        }

        $sql = "INSERT INTO wishlists (id, customer_id, product_id, created_at) 
                VALUES (?, ?, ?, ?)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $wishlist->getId(),
                $wishlist->getCustomerId(),
                $wishlist->getProductId(),
                $wishlist->getCreatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to add to wishlist: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Remove product from wishlist
     * 
     * @param string $customerId
     * @param string $productId
     * @return bool
     */
    public function remove(string $customerId, string $productId): bool
    {
        $sql = "DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$customerId, $productId]);
    }

    /**
     * Check if product is in customer's wishlist
     * 
     * @param string $customerId
     * @param string $productId
     * @return bool
     */
    public function exists(string $customerId, string $productId): bool
    {
        $sql = "SELECT COUNT(*) FROM wishlists WHERE customer_id = ? AND product_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId, $productId]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get customer's wishlist items
     * 
     * @param string $customerId
     * @return Wishlist[]
     */
    public function findByCustomerId(string $customerId): array
    {
        $sql = "SELECT * FROM wishlists WHERE customer_id = ? ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId]);
        
        $wishlists = [];
        while ($data = $stmt->fetch()) {
            $wishlists[] = $this->hydrate($data);
        }
        
        return $wishlists;
    }

    /**
     * Get customer's wishlist with product details
     * 
     * @param string $customerId
     * @return array
     */
    public function findWithProductDetails(string $customerId): array
    {
        $sql = "SELECT w.*, p.name, p.description, p.images, p.status, p.verification_required
                FROM wishlists w
                INNER JOIN products p ON w.product_id = p.id
                WHERE w.customer_id = ? AND p.status = 'Active'
                ORDER BY w.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId]);
        
        $items = [];
        while ($data = $stmt->fetch()) {
            $items[] = [
                'wishlist_id' => $data['id'],
                'product_id' => $data['product_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'images' => json_decode($data['images'], true) ?? [],
                'status' => $data['status'],
                'verification_required' => (bool)$data['verification_required'],
                'added_at' => $data['created_at']
            ];
        }
        
        return $items;
    }

    /**
     * Count wishlist items for customer
     * 
     * @param string $customerId
     * @return int
     */
    public function countByCustomer(string $customerId): int
    {
        $sql = "SELECT COUNT(*) FROM wishlists w
                INNER JOIN products p ON w.product_id = p.id
                WHERE w.customer_id = ? AND p.status = 'Active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Clear customer's wishlist
     * 
     * @param string $customerId
     * @return bool
     */
    public function clearByCustomer(string $customerId): bool
    {
        $sql = "DELETE FROM wishlists WHERE customer_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$customerId]);
    }

    /**
     * Hydrate wishlist from database row
     * 
     * @param array $data
     * @return Wishlist
     */
    private function hydrate(array $data): Wishlist
    {
        return new Wishlist(
            $data['id'],
            $data['customer_id'],
            $data['product_id'],
            $data['created_at']
        );
    }
}