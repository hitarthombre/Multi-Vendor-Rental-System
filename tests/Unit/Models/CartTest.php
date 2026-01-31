<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Models\Cart;
use RentalPlatform\Models\CartItem;
use DateTime;

class CartTest extends TestCase
{
    public function testCreateCart()
    {
        $customerId = 'customer-123';
        $cart = Cart::create($customerId);
        
        $this->assertNotEmpty($cart->getId());
        $this->assertEquals($customerId, $cart->getCustomerId());
        $this->assertEmpty($cart->getItems());
        $this->assertEquals(0, $cart->getItemCount());
        $this->assertInstanceOf(DateTime::class, $cart->getCreatedAt());
    }
    
    public function testAddItemToCart()
    {
        $cart = Cart::create('customer-123');
        $item = new CartItem(
            'item-1',
            $cart->getId(),
            'variant-1',
            'product-1',
            'vendor-1',
            2,
            100.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $cart->addItem($item);
        
        $this->assertEquals(1, $cart->getItemCount());
        $this->assertCount(1, $cart->getItems());
    }
    
    public function testRemoveItemFromCart()
    {
        $cart = Cart::create('customer-123');
        $item = new CartItem(
            'item-1',
            $cart->getId(),
            'variant-1',
            'product-1',
            'vendor-1',
            2,
            100.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $cart->addItem($item);
        $this->assertEquals(1, $cart->getItemCount());
        
        $cart->removeItem('item-1');
        $this->assertEquals(0, $cart->getItemCount());
    }
    
    public function testUpdateItemQuantity()
    {
        $cart = Cart::create('customer-123');
        $item = new CartItem(
            'item-1',
            $cart->getId(),
            'variant-1',
            'product-1',
            'vendor-1',
            2,
            100.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $cart->addItem($item);
        $cart->updateItemQuantity('item-1', 5);
        
        $updatedItem = $cart->getItem('item-1');
        $this->assertEquals(5, $updatedItem->getQuantity());
    }
    
    public function testGetTotalQuantity()
    {
        $cart = Cart::create('customer-123');
        
        $item1 = new CartItem(
            'item-1',
            $cart->getId(),
            'variant-1',
            'product-1',
            'vendor-1',
            2,
            100.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $item2 = new CartItem(
            'item-2',
            $cart->getId(),
            'variant-2',
            'product-2',
            'vendor-1',
            3,
            150.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $cart->addItem($item1);
        $cart->addItem($item2);
        
        $this->assertEquals(5, $cart->getTotalQuantity());
    }
    
    public function testGetTotalPrice()
    {
        $cart = Cart::create('customer-123');
        
        $item1 = new CartItem(
            'item-1',
            $cart->getId(),
            'variant-1',
            'product-1',
            'vendor-1',
            2,
            100.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $item2 = new CartItem(
            'item-2',
            $cart->getId(),
            'variant-2',
            'product-2',
            'vendor-1',
            3,
            150.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $cart->addItem($item1);
        $cart->addItem($item2);
        
        // Total: (2 * 100) + (3 * 150) = 200 + 450 = 650
        $this->assertEquals(650.00, $cart->getTotalPrice());
    }
    
    public function testGroupByVendor()
    {
        $cart = Cart::create('customer-123');
        
        $item1 = new CartItem(
            'item-1',
            $cart->getId(),
            'variant-1',
            'product-1',
            'vendor-1',
            2,
            100.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $item2 = new CartItem(
            'item-2',
            $cart->getId(),
            'variant-2',
            'product-2',
            'vendor-2',
            3,
            150.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $item3 = new CartItem(
            'item-3',
            $cart->getId(),
            'variant-3',
            'product-3',
            'vendor-1',
            1,
            200.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $cart->addItem($item1);
        $cart->addItem($item2);
        $cart->addItem($item3);
        
        $grouped = $cart->groupByVendor();
        
        $this->assertCount(2, $grouped);
        $this->assertArrayHasKey('vendor-1', $grouped);
        $this->assertArrayHasKey('vendor-2', $grouped);
        $this->assertCount(2, $grouped['vendor-1']);
        $this->assertCount(1, $grouped['vendor-2']);
    }
    
    public function testClearCart()
    {
        $cart = Cart::create('customer-123');
        
        $item = new CartItem(
            'item-1',
            $cart->getId(),
            'variant-1',
            'product-1',
            'vendor-1',
            2,
            100.00,
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $cart->addItem($item);
        $this->assertEquals(1, $cart->getItemCount());
        
        $cart->clear();
        $this->assertEquals(0, $cart->getItemCount());
        $this->assertEmpty($cart->getItems());
    }
}
