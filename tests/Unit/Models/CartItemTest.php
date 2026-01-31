<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Models\CartItem;
use DateTime;

class CartItemTest extends TestCase
{
    public function testCreateCartItem()
    {
        $cartId = 'cart-123';
        $variantId = 'variant-456';
        $productId = 'product-789';
        $vendorId = 'vendor-111';
        $quantity = 2;
        $pricePerUnit = 100.00;
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-05');
        
        $item = CartItem::create(
            $cartId,
            $variantId,
            $productId,
            $vendorId,
            $quantity,
            $pricePerUnit,
            $startDate,
            $endDate
        );
        
        $this->assertNotEmpty($item->getId());
        $this->assertEquals($cartId, $item->getCartId());
        $this->assertEquals($variantId, $item->getVariantId());
        $this->assertEquals($productId, $item->getProductId());
        $this->assertEquals($vendorId, $item->getVendorId());
        $this->assertEquals($quantity, $item->getQuantity());
        $this->assertEquals($pricePerUnit, $item->getPricePerUnit());
        $this->assertEquals($startDate, $item->getStartDate());
        $this->assertEquals($endDate, $item->getEndDate());
    }
    
    public function testGetSubtotal()
    {
        $item = CartItem::create(
            'cart-123',
            'variant-456',
            'product-789',
            'vendor-111',
            3,
            150.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        // Subtotal: 3 * 150 = 450
        $this->assertEquals(450.00, $item->getSubtotal());
    }
    
    public function testSetQuantity()
    {
        $item = CartItem::create(
            'cart-123',
            'variant-456',
            'product-789',
            'vendor-111',
            2,
            100.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $this->assertEquals(2, $item->getQuantity());
        $this->assertEquals(200.00, $item->getSubtotal());
        
        $item->setQuantity(5);
        
        $this->assertEquals(5, $item->getQuantity());
        $this->assertEquals(500.00, $item->getSubtotal());
    }
}
