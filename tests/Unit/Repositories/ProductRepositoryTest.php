<?php

namespace Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use PDO;
use RentalPlatform\Database\Connection;
use RentalPlatform\Models\Product;
use RentalPlatform\Models\User;
use RentalPlatform\Models\Category;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\UserRepository;
use RentalPlatform\Repositories\CategoryRepository;

/**
 * Product Repository Integration Tests
 */
class ProductRepositoryTest extends TestCase
{
    private PDO $db;
    private ProductRepository $repository;
    private UserRepository $userRepository;
    private CategoryRepository $categoryRepository;
    private array $testVendorIds = [];
    private array $testCategoryIds = [];

    protected function setUp(): void
    {
        $this->db = Connection::getInstance();
        $this->repository = new ProductRepository();
        $this->userRepository = new UserRepository();
        $this->categoryRepository = new CategoryRepository();
        
        $this->createTestVendors();
        $this->createTestCategories();
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        $this->deleteTestCategories();
        $this->deleteTestVendors();
    }

    private function createTestVendors(): void
    {
        $timestamp = time();
        
        $vendor1 = User::create("vendor1_{$timestamp}", "vendor1_{$timestamp}@example.com", 'password123', User::ROLE_VENDOR);
        $vendor2 = User::create("vendor2_{$timestamp}", "vendor2_{$timestamp}@example.com", 'password123', User::ROLE_VENDOR);
        
        $this->userRepository->create($vendor1);
        $this->userRepository->create($vendor2);
        
        $this->testVendorIds = [
            'vendor1' => $vendor1->getId(),
            'vendor2' => $vendor2->getId()
        ];
    }

