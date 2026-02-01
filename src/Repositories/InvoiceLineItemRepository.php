<?php

namespace RentalPlatform\Repositories;

use RentalPlatform\Models\InvoiceLineItem;
use RentalPlatform\Database\Connection;
use PDO;

/**
 * Invoice Line Item Repository
 * 
 * Handles database operations for invoice line items
 */
class InvoiceLineItemRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    /**
     * Create a new invoice line item
     */
    public function create(InvoiceLineItem $lineItem): void
    {
        $sql = "INSERT INTO invoice_line_items (
            id, invoice_id, description, item_type, quantity,
            unit_price, total_price, tax_rate, tax_amount,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $lineItem->getId(),
            $lineItem->getInvoiceId(),
            $lineItem->getDescription(),
            $lineItem->getItemType(),
            $lineItem->getQuantity(),
            $lineItem->getUnitPrice(),
            $lineItem->getTotalPrice(),
            $lineItem->getTaxRate(),
            $lineItem->getTaxAmount(),
            $lineItem->getCreatedAt(),
            $lineItem->getUpdatedAt()
        ]);
    }

    /**
     * Find line item by ID
     */
    public function findById(string $id): ?InvoiceLineItem
    {
        $sql = "SELECT * FROM invoice_line_items WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->mapRowToLineItem($row);
    }

    /**
     * Find line items by invoice ID
     */
    public function findByInvoiceId(string $invoiceId): array
    {
        $sql = "SELECT * FROM invoice_line_items WHERE invoice_id = ? ORDER BY created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$invoiceId]);
        
        $lineItems = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lineItems[] = $this->mapRowToLineItem($row);
        }

        return $lineItems;
    }

    /**
     * Find line items by type
     */
    public function findByInvoiceIdAndType(string $invoiceId, string $itemType): array
    {
        $sql = "SELECT * FROM invoice_line_items WHERE invoice_id = ? AND item_type = ? ORDER BY created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$invoiceId, $itemType]);
        
        $lineItems = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lineItems[] = $this->mapRowToLineItem($row);
        }

        return $lineItems;
    }

    /**
     * Get line items summary for invoice
     */
    public function getInvoiceSummary(string $invoiceId): array
    {
        $sql = "SELECT 
            item_type,
            COUNT(*) as item_count,
            SUM(total_price) as subtotal,
            SUM(tax_amount) as tax_total,
            SUM(total_price + tax_amount) as total
        FROM invoice_line_items 
        WHERE invoice_id = ? 
        GROUP BY item_type";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$invoiceId]);
        
        $summary = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $summary[$row['item_type']] = [
                'item_count' => (int)$row['item_count'],
                'subtotal' => (float)$row['subtotal'],
                'tax_total' => (float)$row['tax_total'],
                'total' => (float)$row['total']
            ];
        }

        return $summary;
    }

    /**
     * Delete line items by invoice ID
     */
    public function deleteByInvoiceId(string $invoiceId): void
    {
        $sql = "DELETE FROM invoice_line_items WHERE invoice_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$invoiceId]);
    }

    /**
     * Delete line item (for testing purposes only)
     */
    public function delete(string $id): void
    {
        $sql = "DELETE FROM invoice_line_items WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
    }

    /**
     * Map database row to InvoiceLineItem object
     */
    private function mapRowToLineItem(array $row): InvoiceLineItem
    {
        return new InvoiceLineItem(
            $row['id'],
            $row['invoice_id'],
            $row['description'],
            $row['item_type'],
            (int)$row['quantity'],
            (float)$row['unit_price'],
            (float)$row['total_price'],
            (float)$row['tax_rate'],
            (float)$row['tax_amount'],
            $row['created_at'],
            $row['updated_at'] ?? $row['created_at'] // Use created_at if updated_at doesn't exist
        );
    }
}
