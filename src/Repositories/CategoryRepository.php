<?php

namespace RentalPlatform\Repositories;

use PDO;
use PDOException;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\Category;

/**
 * Category Repository
 * 
 * Handles database operations for Category entities
 */
class CategoryRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new category
     * 
     * @param Category $category
     * @return bool
     * @throws PDOException
     */
    public function create(Category $category): bool
    {
        $sql = "INSERT INTO categories (id, name, description, parent_id, created_at, updated_at) 
                VALUES (:id, :name, :description, :parent_id, :created_at, :updated_at)";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $category->getId(),
                ':name' => $category->getName(),
                ':description' => $category->getDescription(),
                ':parent_id' => $category->getParentId(),
                ':created_at' => $category->getCreatedAt(),
                ':updated_at' => $category->getUpdatedAt()
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to create category: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Find category by ID
     * 
     * @param string $id
     * @return Category|null
     */
    public function findById(string $id): ?Category
    {
        $sql = "SELECT * FROM categories WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find category by name
     * 
     * @param string $name
     * @return Category|null
     */
    public function findByName(string $name): ?Category
    {
        $sql = "SELECT * FROM categories WHERE name = :name LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':name' => $name]);
        
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return $this->hydrate($data);
    }

    /**
     * Find all root categories (no parent)
     * 
     * @return Category[]
     */
    public function findRootCategories(): array
    {
        $sql = "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC";
        
        $stmt = $this->db->query($sql);
        
        $categories = [];
        while ($data = $stmt->fetch()) {
            $categories[] = $this->hydrate($data);
        }
        
        return $categories;
    }

    /**
     * Find subcategories by parent ID
     * 
     * @param string $parentId
     * @return Category[]
     */
    public function findByParentId(string $parentId): array
    {
        $sql = "SELECT * FROM categories WHERE parent_id = :parent_id ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':parent_id' => $parentId]);
        
        $categories = [];
        while ($data = $stmt->fetch()) {
            $categories[] = $this->hydrate($data);
        }
        
        return $categories;
    }

    /**
     * Find all categories
     * 
     * @return Category[]
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM categories ORDER BY name ASC";
        
        $stmt = $this->db->query($sql);
        
        $categories = [];
        while ($data = $stmt->fetch()) {
            $categories[] = $this->hydrate($data);
        }
        
        return $categories;
    }

    /**
     * Update category
     * 
     * @param Category $category
     * @return bool
     * @throws PDOException
     */
    public function update(Category $category): bool
    {
        $sql = "UPDATE categories 
                SET name = :name, 
                    description = :description, 
                    parent_id = :parent_id,
                    updated_at = :updated_at
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $category->getId(),
                ':name' => $category->getName(),
                ':description' => $category->getDescription(),
                ':parent_id' => $category->getParentId(),
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to update category: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Delete category
     * 
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM categories WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Check if category has subcategories
     * 
     * @param string $id
     * @return bool
     */
    public function hasSubcategories(string $id): bool
    {
        $sql = "SELECT COUNT(*) FROM categories WHERE parent_id = :parent_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':parent_id' => $id]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if category has products
     * 
     * @param string $id
     * @return bool
     */
    public function hasProducts(string $id): bool
    {
        $sql = "SELECT COUNT(*) FROM products WHERE category_id = :category_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':category_id' => $id]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Hydrate category from database row
     * 
     * @param array $data
     * @return Category
     */
    private function hydrate(array $data): Category
    {
        return new Category(
            $data['id'],
            $data['name'],
            $data['description'],
            $data['parent_id'],
            $data['created_at'],
            $data['updated_at']
        );
    }
}
