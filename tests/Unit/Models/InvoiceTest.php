<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Models\Invoice;

/**
 * Unit tests for Invoice model
 */
class InvoiceTest extends TestCase
{
    public function testCreateInvoice(): void
    {
        $invoice = Invoice::create(
            'order-123',
            'vendor-456',
            'customer-789',
            1000.00,
            100.00,
            1100.00
        );

        $this->assertNotEmpty($invoice->getId());
        $this->assertNotEmpty($invoice->getInvoiceNumber());
        $this->assertStringStartsWith('INV-', $invoice->getInvoiceNumber());
        $this->assertEquals('order-123', $invoice->getOrderId());
        $this->assertEquals('vendor-456', $invoice->getVendorId());
        $this->assertEquals('customer-789', $invoice->getCustomerId());
        $this->assertEquals(1000.00, $invoice->getSubtotal());
        $this->assertEquals(100.00, $invoice->getTaxAmount());
        $this->assertEquals(1100.00, $invoice->getTotalAmount());
        $this->assertEquals(Invoice::STATUS_DRAFT, $invoice->getStatus());
        $this->assertTrue($invoice->isDraft());
        $this->assertFalse($invoice->isFinalized());
    }

    public function testInvoiceNumberUniqueness(): void
    {
        $invoice1 = Invoice::create('order-1', 'vendor-1', 'customer-1', 100, 10, 110);
        $invoice2 = Invoice::create('order-2', 'vendor-1', 'customer-1', 200, 20, 220);
        $invoice3 = Invoice::create('order-3', 'vendor-1', 'customer-1', 300, 30, 330);

        $this->assertNotEquals($invoice1->getInvoiceNumber(), $invoice2->getInvoiceNumber());
        $this->assertNotEquals($invoice2->getInvoiceNumber(), $invoice3->getInvoiceNumber());
        $this->assertNotEquals($invoice1->getInvoiceNumber(), $invoice3->getInvoiceNumber());
    }

    public function testFinalizeInvoice(): void
    {
        $invoice = Invoice::create(
            'order-123',
            'vendor-456',
            'customer-789',
            1000.00,
            100.00,
            1100.00
        );

        $this->assertTrue($invoice->isDraft());
        $this->assertNull($invoice->getFinalizedAt());

        $invoice->finalize();

        $this->assertTrue($invoice->isFinalized());
        $this->assertFalse($invoice->isDraft());
        $this->assertEquals(Invoice::STATUS_FINALIZED, $invoice->getStatus());
        $this->assertNotNull($invoice->getFinalizedAt());
    }

    public function testCannotFinalizeAlreadyFinalizedInvoice(): void
    {
        $invoice = Invoice::create(
            'order-123',
            'vendor-456',
            'customer-789',
            1000.00,
            100.00,
            1100.00
        );

        $invoice->finalize();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invoice is already finalized and cannot be modified');
        
        $invoice->finalize();
    }

    public function testInvoiceToArray(): void
    {
        $invoice = Invoice::create(
            'order-123',
            'vendor-456',
            'customer-789',
            1000.00,
            100.00,
            1100.00
        );

        $array = $invoice->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('invoice_number', $array);
        $this->assertArrayHasKey('order_id', $array);
        $this->assertArrayHasKey('vendor_id', $array);
        $this->assertArrayHasKey('customer_id', $array);
        $this->assertArrayHasKey('subtotal', $array);
        $this->assertArrayHasKey('tax_amount', $array);
        $this->assertArrayHasKey('total_amount', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('finalized_at', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);

        $this->assertEquals('order-123', $array['order_id']);
        $this->assertEquals(1000.00, $array['subtotal']);
        $this->assertEquals(Invoice::STATUS_DRAFT, $array['status']);
    }

    public function testRefundInvoiceWithNegativeAmounts(): void
    {
        $refundInvoice = Invoice::create(
            'order-123',
            'vendor-456',
            'customer-789',
            -500.00,
            -50.00,
            -550.00
        );

        $this->assertEquals(-500.00, $refundInvoice->getSubtotal());
        $this->assertEquals(-50.00, $refundInvoice->getTaxAmount());
        $this->assertEquals(-550.00, $refundInvoice->getTotalAmount());
    }

    public function testInvoiceTimestamps(): void
    {
        $invoice = Invoice::create(
            'order-123',
            'vendor-456',
            'customer-789',
            1000.00,
            100.00,
            1100.00
        );

        $this->assertNotEmpty($invoice->getCreatedAt());
        $this->assertNotEmpty($invoice->getUpdatedAt());
        
        $createdAt = $invoice->getCreatedAt();
        
        // Touch should update the timestamp
        $invoice->touch();
        
        $this->assertEquals($createdAt, $invoice->getCreatedAt());
        $this->assertGreaterThanOrEqual($createdAt, $invoice->getUpdatedAt());
    }
}
