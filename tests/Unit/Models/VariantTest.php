<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Models\Variant;

/**
 * Variant Model Unit Tests
 */
class VariantTest extends TestCase
{
    public function testCreateVariant(): void
    {
        $attributeValues = [
            'color-attr-id' => 'red-value-id',
            'size-attr-id' => 'large-value-id'
        ];

        $variant = Variant::create(
            'product-123',
            'SKU-TEST-001',
            $attributeValues,
            5
        );

        $this->assertNotEmpty($variant->getId());
        $this->assertEquals('product-123', $variant->getProductId());
        $this->assertEquals('SKU-TEST-001', $variant->getSku());
        $this->assertEquals($attributeValues, $variant->getAttributeValues());
        $this->assertEquals(5, $variant->getQuantity());
    }

    public function testCreateVariantWithDefaultQuantity(): void
    {
        $variant = Variant::create(
            'product-123',
            'SKU-TEST-002',
            []
        );

        $this->assertEquals(1, $variant->getQuantity());
    }

    public function testSetters(): void
    {
        $variant = Variant::create(
            'product-123',
            'SKU-TEST-001',
            []
        );

        $variant->setSku('SKU-UPDATED-001');
        $this->assertEquals('SKU-UPDATED-001', $variant->getSku());

        $newAttributes = ['attr1' => 'value1'];
        $variant->setAttributeValues($newAttributes);
        $this->assertEquals($newAttributes, $variant->getAttributeValues());

        $variant->setQuantity(10);
        $this->assertEquals(10, $variant->getQuantity());
    }

    public function testHasAttributeValue(): void
    {
        $attributeValues = [
            'color-attr-id' => 'red-value-id',
            'size-attr-id' => 'large-value-id'
        ];

        $variant = Variant::create(
            'product-123',
            'SKU-TEST-001',
            $attributeValues
        );

        $this->assertTrue($variant->hasAttributeValue('color-attr-id', 'red-value-id'));
        $this->assertTrue($variant->hasAttributeValue('size-attr-id', 'large-value-id'));
        $this->assertFalse($variant->hasAttributeValue('color-attr-id', 'blue-value-id'));
        $this->assertFalse($variant->hasAttributeValue('nonexistent-attr', 'value'));
    }

    public function testGetAttributeValue(): void
    {
        $attributeValues = [
            'color-attr-id' => 'red-value-id',
            'size-attr-id' => 'large-value-id'
        ];

        $variant = Variant::create(
            'product-123',
            'SKU-TEST-001',
            $attributeValues
        );

        $this->assertEquals('red-value-id', $variant->getAttributeValue('color-attr-id'));
        $this->assertEquals('large-value-id', $variant->getAttributeValue('size-attr-id'));
        $this->assertNull($variant->getAttributeValue('nonexistent-attr'));
    }

    public function testToArray(): void
    {
        $attributeValues = [
            'color-attr-id' => 'red-value-id'
        ];

        $variant = Variant::create(
            'product-123',
            'SKU-TEST-001',
            $attributeValues,
            3
        );

        $array = $variant->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('product_id', $array);
        $this->assertArrayHasKey('sku', $array);
        $this->assertArrayHasKey('attribute_values', $array);
        $this->assertArrayHasKey('quantity', $array);
        $this->assertEquals('product-123', $array['product_id']);
        $this->assertEquals('SKU-TEST-001', $array['sku']);
        $this->assertEquals(3, $array['quantity']);
    }
}
