<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Models\Wishlist;

class WishlistTest extends TestCase
{
    public function testCreateWishlist()
    {
        $customerId = 'customer-123';
        $productId = 'product-456';
        
        $wishlist = Wishlist::create($customerId, $productId);
        
        $this->assertNotEmpty($wishlist->getId());
        $this->assertEquals($customerId, $wishlist->getCustomerId());
        $this->assertEquals($productId, $wishlist->getProductId());
        $this->assertNotEmpty($wishlist->getCreatedAt());
    }
    
    public function testToArray()
    {
        $customerId = 'customer-123';
        $productId = 'product-456';
        
        $wishlist = Wishlist::create($customerId, $productId);
        $array = $wishlist->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('customer_id', $array);
        $this->assertArrayHasKey('product_id', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertEquals($customerId, $array['customer_id']);
        $this->assertEquals($productId, $array['product_id']);
    }
}
