<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Models\Refund;
use DateTime;

class RefundTest extends TestCase
{
    public function testCreateRefund()
    {
        $paymentId = 'payment-123';
        $orderId = 'order-456';
        $amount = 500.00;
        $reason = 'Order cancelled by vendor';
        
        $refund = Refund::create($paymentId, $orderId, $amount, $reason);
        
        $this->assertNotEmpty($refund->getId());
        $this->assertEquals($paymentId, $refund->getPaymentId());
        $this->assertEquals($orderId, $refund->getOrderId());
        $this->assertEquals($amount, $refund->getAmount());
        $this->assertEquals($reason, $refund->getReason());
        $this->assertEquals('pending', $refund->getStatus());
        $this->assertNull($refund->getRazorpayRefundId());
        $this->assertNull($refund->getProcessedAt());
    }
    
    public function testMarkAsProcessing()
    {
        $refund = Refund::create('payment-123', 'order-456', 500.00, 'Cancelled');
        
        $this->assertEquals('pending', $refund->getStatus());
        
        $razorpayRefundId = 'rfnd_123456';
        $refund->markProcessing($razorpayRefundId);
        
        $this->assertEquals('processing', $refund->getStatus());
        $this->assertEquals($razorpayRefundId, $refund->getRazorpayRefundId());
    }
    
    public function testCompleteRefund()
    {
        $refund = Refund::create('payment-123', 'order-456', 500.00, 'Cancelled');
        $refund->markProcessing('rfnd_123');
        
        $this->assertEquals('processing', $refund->getStatus());
        $this->assertNull($refund->getProcessedAt());
        
        $refund->complete();
        
        $this->assertEquals('completed', $refund->getStatus());
        $this->assertInstanceOf(DateTime::class, $refund->getProcessedAt());
    }
}
