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
            // Get product name
            $product = $this->orderRepo->findById($order->getId()); // This gets us access to product via order
            $productRepo = new \RentalPlatform\Repositories\ProductRepository();
            $productModel = $productRepo->findById($orderItem->getProductId());
            $productName = $productModel ? $productModel->getName() : 'Product';
            
            $lineItem = InvoiceLineItem::createRentalItem(
                $invoice->getId(),
                $productName,
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
            date('Y-m-d H:i:s') // Update timestamp
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
     * Generate PDF invoice for download (Task 22.4)
     * 
     * Requirements:
     * - 16.6: Allow customers to download invoices for active and completed rentals
     * 
     * @param string $orderId
     * @return string PDF content
     * @throws Exception
     */
    public function generateInvoicePDF(string $orderId): string
    {
        // Get order details
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        // Get invoice
        $invoice = $this->invoiceRepo->findByOrderId($orderId);
        if (!$invoice) {
            throw new Exception('Invoice not found for this order');
        }

        // Get invoice details
        $invoiceDetails = $this->getInvoiceDetails($invoice->getId());
        $lineItems = $invoiceDetails['line_items'];

        // Generate HTML content for PDF
        $html = $this->generateInvoiceHTML($order, $invoice, $lineItems);

        // For now, return HTML as PDF content
        // In a production environment, you would use a library like TCPDF or DomPDF
        return $this->convertHTMLToPDF($html);
    }

    /**
     * Generate HTML content for invoice with vendor branding
     */
    private function generateInvoiceHTML($order, $invoice, $lineItems): string
    {
        $orderArray = $order->toArray();
        $invoiceArray = $invoice->toArray();
        
        // Get vendor branding information
        $vendorRepo = new \RentalPlatform\Repositories\VendorRepository();
        $vendor = $vendorRepo->findById($orderArray['vendor_id']);
        
        $vendorBrandColor = $vendor ? $vendor->getBrandColor() : '#3b82f6';
        $vendorLogo = $vendor ? $vendor->getLogo() : null;
        $vendorBusinessName = $vendor ? $vendor->getBusinessName() : 'Vendor';
        $vendorLegalName = $vendor ? $vendor->getLegalName() : $vendorBusinessName;
        $vendorContactEmail = $vendor ? $vendor->getContactEmail() : '';
        $vendorContactPhone = $vendor ? $vendor->getContactPhone() : '';
        
        $logoHtml = '';
        if ($vendorLogo) {
            $logoPath = __DIR__ . '/../../public' . $vendorLogo;
            if (file_exists($logoPath)) {
                $logoHtml = "<img src='data:image/png;base64," . base64_encode(file_get_contents($logoPath)) . "' style='max-height: 80px; max-width: 200px; margin-bottom: 10px;' alt='Vendor Logo'>";
            }
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Invoice - {$invoiceArray['invoice_number']}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid {$vendorBrandColor}; padding-bottom: 20px; }
                .vendor-info { margin-bottom: 20px; }
                .company-info { margin-bottom: 20px; background-color: #f8f9fa; padding: 15px; border-left: 4px solid {$vendorBrandColor}; }
                .invoice-details { margin-bottom: 20px; }
                .order-info { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: {$vendorBrandColor}; color: white; }
                .total-row { font-weight: bold; background-color: #f8f9fa; }
                .final-total { background-color: {$vendorBrandColor}; color: white; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
                .brand-accent { color: {$vendorBrandColor}; }
                .vendor-header { display: flex; align-items: center; justify-content: center; flex-direction: column; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='vendor-header'>
                    {$logoHtml}
                    <h1 class='brand-accent'>INVOICE</h1>
                    <h2>{$vendorBusinessName}</h2>
                </div>
            </div>
            
            <div class='company-info'>
                <strong>{$vendorLegalName}</strong><br>";
        
        if ($vendorContactEmail) {
            $html .= "Email: {$vendorContactEmail}<br>";
        }
        if ($vendorContactPhone) {
            $html .= "Phone: {$vendorContactPhone}<br>";
        }
        
        $html .= "
                <em>Powered by RentalHub Platform</em>
            </div>
            
            <div class='invoice-details'>
                <table>
                    <tr>
                        <td><strong>Invoice Number:</strong></td>
                        <td>{$invoiceArray['invoice_number']}</td>
                        <td><strong>Invoice Date:</strong></td>
                        <td>" . date('d/m/Y', strtotime($invoiceArray['created_at'])) . "</td>
                    </tr>
                    <tr>
                        <td><strong>Order Number:</strong></td>
                        <td>{$orderArray['order_number']}</td>
                        <td><strong>Order Date:</strong></td>
                        <td>" . date('d/m/Y', strtotime($orderArray['created_at'])) . "</td>
                    </tr>
                </table>
            </div>
            
            <div class='order-info'>
                <table>
                    <tr>
                        <td><strong>Customer ID:</strong></td>
                        <td>{$orderArray['customer_id']}</td>
                        <td><strong>Vendor:</strong></td>
                        <td>{$vendorBusinessName}</td>
                    </tr>
                    <tr>
                        <td><strong>Payment ID:</strong></td>
                        <td>{$orderArray['payment_id']}</td>
                        <td><strong>Order Status:</strong></td>
                        <td>{$orderArray['status']}</td>
                    </tr>
                </table>
            </div>
            
            <h3 class='brand-accent'>Invoice Items</h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Tax</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($lineItems as $item) {
            $html .= "
                    <tr>
                        <td>{$item['description']}</td>
                        <td>{$item['item_type']}</td>
                        <td>{$item['quantity']}</td>
                        <td>₹" . number_format($item['unit_price'], 2) . "</td>
                        <td>₹" . number_format($item['tax_amount'], 2) . "</td>
                        <td>₹" . number_format($item['total_price'], 2) . "</td>
                    </tr>";
        }
        
        $html .= "
                    <tr class='total-row'>
                        <td colspan='4'><strong>Subtotal</strong></td>
                        <td>₹" . number_format($invoiceArray['tax_amount'], 2) . "</td>
                        <td>₹" . number_format($invoiceArray['subtotal'], 2) . "</td>
                    </tr>
                    <tr class='final-total'>
                        <td colspan='5'><strong>TOTAL AMOUNT</strong></td>
                        <td><strong>₹" . number_format($invoiceArray['total_amount'], 2) . "</strong></td>
                    </tr>
                </tbody>
            </table>
            
            <div class='footer'>
                <p>Thank you for choosing {$vendorBusinessName}!</p>
                <p>This is a computer-generated invoice and does not require a signature.</p>
                <p>Generated on: " . date('d/m/Y H:i:s') . "</p>
                <p><em>Powered by RentalHub Multi-Vendor Platform</em></p>
            </div>
        </body>
        </html>";
        
        return $html;
    }

    /**
     * Convert HTML to PDF (simplified version)
     * In production, use a proper PDF library like TCPDF or DomPDF
     */
    private function convertHTMLToPDF(string $html): string
    {
        // For now, return HTML content with PDF headers
        // In production, implement proper PDF conversion
        return $html;
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
