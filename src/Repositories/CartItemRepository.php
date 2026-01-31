<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\CartItem;

/**
 * CartItem Repository
 * 
 * Handles database operations for CartItem entities
 */
class CartItemRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new cart item
     * 
     * @param CartItem $cartItem
     * @return bool
     * @throws PDOException
     */
    public function create(CartItem $cartItem): bool
    {
        $sql = "INSERT INTO cart_items (id, cart_id, product_id, variant_id, rental_period_id, quantity, tentative_price, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $cartItem->getId(),
                $cartItem->getCartId(),
                $cartItem->getProductId(),
                $cartItem->getVariantId(),
                $cartItem->getRentalPeriodId(),
                $cartItem->getQuantity(),
                $cartItem->getTentativePrice(),
                $cartItem->getCreatedAt(),
                $cartItem->getUpdatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create cart item: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Find cart item by ID
     * 
     * @param string $id
     * @return CartItem|null
     */
    public function findById(string $id): ?CartItem
    {
        $sql = "SELECT * FROM cart_items WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $data = $stmt->fetch();
        
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Find all items in a cart
     * 
     * @param string $cartId
     * @return CartItem[]
     */
    public function findByCartId(string $cartId): array
    {
        $sql = "SELECT * FROM cart_items WHERE cart_id = ? ORDER BY created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cartId]);
        
        $items = [];
        while ($data = $stmt->fetch()) {
            $items[] = $this->hydrate($data);
        }
        
        return $items;
    }

    /**
     * Find cart items with product details
     * 
     * @param string $cartId
     * @return array
     */
    public function findWithProductDetails(string $cartId): array
    {
        $sql = "SELECT ci.*, p.name, p.description, p.images, p.verification_required,
                       v.business_name as vendor_name, v.business_name,
                       var.sku, var.attribute_values,
                       rp.start_datetime, rp.end_datetime, rp.duration_value, rp.duration_unit
                FROM cart_items ci
                INNER JOIN products p ON ci.product_id = p.id
                INNER JOIN vendors v ON p.vendor_id = v.id
                LEFT JOIN variants var ON ci.variant_id = var.id
                INNER JOIN rental_periods rp ON ci.rental_period_id = rp.id
                WHERE ci.cart_id = ?
                ORDER BY v.business_name, ci.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cartId]);
        
        $items = [];
        while ($data = $stmt->fetch()) {
            $cartItem = $this->hydrate($data);
            $items[] = [
                'cart_item' => $cartItem->toArray(),
                'product' => [
                    'id' => $data['product_id'],
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'images' => json_decode($data['images'], true) ?? [],
                    'verification_required' => (bool)$data['verification_required']
                ],
                'vendor' => [
                    'name' => $data['vendor_name'],
                    'business_name' => $data['business_name']
                ],
                'variant' => $data['sku'] ? [
                    'sku' => $data['sku'],
                    'attribute_values' => json_decode($data['attribute_values'], true) ?? []
                ] : null,
                'rental_period' => [
                    'start_datetime' => $data['start_datetime'],
                    'end_datetime' => $data['end_datetime'],
                    'duration_value' => (int)$data['duration_value'],
                    'duration_unit' => $data['duration_unit']
                ]
            ];
        }
        
        return $items;
    }

    /**
     * Find existing cart item for same product/variant/period
     * 
     * @param string $cartId
     * @param string $productId
     * @param string|null $variantId
     * @param string $rentalPeriodId
     * @return CartItem|null
     */
    public function findExisting(string $cartId, string $productId, ?string $variantId, string $rentalPeriodId): ?CartItem
    {
        $sql = "SELECT * FROM cart_items 
                WHERE cart_id = ? AND product_id = ? 
                AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))
                AND rental_period_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cartId, $productId, $variantId, $variantId, $rentalPeriodId]);
        
        $data = $stmt->fetch();
        
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Update cart item
     * 
     * @param CartItem $cartItem
     * @return bool
     */
    public function update(CartItem $cartItem): bool
    {
        $sql = "UPDATE cart_items 
                SET quantity = ?, tentative_price = ?, rental_period_id = ?, updated_at = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $cartItem->getQuantity(),
            $cartItem->getTentativePrice(),
            $cartItem->getRentalPeriodId(),
            $cartItem->getUpdatedAt(),
            $cartItem->getId()
        ]);
    }

    /**
     * Delete cart item
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM cart_items WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Delete all items from cart
     * 
     * @param string $cartId
     * @return bool
     */
    public function deleteByCartId(string $cartId): bool
    {
        $sql = "DELETE FROM cart_items WHERE cart_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$cartId]);
    }

    /**
     * Group cart items by vendor
     * 
     * @param string $cartId
     * @return array
     */
    public function groupByVendor(string $cartId): array
    {
        $sql = "SELECT v.id as vendor_id, v.business_name, 
                       COUNT(ci.id) as item_count,
                       SUM(ci.tentative_price * ci.quantity) as vendor_total
                FROM cart_items ci
                INNER JOIN products p ON ci.product_id = p.id
                INNER JOIN vendors v ON p.vendor_id = v.id
                WHERE ci.cart_id = ?
                GROUP BY v.id, v.business_name
                ORDER BY v.business_name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cartId]);
        
        $vendors = [];
        while ($data = $stmt->fetch()) {
            $vendors[] = [
                'vendor_id' => $data['vendor_id'],
                'business_name' => $data['business_name'],
                'item_count' => (int)$data['item_count'],
                'total' => (float)$data['vendor_total']
            ];
        }
        
        return $vendors;
    }

    /**
     * Get cart summary
     * 
     * @param string $cartId
     * @return array
     */
    public function getCartSummary(string $cartId): array
    {
        $sql = "SELECT COUNT(*) as total_items,
                       COUNT(DISTINCT p.vendor_id) as vendor_count,
                       SUM(ci.tentative_price * ci.quantity) as total_amount
                FROM cart_items ci
                INNER JOIN products p ON ci.product_id = p.id
                WHERE ci.cart_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cartId]);
        
        $data = $stmt->fetch();
        
        return [
            'total_items' => (int)$data['total_items'],
            'vendor_count' => (int)$data['vendor_count'],
            'total_amount' => (float)$data['total_amount']
        ];
    }

    /**
     * Hydrate cart item from database row
     * 
     * @param array $data
     * @return CartItem
     */
    private function hydrate(array $data): CartItem
    {
        return new CartItem(
            $data['id'],
            $data['cart_id'],
            $data['product_id'],
            $data['variant_id'],
            $data['rental_period_id'],
            (int)$data['quantity'],
            (float)$data['tentative_price'],
            $data['created_at'],
            $data['updated_at']
        );
    }
}