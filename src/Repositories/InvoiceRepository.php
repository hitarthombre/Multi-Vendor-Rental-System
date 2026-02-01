<?php

namespace RentalPlatform\Repositories;

use RentalPlatform\Models\Invoice;
use RentalPlatform\Database\Connection;
use PDO;

/**
 * Invoice Repository
 * 
 * Handles database operations for invoices
 */
class InvoiceRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    /**
     * Create a new invoice
     */
    public function create(Invoice $invoice): void
    {
        $sql = "INSERT INTO invoices (
            id, invoice_number, order_id, vendor_id, customer_id,
            subtotal, tax_amount, total_amount, status, finalized_at,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $invoice->getId(),
            $invoice->getInvoiceNumber(),
            $invoice->getOrderId(),
            $invoice->getVendorId(),
            $invoice->getCustomerId(),
            $invoice->getSubtotal(),
            $invoice->getTaxAmount(),
            $invoice->getTotalAmount(),
            $invoice->getStatus(),
            $invoice->getFinalizedAt(),
            $invoice->getCreatedAt(),
            $invoice->getUpdatedAt()
        ]);
    }

    /**
     * Find invoice by ID
     */
    public function findById(string $id): ?Invoice
    {
        $sql = "SELECT * FROM invoices WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->mapRowToInvoice($row);
    }

    /**
     * Find invoice by invoice number
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        $sql = "SELECT * FROM invoices WHERE invoice_number = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$invoiceNumber]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->mapRowToInvoice($row);
    }

    /**
     * Find invoice by order ID
     */
    public function findByOrderId(string $orderId): ?Invoice
    {
        $sql = "SELECT * FROM invoices WHERE order_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->mapRowToInvoice($row);
    }

    /**
     * Find invoices by vendor ID
     */
    public function findByVendorId(string $vendorId): array
    {
        $sql = "SELECT * FROM invoices WHERE vendor_id = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$vendorId]);
        
        $invoices = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $invoices[] = $this->mapRowToInvoice($row);
        }

        return $invoices;
    }

    /**
     * Find invoices by customer ID
     */
    public function findByCustomerId(string $customerId): array
    {
        $sql = "SELECT * FROM invoices WHERE customer_id = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$customerId]);
        
        $invoices = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $invoices[] = $this->mapRowToInvoice($row);
        }

        return $invoices;
    }

    /**
     * Find invoices by status
     */
    public function findByStatus(string $status): array
    {
        $sql = "SELECT * FROM invoices WHERE status = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$status]);
        
        $invoices = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $invoices[] = $this->mapRowToInvoice($row);
        }

        return $invoices;
    }

    /**
     * Update invoice
     */
    public function update(Invoice $invoice): void
    {
        $sql = "UPDATE invoices SET 
            subtotal = ?,
            tax_amount = ?,
            total_amount = ?,
            status = ?,
            finalized_at = ?,
            updated_at = ?
        WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $invoice->getSubtotal(),
            $invoice->getTaxAmount(),
            $invoice->getTotalAmount(),
            $invoice->getStatus(),
            $invoice->getFinalizedAt(),
            $invoice->getUpdatedAt(),
            $invoice->getId()
        ]);
    }

    /**
     * Check if invoice number exists
     */
    public function invoiceNumberExists(string $invoiceNumber): bool
    {
        $sql = "SELECT COUNT(*) FROM invoices WHERE invoice_number = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$invoiceNumber]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get invoice statistics for vendor
     */
    public function getVendorStatistics(string $vendorId): array
    {
        $sql = "SELECT 
            COUNT(*) as total_invoices,
            SUM(total_amount) as total_revenue,
            SUM(CASE WHEN status = 'Finalized' THEN total_amount ELSE 0 END) as finalized_revenue
        FROM invoices 
        WHERE vendor_id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$vendorId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Delete invoice (for testing purposes only)
     */
    public function delete(string $id): void
    {
        $sql = "DELETE FROM invoices WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
    }

    /**
     * Map database row to Invoice object
     */
    private function mapRowToInvoice(array $row): Invoice
    {
        return new Invoice(
            $row['id'],
            $row['invoice_number'],
            $row['order_id'],
            $row['vendor_id'],
            $row['customer_id'],
            (float)$row['subtotal'],
            (float)$row['tax_amount'],
            (float)$row['total_amount'],
            $row['status'],
            $row['finalized_at'],
            $row['created_at'],
            $row['updated_at'] ?? $row['created_at'] // Use created_at if updated_at doesn't exist
        );
    }
}
