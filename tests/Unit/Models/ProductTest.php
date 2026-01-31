<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Models\Product;

/**
 * Product Model Unit Tests
 */
class ProductTest extends TestCase
{
    public function testCreateProduct(): void
    {
        $product = Product::create(
            'vendor-123',
            'Test Product',
            'Test Description',
            'category-456',
            ['image1.jpg', 'image2.jpg'],
            true,
            Product::STATUS_ACTIVE
        );

        $this->assertNotEmpty($product->getId());
        $this->assertEquals('vendor-123', $product->getVendorId());
        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals('Test Description', $product->getDescription());
        $this->assertEquals('category-456', $product->getCategoryId());
        $this->assertEquals(['image1.jpg', 'image2.jpg'], $product->getImages());
        $this->assertTrue($product->isVerificationRequired());
        $this->assertEquals(Product::STATUS_ACTIVE, $product->getStatus());
        $this->assertTrue($product->isActive());
    }

    public function testCreateProductWithDefaults(): void
    {
        $product = Product::create(
            'vendor-123',
            'Test Product',
            'Test Description'
        );

        $this->assertNotEmpty($product->getId());
        $this->assertNull($product->getCategoryId());
        $this->assertEquals([], $product->getImages());
        $this->assertFalse($product->isVerificationRequired());
        $this->assertEquals(Product::STATUS_ACTIVE, $product->getStatus());
    }

    public function testSetters(): void
    {
        $product = Product::create(
            'vendor-123',
            'Test Product',
            'Test Description'
        );

        $product->setName('Updated Product');
        $this->assertEquals('Updated Product', $product->getName());

        $product->setDescription('Updated Description');
        $this->assertEquals('Updated Description', $product->getDescription());

        $product->setCategoryId('new-category');
        $this->assertEquals('new-category', $product->getCategoryId());

        $product->setImages(['new-image.jpg']);
        $this->assertEquals(['new-image.jpg'], $product->getImages());

        $product->setVerificationRequired(true);
        $this->assertTrue($product->isVerificationRequired());

        $product->setStatus(Product::STATUS_INACTIVE);
        $this->assertEquals(Product::STATUS_INACTIVE, $product->getStatus());
        $this->assertFalse($product->isActive());
    }

    public function testInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $product = Product::create(
            'vendor-123',
            'Test Product',
            'Test Description'
        );
        
        $product->setStatus('InvalidStatus');
    }

    public function testBelongsToVendor(): void
    {
        $product = Product::create(
            'vendor-123',
            'Test Product',
            'Test Description'
        );

        $this->assertTrue($product->belongsToVendor('vendor-123'));
        $this->assertFalse($product->belongsToVendor('vendor-456'));
    }

    public function testIsValidStatus(): void
    {
        $this->assertTrue(Product::isValidStatus(Product::STATUS_ACTIVE));
        $this->assertTrue(Product::isValidStatus(Product::STATUS_INACTIVE));
        $this->assertTrue(Product::isValidStatus(Product::STATUS_DELETED));
        $this->assertFalse(Product::isValidStatus('InvalidStatus'));
    }

    public function testToArray(): void
    {
        $product = Product::create(
            'vendor-123',
            'Test Product',
            'Test Description',
            'category-456',
            ['image1.jpg'],
            true,
            Product::STATUS_ACTIVE
        );

        $array = $product->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('vendor_id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('category_id', $array);
        $this->assertArrayHasKey('images', $array);
        $this->assertArrayHasKey('verification_required', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertEquals('vendor-123', $array['vendor_id']);
        $this->assertEquals('Test Product', $array['name']);
    }
}
