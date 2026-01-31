<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Models\Category;

/**
 * Category Model Unit Tests
 */
class CategoryTest extends TestCase
{
    public function testCreateCategory(): void
    {
        $category = Category::create(
            'Electronics',
            'Electronic devices and gadgets',
            null
        );

        $this->assertNotEmpty($category->getId());
        $this->assertEquals('Electronics', $category->getName());
        $this->assertEquals('Electronic devices and gadgets', $category->getDescription());
        $this->assertNull($category->getParentId());
        $this->assertTrue($category->isRootCategory());
    }

    public function testCreateSubcategory(): void
    {
        $category = Category::create(
            'Laptops',
            'Laptop computers',
            'parent-category-id'
        );

        $this->assertNotEmpty($category->getId());
        $this->assertEquals('Laptops', $category->getName());
        $this->assertEquals('parent-category-id', $category->getParentId());
        $this->assertFalse($category->isRootCategory());
    }

    public function testSetters(): void
    {
        $category = Category::create('Test Category');

        $category->setName('Updated Category');
        $this->assertEquals('Updated Category', $category->getName());

        $category->setDescription('Updated Description');
        $this->assertEquals('Updated Description', $category->getDescription());

        $category->setParentId('parent-id');
        $this->assertEquals('parent-id', $category->getParentId());
        $this->assertFalse($category->isRootCategory());

        $category->setParentId(null);
        $this->assertNull($category->getParentId());
        $this->assertTrue($category->isRootCategory());
    }

    public function testToArray(): void
    {
        $category = Category::create(
            'Electronics',
            'Electronic devices',
            'parent-id'
        );

        $array = $category->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('parent_id', $array);
        $this->assertEquals('Electronics', $array['name']);
        $this->assertEquals('parent-id', $array['parent_id']);
    }
}