    private function deleteTestVendors(): void
    {
        foreach ($this->testVendorIds as $vendorId) {
            try {
                $this->userRepository->delete($vendorId);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
    }

    private function createTestCategories(): void
    {
        $category1 = Category::create('Electronics', 'Electronic devices');
        $category2 = Category::create('Furniture', 'Home furniture');
        
        $this->categoryRepository->create($category1);
        $this->categoryRepository->create($category2);
        
        $this->testCategoryIds = [
            'electronics' => $category1->getId(),
            'furniture' => $category2->getId()
        ];
    }

    private function deleteTestCategories(): void
    {
        foreach ($this->testCategoryIds as $categoryId) {
            try {
                $this->categoryRepository->delete($categoryId);
            } catch (\Exception $e) {
                // Ignore errors during cleanup
            }
        }
    }

    private function cleanupTestData(): void
    {
        // Delete test products
        $stmt = $this->db->prepare("DELETE FROM products WHERE name LIKE 'Test Product%'");
        $stmt->execute();
    }

    public function testCreateProduct(): void
    {
        $product = Product::create(
            $this->testVendorIds['vendor1'],
            'Test Product 1',
            'Test Description',
            $this->testCategoryIds['electronics'],
            ['image1.jpg', 'image2.jpg'],
            true,
            Product::STATUS_ACTIVE
        );

        $result = $this->repository->create($product);
        $this->assertTrue($result);

        // Verify it was saved
        $found = $this->repository->findById($product->getId());
        $this->assertNotNull($found);
        $this->assertEquals($product->getId(), $found->getId());
        $this->assertEquals($product->getName(), $found->getName());
        $this->assertEquals($product->getVendorId(), $found->getVendorId());
    }

    public function testFindById(): void
    {
        $product = Product::create(
            $this->testVendorIds['vendor1'],
            'Test Product 2',
            'Test Description'
        );

        $this->repository->create($product);
        $found = $this->repository->findById($product->getId());

        $this->assertNotNull($found);
        $this->assertEquals($product->getId(), $found->getId());
        $this->assertEquals($product->getName(), $found->getName());
    }

    public function testFindByIdNotFound(): void
    {
        $found = $this->repository->findById('non-existent-id');
        $this->assertNull($found);
    }

    public function testFindByVendorId(): void
    {
        $vendorId = $this->testVendorIds['vendor1'];
        
        // Create multiple products for vendor1
        for ($i = 0; $i < 3; $i++) {
            $product = Product::create(
                $vendorId,
                "Test Product Vendor1 {$i}",
                'Description'
            );
            $this->repository->create($product);
        }

        // Create one for vendor2
        $product = Product::create(
            $this->testVendorIds['vendor2'],
            'Test Product Vendor2',
            'Description'
        );
        $this->repository->create($product);

        $products = $this->repository->findByVendorId($vendorId);
        $this->assertCount(3, $products);
        
        foreach ($products as $product) {
            $this->assertEquals($vendorId, $product->getVendorId());
        }
    }

    public function testFindByVendorIdWithStatus(): void
    {
        $vendorId = $this->testVendorIds['vendor1'];
        
        // Create active products
        for ($i = 0; $i < 2; $i++) {
            $product = Product::create(
                $vendorId,
                "Test Product Active {$i}",
                'Description',
                null,
                [],
                false,
                Product::STATUS_ACTIVE
            );
            $this->repository->create($product);
        }

        // Create inactive product
        $product = Product::create(
            $vendorId,
            'Test Product Inactive',
            'Description',
            null,
            [],
            false,
            Product::STATUS_INACTIVE
        );
        $this->repository->create($product);

        $activeProducts = $this->repository->findByVendorId($vendorId, Product::STATUS_ACTIVE);
        $this->assertCount(2, $activeProducts);
        
        foreach ($activeProducts as $product) {
            $this->assertEquals(Product::STATUS_ACTIVE, $product->getStatus());
        }
    }

    public function testFindByCategoryId(): void
    {
        $categoryId = $this->testCategoryIds['electronics'];
        
        // Create products in electronics category
        for ($i = 0; $i < 2; $i++) {
            $product = Product::create(
                $this->testVendorIds['vendor1'],
                "Test Product Electronics {$i}",
                'Description',
                $categoryId
            );
            $this->repository->create($product);
        }

        // Create product in furniture category
        $product = Product::create(
            $this->testVendorIds['vendor1'],
            'Test Product Furniture',
            'Description',
            $this->testCategoryIds['furniture']
        );
        $this->repository->create($product);

        $products = $this->repository->findByCategoryId($categoryId);
        $this->assertCount(2, $products);
        
        foreach ($products as $product) {
            $this->assertEquals($categoryId, $product->getCategoryId());
        }
    }

    public function testUpdateProduct(): void
    {
        $product = Product::create(
            $this->testVendorIds['vendor1'],
            'Test Product Original',
            'Original Description'
        );

        $this->repository->create($product);

        // Update product
        $product->setName('Test Product Updated');
        $product->setDescription('Updated Description');
        $product->setStatus(Product::STATUS_INACTIVE);

        $result = $this->repository->update($product);
        $this->assertTrue($result);

        // Verify update
        $found = $this->repository->findById($product->getId());
        $this->assertEquals('Test Product Updated', $found->getName());
        $this->assertEquals('Updated Description', $found->getDescription());
        $this->assertEquals(Product::STATUS_INACTIVE, $found->getStatus());
    }

    public function testDeleteProduct(): void
    {
        $product = Product::create(
            $this->testVendorIds['vendor1'],
            'Test Product To Delete',
            'Description'
        );

        $this->repository->create($product);

        // Soft delete
        $result = $this->repository->delete($product->getId());
        $this->assertTrue($result);

        // Verify it's marked as deleted
        $found = $this->repository->findById($product->getId());
        $this->assertNotNull($found);
        $this->assertEquals(Product::STATUS_DELETED, $found->getStatus());
    }

    public function testBelongsToVendor(): void
    {
        $product = Product::create(
            $this->testVendorIds['vendor1'],
            'Test Product Ownership',
            'Description'
        );

        $this->repository->create($product);

        $this->assertTrue($this->repository->belongsToVendor($product->getId(), $this->testVendorIds['vendor1']));
        $this->assertFalse($this->repository->belongsToVendor($product->getId(), $this->testVendorIds['vendor2']));
    }

    public function testSearchProducts(): void
    {
        // Create products with searchable names
        $product1 = Product::create(
            $this->testVendorIds['vendor1'],
            'Test Product Laptop Computer',
            'High-performance laptop'
        );
        $this->repository->create($product1);

        $product2 = Product::create(
            $this->testVendorIds['vendor1'],
            'Test Product Desktop',
            'Desktop computer for office'
        );
        $this->repository->create($product2);

        $product3 = Product::create(
            $this->testVendorIds['vendor1'],
            'Test Product Chair',
            'Office chair'
        );
        $this->repository->create($product3);

        // Search for "computer"
        $results = $this->repository->search('computer');
        $this->assertGreaterThanOrEqual(2, count($results));

        // Search for "laptop"
        $results = $this->repository->search('laptop');
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testCountByVendor(): void
    {
        $vendorId = $this->testVendorIds['vendor1'];
        
        // Create products
        for ($i = 0; $i < 3; $i++) {
            $product = Product::create(
                $vendorId,
                "Test Product Count {$i}",
                'Description'
            );
            $this->repository->create($product);
        }

        $count = $this->repository->countByVendor($vendorId);
        $this->assertEquals(3, $count);

        $count = $this->repository->countByVendor($this->testVendorIds['vendor2']);
        $this->assertEquals(0, $count);
    }

    public function testFindAllActive(): void
    {
        // Create active products
        for ($i = 0; $i < 2; $i++) {
            $product = Product::create(
                $this->testVendorIds['vendor1'],
                "Test Product Active All {$i}",
                'Description',
                null,
                [],
                false,
                Product::STATUS_ACTIVE
            );
            $this->repository->create($product);
        }

        // Create inactive product
        $product = Product::create(
            $this->testVendorIds['vendor1'],
            'Test Product Inactive All',
            'Description',
            null,
            [],
            false,
            Product::STATUS_INACTIVE
        );
        $this->repository->create($product);

        $activeProducts = $this->repository->findAllActive();
        
        // Should have at least our 2 test products
        $this->assertGreaterThanOrEqual(2, count($activeProducts));
        
        foreach ($activeProducts as $product) {
            $this->assertEquals(Product::STATUS_ACTIVE, $product->getStatus());
        }
    }
}
