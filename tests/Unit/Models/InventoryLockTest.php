<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Models\InventoryLock;
use DateTime;

class InventoryLockTest extends TestCase
{
    public function testCreateInventoryLock()
    {
        $variantId = 'variant-123';
        $orderId = 'order-456';
        $startDate = new DateTime('2024-01-01');
        $endDate = new DateTime('2024-01-05');
        
        $lock = InventoryLock::create($variantId, $orderId, $startDate, $endDate);
        
        $this->assertNotEmpty($lock->getId());
        $this->assertEquals($variantId, $lock->getVariantId());
        $this->assertEquals($orderId, $lock->getOrderId());
        $this->assertEquals($startDate, $lock->getStartDate());
        $this->assertEquals($endDate, $lock->getEndDate());
        $this->assertEquals('active', $lock->getStatus());
        $this->assertNull($lock->getReleasedAt());
    }
    
    public function testOverlapsWithCompleteOverlap()
    {
        $lock = InventoryLock::create(
            'variant-123',
            'order-1',
            new DateTime('2024-01-01'),
            new DateTime('2024-01-10')
        );
        
        // Period completely inside the lock
        $this->assertTrue($lock->overlaps(
            new DateTime('2024-01-05'),
            new DateTime('2024-01-08')
        ));
    }
    
    public function testOverlapsWithPartialOverlap()
    {
        $lock = InventoryLock::create(
            'variant-123',
            'order-1',
            new DateTime('2024-01-01'),
            new DateTime('2024-01-10')
        );
        
        // Period overlaps end of lock
        $this->assertTrue($lock->overlaps(
            new DateTime('2024-01-08'),
            new DateTime('2024-01-15')
        ));
        
        // Period overlaps start of lock
        $this->assertTrue($lock->overlaps(
            new DateTime('2023-12-28'),
            new DateTime('2024-01-03')
        ));
    }
    
    public function testNoOverlapWhenPeriodsAreAdjacent()
    {
        $lock = InventoryLock::create(
            'variant-123',
            'order-1',
            new DateTime('2024-01-01'),
            new DateTime('2024-01-10')
        );
        
        // Period starts exactly when lock ends
        $this->assertFalse($lock->overlaps(
            new DateTime('2024-01-10'),
            new DateTime('2024-01-15')
        ));
        
        // Period ends exactly when lock starts
        $this->assertFalse($lock->overlaps(
            new DateTime('2023-12-25'),
            new DateTime('2024-01-01')
        ));
    }
    
    public function testNoOverlapWhenPeriodsAreSeparate()
    {
        $lock = InventoryLock::create(
            'variant-123',
            'order-1',
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        // Period is completely after lock
        $this->assertFalse($lock->overlaps(
            new DateTime('2024-01-10'),
            new DateTime('2024-01-15')
        ));
        
        // Period is completely before lock
        $this->assertFalse($lock->overlaps(
            new DateTime('2023-12-20'),
            new DateTime('2023-12-25')
        ));
    }
    
    public function testReleaseLock()
    {
        $lock = InventoryLock::create(
            'variant-123',
            'order-456',
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $this->assertEquals('active', $lock->getStatus());
        $this->assertNull($lock->getReleasedAt());
        
        $lock->release();
        
        $this->assertEquals('released', $lock->getStatus());
        $this->assertInstanceOf(DateTime::class, $lock->getReleasedAt());
    }
    
    public function testIsActive()
    {
        $lock = InventoryLock::create(
            'variant-123',
            'order-456',
            new DateTime('2024-01-01'),
            new DateTime('2024-01-05')
        );
        
        $this->assertTrue($lock->isActive());
        
        $lock->release();
        
        $this->assertFalse($lock->isActive());
    }
}
