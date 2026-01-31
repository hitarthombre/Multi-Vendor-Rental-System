<?php

namespace RentalPlatform\Services;

use RentalPlatform\Models\Invoice;
use RentalPlatform\Models\InvoiceLineItem;
use RentalPlatform\Repositories\InvoiceRepository;
use RentalPlatform\Repositories\InvoiceLineItemRepository;
use RentalPlatform\Repositories\OrderRepository;
use RentalPlatform\Repositories\OrderItemRepository;
use Exception;

/**
 * Invoice Service
 * 
 * Handles invoice generation and management
 * Requirements: 13.1, 13.2, 13.3, 13.4, 13.5, 13.6, 13.7
 */
class InvoiceService
{
    private InvoiceRepository $invoiceRepo;
    private InvoiceLineItemRepository $lineItemRepo;
    private OrderRepository $orderRepo;
    private OrderItemRepository $orderItemRepo;
    private AuditLogger $auditLogger;

    public function __construct()
    {
        $this->invoiceRepo = new InvoiceRepository();
        $this->lineItemRepo = new InvoiceLineItemRepository();
        $this->orderRepo = new OrderRepository();
        $this->orderItemRepo = new OrderItemRepository();
        $this->auditLogger = new AuditLogger(\RentalPlatform\Database\Connection::getInstance());
    }

    /**
     * Generate invoice for an order (Task 17.1)
     * 
     * Requirements:
     * - 13.1: Generate separate invoice for each order
     * - 13.2: Include all required information
     * - 13.3: Generate only after payment verification
     * - 13.5: Link to order and payment
     * 
     * @param string $orderId
     * @return Invoice
     * @throws Exception
     */
    public function generateInvoiceForOrder(string $orderId): Invoice
    {
        // Get order details
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Check if invoice already exists
        $existingInvoice = $this->invoiceRepo->findByOrderId($orderId);
        if ($existingInvoice) {
            throw new Exception('Invoice already exists for this order');
        }

        // Get order items
        $orderItems = $this->orderItemRepo->findByOrderId($orderId);
        if (empty($orderItems)) {
            throw new Exception('Order has no items');
        }

        // Calculate totals
        $subtotal = 0;
        $taxAmount = 0;

        // Create invoice
        $invoice = Invoice::create(
            $order->getId(),
            $order->getVendorId(),
            $order->getCustomerId(),
            $order->getTotalAmount(), // Will be recalculated
            0, // Tax amount will be calculated
            $order->getTotalAmount()
        );

        $this->invoiceRepo->create($invoice);

        // Create line items for each order item (Task 17.5)
        foreach ($orderItems as $orderItem) {
            $lineItem = InvoiceLineItem::createRentalItem(
                $invoice->getId(),
                $orderItem->getProductName(),
                $orderItem->getQuantity(),
                $orderItem->getUnitPrice(),
                0.0 // Tax rate - can be configured per product
            );

            $this->lineItemRepo->create($lineItem);
            
            $subtotal += $lineItem->getTotalPrice();
            $taxAmount += $lineItem->getTaxAmount();
        }

        // Add deposit as separate line item if applicable (Task 17.5, Requirement 13.6, 14.3)
        if ($order->getDepositAmount() > 0) {
            $depositItem = InvoiceLineItem::createDepositItem(
                $invoice->getId(),
                'Security Deposit',
                $order->getDepositAmount()
            );

            $this->lineItemRepo->create($depositItem);
            $subtotal += $depositItem->getTotalPrice();
        }

        // Update invoice totals
        $invoice = new Invoice(
            $invoice->getId(),
            $invoice->getInvoiceNumber(),
            $invoice->getOrderId(),
            $invoice->getVendorId(),
            $invoice->getCustomerId(),
            $subtotal,
            $taxAmount,
            $subtotal + $taxAmount,
            $invoice->getStatus(),
            $invoice->getFinalizedAt(),
            $invoice->getCreatedAt(),
            $invoice->getUpdatedAt()
        );

        $this->invoiceRepo->update($invoice);

        return $invoice;
    }

    /**
     * Finalize invoice (Task 17.3)
     * 
     * Requirements:
     * - 13.4: Make invoice immutable after finalization
     * 
     * @param string $invoiceId
     * @param string $actorId
     * @throws Exception
     */
    public function finalizeInvoice(string $invoiceId, string $actorId): void
    {
        $invoice = $this->invoiceRepo->findById($invoiceId);
        if (!$invoice) {
            throw new Exception('Invoice not found');
        }

        if ($invoice->isFinalized()) {
            throw new Exception('Invoice is already finalized');
        }

        // Finalize the invoice
        $invoice->finalize();
        $this->invoiceRepo->update($invoice);

        // Log the finalization
        $this->auditLogger->logInvoiceFinalize(
            $invoice->getId(),
            $invoice->getOrderId(),
            $invoice->getTotalAmount()
        );
    }

