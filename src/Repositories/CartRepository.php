<?php

namespace RentalPlatform\Repositories;

use PDO;
use DateTime;
use RentalPlatform\Models\Cart;
use RentalPlatform\Models\CartItem;

/**
 * CartRepository
 * 
 * Handles database operations for shopping carts
 */
class CartRepository
{
    private PDO $db;

    public function __construct(PDO $db = null)
    {
        $this->db = $db ?? \RentalPlatform\Database\Connection::getInstance();
    }

    /**
     * Create a new cart
     */
    public function create(Cart $cart): void
    {
        $sql = "INSERT INTO carts (id, customer_id, created_at, updated_at)
                VALUES (:id, :customer_id, :created_at, :updated_at)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $cart->getId(),
            'customer_id' => $cart->getCustomerId(),
            'created_at' => $cart->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $cart->getUpdatedAt()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update cart
     */
    public function update(Cart $cart): void
    {
        $sql = "UPDATE carts SET updated_at = :updated_at WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'updated_at' => $cart->getUpdatedAt()->format('Y-m-d H:i:s'),
            'id' => $cart->getId()
        ]);
    }

    /**
     * Find cart by ID
     */
    public function findById(string $id): ?Cart
    {
        $sql = "SELECT * FROM carts WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        $cart = $this->hydrate($row);
        $this->loadCartItems($cart);
        
        return $cart;
    }

    /**
     * Find cart by customer ID
     */
    public function findByCustomerId(string $customerId): ?Cart
    {
        $sql = "SELECT * FROM carts WHERE customer_id = :customer_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['customer_id' => $customerId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        $cart = $this->hydrate($row);
        $this->loadCartItems($cart);
        
        return $cart;
    }

    /**
     * Get or create cart for customer
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
     * Add item to cart
     */
    public function addItem(CartItem $item): void
    {
        $sql = "INSERT INTO cart_items (
            id, cart_id, variant_id, product_id, vendor_id,
            quantity, price_per_unit, start_date, end_date, created_at
        ) VALUES (
            :id, :cart_id, :variant_id, :product_id, :vendor_id,
            :quantity, :price_per_unit, :start_date, :end_date, :created_at
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $item->getId(),
            'cart_id' => $item->getCartId(),
            'variant_id' => $item->getVariantId(),
            'product_id' => $item->getProductId(),
            'vendor_id' => $item->getVendorId(),
            'quantity' => $item->getQuantity(),
            'price_per_unit' => $item->getPricePerUnit(),
            'start_date' => $item->getStartDate()->format('Y-m-d H:i:s'),
            'end_date' => $item->getEndDate()->format('Y-m-d H:i:s'),
            'created_at' => $item->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update cart item
     */
    public function updateItem(CartItem $item): void
    {
        $sql = "UPDATE cart_items SET
            quantity = :quantity,
            price_per_unit = :price_per_unit,
            start_date = :start_date,
            end_date = :end_date
            WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'quantity' => $item->getQuantity(),
            'price_per_unit' => $item->getPricePerUnit(),
            'start_date' => $item->getStartDate()->format('Y-m-d H:i:s'),
            'end_date' => $item->getEndDate()->format('Y-m-d H:i:s'),
            'id' => $item->getId()
        ]);
    }

    /**
     * Remove item from cart
     */
    public function removeItem(string $itemId): void
    {
        $sql = "DELETE FROM cart_items WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $itemId]);
    }

    /**
     * Clear all items from cart
     */
    public function clearCart(string $cartId): void
    {
        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cart_id' => $cartId]);
    }

    /**
     * Delete cart
     */
    public function delete(string $cartId): void
    {
        // Delete items first
        $this->clearCart($cartId);
        
        // Delete cart
        $sql = "DELETE FROM carts WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $cartId]);
    }

    /**
     * Load cart items
     */
    private function loadCartItems(Cart $cart): void
    {
        $sql = "SELECT * FROM cart_items WHERE cart_id = :cart_id ORDER BY created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cart_id' => $cart->getId()]);
        
        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = $this->hydrateItem($row);
        }
        
        $cart->setItems($items);
    }

    /**
     * Hydrate cart from database row
     */
    private function hydrate(array $row): Cart
    {
        return new Cart(
            $row['id'],
            $row['customer_id'],
            [],
            new DateTime($row['created_at']),
            new DateTime($row['updated_at'])
        );
    }

    /**
     * Hydrate cart item from database row
     */
    private function hydrateItem(array $row): CartItem
    {
        return new CartItem(
            $row['id'],
            $row['cart_id'],
            $row['variant_id'],
            $row['product_id'],
            $row['vendor_id'],
            (int)$row['quantity'],
            (float)$row['price_per_unit'],
            new DateTime($row['start_date']),
            new DateTime($row['end_date']),
            new DateTime($row['created_at'])
        );
    }
}
