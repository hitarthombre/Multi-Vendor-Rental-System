<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\Product;

/**
 * Product Repository
 * 
 * Handles database operations for Product entities
 */
class ProductRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new product
     * 
     * @param Product $product
     * @return bool
     * @throws PDOException
     */
    public function create(Product $product): bool
    {
        $sql = "INSERT INTO products (id, vendor_id, name, description, category_id, images, 
                verification_required, status, created_at, updated_at) 
                VALUES (:id, :vendor_id, :name, :description, :category_id, :images, 
                :verification_required, :status, :created_at, :updated_at)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $product->getId(),
                ':vendor_id' => $product->getVendorId(),
                ':name' => $product->getName(),
                ':description' => $product->getDescription(),
                ':category_id' => $product->getCategoryId(),
                ':images' => json_encode($product->getImages()),
                ':verification_required' => $product->isVerificationRequired() ? 1 : 0,
                ':status' => $product->getStatus(),
                ':created_at' => $product->getCreatedAt(),
                ':updated_at' => $product->getUpdatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create product: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Find product by ID
     * 
     * @param string $id
     * @return Product|null
     */
    public function findById(string $id): ?Product
    {
        $sql = "SELECT * FROM products WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find products by vendor ID
     * 
     * @param string $vendorId
     * @param string|null $status Filter by status (optional)
     * @return Product[]
     */
    public function findByVendorId(string $vendorId, ?string $status = null): array
    {
        if ($status !== null) {
            $sql = "SELECT * FROM products WHERE vendor_id = :vendor_id AND status = :status ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':vendor_id' => $vendorId, ':status' => $status]);
        } else {
            $sql = "SELECT * FROM products WHERE vendor_id = :vendor_id ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':vendor_id' => $vendorId]);
        }
        
        $products = [];
        while ($data = $stmt->fetch()) {
            $products[] = $this->hydrate($data);
        }
        
        return $products;
    }

    /**
     * Find products by category ID
     * 
     * @param string $categoryId
     * @param string|null $status Filter by status (optional)
     * @return Product[]
     */
    public function findByCategoryId(string $categoryId, ?string $status = Product::STATUS_ACTIVE): array
    {
        if ($status !== null) {
            $sql = "SELECT * FROM products WHERE category_id = :category_id AND status = :status ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':category_id' => $categoryId, ':status' => $status]);
        } else {
            $sql = "SELECT * FROM products WHERE category_id = :category_id ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':category_id' => $categoryId]);
        }
        
        $products = [];
        while ($data = $stmt->fetch()) {
            $products[] = $this->hydrate($data);
        }
        
        return $products;
    }

    /**
     * Find all active products
     * 
     * @return Product[]
     */
    public function findAllActive(): array
    {
        $sql = "SELECT * FROM products WHERE status = :status ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':status' => Product::STATUS_ACTIVE]);
        
        $products = [];
        while ($data = $stmt->fetch()) {
            $products[] = $this->hydrate($data);
        }
        
        return $products;
    }

    /**
     * Find all products
     * 
     * @return Product[]
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM products ORDER BY created_at DESC";
        
        $stmt = $this->db->query($sql);
        
        $products = [];
        while ($data = $stmt->fetch()) {
            $products[] = $this->hydrate($data);
        }
        
        return $products;
    }

    /**
     * Update product
     * 
     * @param Product $product
     * @return bool
     * @throws PDOException
     */
    public function update(Product $product): bool
    {
        $sql = "UPDATE products 
                SET name = :name, 
                    description = :description, 
                    category_id = :category_id, 
                    images = :images,
                    verification_required = :verification_required,
                    status = :status,
                    updated_at = :updated_at
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $product->getId(),
                ':name' => $product->getName(),
                ':description' => $product->getDescription(),
                ':category_id' => $product->getCategoryId(),
                ':images' => json_encode($product->getImages()),
                ':verification_required' => $product->isVerificationRequired() ? 1 : 0,
                ':status' => $product->getStatus(),
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to update product: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Delete product (soft delete by setting status to Deleted)
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $sql = "UPDATE products SET status = :status, updated_at = :updated_at WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':status' => Product::STATUS_DELETED,
            ':updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Hard delete product (permanently remove from database)
     * 
     * @param string $id
     * @return bool
     */
    public function hardDelete(string $id): bool
    {
        $sql = "DELETE FROM products WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Check if product belongs to vendor
     * 
     * @param string $productId
     * @param string $vendorId
     * @return bool
     */
    public function belongsToVendor(string $productId, string $vendorId): bool
    {
        $sql = "SELECT COUNT(*) FROM products WHERE id = :id AND vendor_id = :vendor_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $productId, ':vendor_id' => $vendorId]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Search products by name or description
     * 
     * @param string $query
     * @param string|null $status Filter by status (optional)
     * @return Product[]
     */
    public function search(string $query, ?string $status = Product::STATUS_ACTIVE): array
    {
        $searchTerm = "%{$query}%";
        
        if ($status !== null) {
            $sql = "SELECT * FROM products 
                    WHERE (name LIKE :query OR description LIKE :query) 
                    AND status = :status 
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':query' => $searchTerm, ':status' => $status]);
        } else {
            $sql = "SELECT * FROM products 
                    WHERE name LIKE :query OR description LIKE :query 
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':query' => $searchTerm]);
        }
        
        $products = [];
        while ($data = $stmt->fetch()) {
            $products[] = $this->hydrate($data);
        }
        
        return $products;
    }

    /**
     * Count products by vendor
     * 
     * @param string $vendorId
     * @param string|null $status Filter by status (optional)
     * @return int
     */
    public function countByVendor(string $vendorId, ?string $status = null): int
    {
        if ($status !== null) {
            $sql = "SELECT COUNT(*) FROM products WHERE vendor_id = :vendor_id AND status = :status";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':vendor_id' => $vendorId, ':status' => $status]);
        } else {
            $sql = "SELECT COUNT(*) FROM products WHERE vendor_id = :vendor_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':vendor_id' => $vendorId]);
        }
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Hydrate product from database row
     * 
     * @param array $data
     * @return Product
     */
    private function hydrate(array $data): Product
    {
        $images = json_decode($data['images'], true) ?? [];
        
        return new Product(
            $data['id'],
            $data['vendor_id'],
            $data['name'],
            $data['description'],
            $data['category_id'],
            $images,
            (bool)$data['verification_required'],
            $data['status'],
            $data['created_at'],
            $data['updated_at']
        );
    }
}