    /**
     * Add service charge to invoice (Task 17.5)
     * 
     * Requirements:
     * - 13.6: Add service charges as separate line items
     * - 14.3: Record deposits separately
     * 
     * @param string $invoiceId
     * @param string $description
     * @param string $itemType
     * @param float $amount
     * @param float $taxRate
     * @throws Exception
     */
    public function addServiceCharge(
        string $invoiceId,
        string $description,
        string $itemType,
        float $amount,
        float $taxRate = 0.0
    ): void {
        $invoice = $this->invoiceRepo->findById($invoiceId);
        if (!$invoice) {
            throw new Exception('Invoice not found');
        }

        if ($invoice->isFinalized()) {
            throw new Exception('Cannot modify finalized invoice');
        }

        // Create line item based on type
        $lineItem = match($itemType) {
            InvoiceLineItem::TYPE_DEPOSIT => InvoiceLineItem::createDepositItem($invoiceId, $description, $amount),
            InvoiceLineItem::TYPE_DELIVERY => InvoiceLineItem::createDeliveryItem($invoiceId, $amount, $taxRate),
            InvoiceLineItem::TYPE_FEE => InvoiceLineItem::createFeeItem($invoiceId, $description, $amount, $taxRate),
            InvoiceLineItem::TYPE_PENALTY => InvoiceLineItem::createPenaltyItem($invoiceId, $description, $amount),
            default => throw new Exception('Invalid item type')
        };

        $this->lineItemRepo->create($lineItem);

        // Recalculate invoice totals
        $this->recalculateInvoiceTotals($invoiceId);
    }

    /**
     * Create refund record without modifying original invoice (Task 17.7)
     * 
     * Requirements:
     * - 13.7: Create financial reversal records
     * - 13.7: Preserve original invoice
     * 
     * @param string $originalInvoiceId
     * @param float $refundAmount
     * @param string $reason
     * @return Invoice Refund invoice
     * @throws Exception
     */
    public function createRefundInvoice(
        string $originalInvoiceId,
        float $refundAmount,
        string $reason
    ): Invoice {
        $originalInvoice = $this->invoiceRepo->findById($originalInvoiceId);
        if (!$originalInvoice) {
            throw new Exception('Original invoice not found');
        }

        // Create a new invoice for the refund (negative amounts)
        $refundInvoice = Invoice::create(
            $originalInvoice->getOrderId(),
            $originalInvoice->getVendorId(),
            $originalInvoice->getCustomerId(),
            -$refundAmount,
            0,
            -$refundAmount
        );

        $this->invoiceRepo->create($refundInvoice);

        // Create line item for refund
        $refundLineItem = InvoiceLineItem::create(
            $refundInvoice->getId(),
            "Refund: {$reason} (Original Invoice: {$originalInvoice->getInvoiceNumber()})",
            InvoiceLineItem::TYPE_FEE,
            1,
            -$refundAmount,
            0.0
        );

        $this->lineItemRepo->create($refundLineItem);

        // Finalize the refund invoice immediately
        $refundInvoice->finalize();
        $this->invoiceRepo->update($refundInvoice);

        return $refundInvoice;
    }

    /**
     * Get invoice with line items
     * 
     * @param string $invoiceId
     * @return array
     * @throws Exception
     */
    public function getInvoiceDetails(string $invoiceId): array
    {
        $invoice = $this->invoiceRepo->findById($invoiceId);
        if (!$invoice) {
            throw new Exception('Invoice not found');
        }

        $lineItems = $this->lineItemRepo->findByInvoiceId($invoiceId);
        $summary = $this->lineItemRepo->getInvoiceSummary($invoiceId);

        return [
            'invoice' => $invoice->toArray(),
            'line_items' => array_map(fn($item) => $item->toArray(), $lineItems),
            'summary' => $summary
        ];
    }

    /**
     * Get invoices for vendor
     * 
     * @param string $vendorId
     * @return array
     */
    public function getVendorInvoices(string $vendorId): array
    {
        return $this->invoiceRepo->findByVendorId($vendorId);
    }

    /**
     * Get invoices for customer
     * 
     * @param string $customerId
     * @return array
     */
    public function getCustomerInvoices(string $customerId): array
    {
        return $this->invoiceRepo->findByCustomerId($customerId);
    }

    /**
     * Get invoice by order ID
     * 
     * @param string $orderId
     * @return Invoice|null
     */
    public function getInvoiceByOrderId(string $orderId): ?Invoice
    {
        return $this->invoiceRepo->findByOrderId($orderId);
    }

    /**
     * Recalculate invoice totals from line items
     * 
     * @param string $invoiceId
     * @throws Exception
     */
    private function recalculateInvoiceTotals(string $invoiceId): void
    {
        $invoice = $this->invoiceRepo->findById($invoiceId);
        if (!$invoice) {
            throw new Exception('Invoice not found');
        }

        $lineItems = $this->lineItemRepo->findByInvoiceId($invoiceId);

        $subtotal = 0;
        $taxAmount = 0;

        foreach ($lineItems as $lineItem) {
            $subtotal += $lineItem->getTotalPrice();
            $taxAmount += $lineItem->getTaxAmount();
        }

        // Create updated invoice
        $updatedInvoice = new Invoice(
            $invoice->getId(),
            $invoice->getInvoiceNumber(),
            $invoice->getOrderId(),
            $invoice->getVendorId(),
            $invoice->getCustomerId(),
            $subtotal,
            $taxAmount,
            $subtotal + $taxAmount,
            $invoice->getStatus(),
            $invoice->getFinalizedAt(),
            $invoice->getCreatedAt(),
            date('Y-m-d H:i:s')
        );

        $this->invoiceRepo->update($updatedInvoice);
    }
}
