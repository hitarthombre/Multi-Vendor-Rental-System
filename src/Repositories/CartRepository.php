<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\Cart;

/**
 * Cart Repository
 * 
 * Handles database operations for Cart entities
 */
class CartRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new cart
     * 
     * @param Cart $cart
     * @return bool
     * @throws PDOException
     */
    public function create(Cart $cart): bool
    {
        $sql = "INSERT INTO carts (id, customer_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $cart->getId(),
                $cart->getCustomerId(),
                $cart->getCreatedAt(),
                $cart->getUpdatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create cart: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Find cart by ID
     * 
     * @param string $id
     * @return Cart|null
     */
    public function findById(string $id): ?Cart
    {
        $sql = "SELECT * FROM carts WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $data = $stmt->fetch();
        
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Find cart by customer ID
     * 
     * @param string $customerId
     * @return Cart|null
     */
    public function findByCustomerId(string $customerId): ?Cart
    {
        $sql = "SELECT * FROM carts WHERE customer_id = ? ORDER BY updated_at DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId]);
        
        $data = $stmt->fetch();
        
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Get or create cart for customer
     * 
     * @param string $customerId
     * @return Cart
     */
    public function getOrCreateForCustomer(string $customerId): Cart
    {
        $cart = $this->findByCustomerId($customerId);
        
        if (!$cart) {
            $cart = Cart::create($customerId);
            $this->create($cart);
        }
        
        return $cart;
    }

    /**
     * Update cart
     * 
     * @param Cart $cart
     * @return bool
     */
    public function update(Cart $cart): bool
    {
        $sql = "UPDATE carts SET updated_at = ? WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $cart->getUpdatedAt(),
            $cart->getId()
        ]);
    }

    /**
     * Delete cart
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM carts WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Clear cart (remove all items)
     * 
     * @param string $cartId
     * @return bool
     */
    public function clear(string $cartId): bool
    {
        $sql = "DELETE FROM cart_items WHERE cart_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$cartId]);
    }

    /**
     * Get cart item count
     * 
     * @param string $cartId
     * @return int
     */
    public function getItemCount(string $cartId): int
    {
        $sql = "SELECT COUNT(*) FROM cart_items WHERE cart_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cartId]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get cart total
     * 
     * @param string $cartId
     * @return float
     */
    public function getTotal(string $cartId): float
    {
        $sql = "SELECT SUM(tentative_price * quantity) FROM cart_items WHERE cart_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cartId]);
        
        return (float)$stmt->fetchColumn();
    }

    /**
     * Delete old empty carts
     * 
     * @param int $daysOld
     * @return int Number of deleted carts
     */
    public function deleteOldEmptyCarts(int $daysOld = 30): int
    {
        $sql = "DELETE c FROM carts c
                LEFT JOIN cart_items ci ON c.id = ci.cart_id
                WHERE ci.cart_id IS NULL 
                AND c.updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$daysOld]);
        
        return $stmt->rowCount();
    }

    /**
     * Hydrate cart from database row
     * 
     * @param array $data
     * @return Cart
     */
    private function hydrate(array $data): Cart
    {
        return new Cart(
            $data['id'],
            $data['customer_id'],
            $data['created_at'],
            $data['updated_at']
        );
    }
}