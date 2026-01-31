<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use RentalPlatform\Models\InvoiceLineItem;

/**
 * Unit tests for InvoiceLineItem model
 */
class InvoiceLineItemTest extends TestCase
{
    public function testCreateRentalItem(): void
    {
        $lineItem = InvoiceLineItem::createRentalItem(
            'invoice-123',
            'Camera - Canon EOS R5',
            1,
            500.00,
            10.0
        );

        $this->assertNotEmpty($lineItem->getId());
        $this->assertEquals('invoice-123', $lineItem->getInvoiceId());
        $this->assertStringContainsString('Camera - Canon EOS R5', $lineItem->getDescription());
        $this->assertEquals(InvoiceLineItem::TYPE_RENTAL, $lineItem->getItemType());
        $this->assertEquals(1, $lineItem->getQuantity());
        $this->assertEquals(500.00, $lineItem->getUnitPrice());
        $this->assertEquals(500.00, $lineItem->getTotalPrice());
        $this->assertEquals(10.0, $lineItem->getTaxRate());
        $this->assertEquals(50.00, $lineItem->getTaxAmount());
        $this->assertEquals(550.00, $lineItem->getTotalWithTax());
        $this->assertTrue($lineItem->isRental());
        $this->assertFalse($lineItem->isDeposit());
    }

    public function testCreateDepositItem(): void
    {
        $lineItem = InvoiceLineItem::createDepositItem(
            'invoice-123',
            'Camera Equipment',
            200.00
        );

        $this->assertEquals(InvoiceLineItem::TYPE_DEPOSIT, $lineItem->getItemType());
        $this->assertStringContainsString('Security Deposit', $lineItem->getDescription());
        $this->assertStringContainsString('Camera Equipment', $lineItem->getDescription());
        $this->assertEquals(200.00, $lineItem->getTotalPrice());
        $this->assertEquals(0.0, $lineItem->getTaxRate());
        $this->assertEquals(0.0, $lineItem->getTaxAmount());
        $this->assertTrue($lineItem->isDeposit());
        $this->assertFalse($lineItem->isRental());
    }

    public function testCreateDeliveryItem(): void
    {
        $lineItem = InvoiceLineItem::createDeliveryItem(
            'invoice-123',
            50.00,
            5.0
        );

        $this->assertEquals(InvoiceLineItem::TYPE_DELIVERY, $lineItem->getItemType());
        $this->assertEquals('Delivery Fee', $lineItem->getDescription());
        $this->assertEquals(50.00, $lineItem->getTotalPrice());
        $this->assertEquals(5.0, $lineItem->getTaxRate());
        $this->assertEquals(2.50, $lineItem->getTaxAmount());
        $this->assertEquals(52.50, $lineItem->getTotalWithTax());
    }

    public function testCreateFeeItem(): void
    {
        $lineItem = InvoiceLineItem::createFeeItem(
            'invoice-123',
            'Service Fee',
            25.00,
            8.0
        );

        $this->assertEquals(InvoiceLineItem::TYPE_FEE, $lineItem->getItemType());
        $this->assertEquals('Service Fee', $lineItem->getDescription());
        $this->assertEquals(25.00, $lineItem->getTotalPrice());
        $this->assertEquals(8.0, $lineItem->getTaxRate());
        $this->assertEquals(2.00, $lineItem->getTaxAmount());
    }

    public function testCreatePenaltyItem(): void
    {
        $lineItem = InvoiceLineItem::createPenaltyItem(
            'invoice-123',
            'Late Return',
            100.00
        );

        $this->assertEquals(InvoiceLineItem::TYPE_PENALTY, $lineItem->getItemType());
        $this->assertStringContainsString('Penalty', $lineItem->getDescription());
        $this->assertStringContainsString('Late Return', $lineItem->getDescription());
        $this->assertEquals(100.00, $lineItem->getTotalPrice());
        $this->assertEquals(0.0, $lineItem->getTaxRate());
        $this->assertEquals(0.0, $lineItem->getTaxAmount());
    }

    public function testMultipleQuantityCalculation(): void
    {
        $lineItem = InvoiceLineItem::create(
            'invoice-123',
            'Test Product',
            InvoiceLineItem::TYPE_RENTAL,
            3,
            100.00,
            10.0
        );

        $this->assertEquals(3, $lineItem->getQuantity());
        $this->assertEquals(100.00, $lineItem->getUnitPrice());
        $this->assertEquals(300.00, $lineItem->getTotalPrice());
        $this->assertEquals(30.00, $lineItem->getTaxAmount());
        $this->assertEquals(330.00, $lineItem->getTotalWithTax());
    }

    public function testZeroTaxRate(): void
    {
        $lineItem = InvoiceLineItem::create(
            'invoice-123',
            'Tax-Free Item',
            InvoiceLineItem::TYPE_RENTAL,
            1,
            100.00,
            0.0
        );

        $this->assertEquals(0.0, $lineItem->getTaxRate());
        $this->assertEquals(0.0, $lineItem->getTaxAmount());
        $this->assertEquals(100.00, $lineItem->getTotalWithTax());
    }

    public function testLineItemToArray(): void
    {
        $lineItem = InvoiceLineItem::createRentalItem(
            'invoice-123',
            'Test Product',
            2,
            50.00,
            10.0
        );

        $array = $lineItem->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('invoice_id', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('item_type', $array);
        $this->assertArrayHasKey('quantity', $array);
        $this->assertArrayHasKey('unit_price', $array);
        $this->assertArrayHasKey('total_price', $array);
        $this->assertArrayHasKey('tax_rate', $array);
        $this->assertArrayHasKey('tax_amount', $array);
        $this->assertArrayHasKey('total_with_tax', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);

        $this->assertEquals('invoice-123', $array['invoice_id']);
        $this->assertEquals(InvoiceLineItem::TYPE_RENTAL, $array['item_type']);
        $this->assertEquals(2, $array['quantity']);
        $this->assertEquals(100.00, $array['total_price']);
    }

    public function testItemTypeChecks(): void
    {
        $rentalItem = InvoiceLineItem::createRentalItem('inv-1', 'Product', 1, 100, 0);
        $depositItem = InvoiceLineItem::createDepositItem('inv-1', 'Deposit', 50);

        $this->assertTrue($rentalItem->isRental());
        $this->assertFalse($rentalItem->isDeposit());

        $this->assertTrue($depositItem->isDeposit());
        $this->assertFalse($depositItem->isRental());
    }

    public function testTimestamps(): void
    {
        $lineItem = InvoiceLineItem::create(
            'invoice-123',
            'Test Item',
            InvoiceLineItem::TYPE_RENTAL,
            1,
            100.00,
            0.0
        );

        $this->assertNotEmpty($lineItem->getCreatedAt());
        $this->assertNotEmpty($lineItem->getUpdatedAt());
    }
}
