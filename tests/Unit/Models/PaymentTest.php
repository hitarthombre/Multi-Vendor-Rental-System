<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Models\Payment;
use DateTime;

class PaymentTest extends TestCase
{
    public function testCreatePayment()
    {
        $razorpayOrderId = 'order_123456';
        $amount = 1000.00;
        $customerId = 'customer-123';
        
        $payment = Payment::create($razorpayOrderId, $amount, $customerId);
        
        $this->assertNotEmpty($payment->getId());
        $this->assertEquals($razorpayOrderId, $payment->getRazorpayOrderId());
        $this->assertEquals($amount, $payment->getAmount());
        $this->assertEquals('INR', $payment->getCurrency());
        $this->assertEquals($customerId, $payment->getCustomerId());
        $this->assertEquals('created', $payment->getStatus());
        $this->assertNull($payment->getRazorpayPaymentId());
        $this->assertNull($payment->getRazorpaySignature());
        $this->assertNull($payment->getVerifiedAt());
    }
    
    public function testVerifyPayment()
    {
        $payment = Payment::create('order_123', 1000.00, 'customer-123');
        
        $this->assertEquals('created', $payment->getStatus());
        $this->assertNull($payment->getVerifiedAt());
        
        $payment->verify('pay_123', 'signature_abc');
        
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('pay_123', $payment->getRazorpayPaymentId());
        $this->assertEquals('signature_abc', $payment->getRazorpaySignature());
        $this->assertInstanceOf(DateTime::class, $payment->getVerifiedAt());
    }
    
    public function testMarkAsFailed()
    {
        $payment = Payment::create('order_123', 1000.00, 'customer-123');
        
        $this->assertEquals('created', $payment->getStatus());
        
        $payment->fail();
        
        $this->assertEquals('failed', $payment->getStatus());
    }
    
    public function testIsVerified()
    {
        $payment = Payment::create('order_123', 1000.00, 'customer-123');
        
        $this->assertFalse($payment->isVerified());
        
        $payment->verify('pay_123', 'signature_abc');
        
        $this->assertTrue($payment->isVerified());
    }
    
    public function testCreateWithMetadata()
    {
        $metadata = ['order_id' => 'order-456', 'cart_id' => 'cart-789'];
        $payment = Payment::create('order_123', 1000.00, 'customer-123', $metadata);
        
        $this->assertEquals($metadata, $payment->getMetadata());
    }
}
