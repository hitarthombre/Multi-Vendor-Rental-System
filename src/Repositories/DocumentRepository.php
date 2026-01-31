<?php

namespace RentalPlatform\Repositories;

use RentalPlatform\Models\Document;
use RentalPlatform\Database\Connection;
use PDO;

/**
 * Document Repository
 * 
 * Handles database operations for documents
 */
class DocumentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    /**
     * Create a new document
     */
    public function create(Document $document): void
    {
        $sql = "INSERT INTO documents (
            id, order_id, customer_id, document_type, 
            file_name, file_path, file_size, mime_type, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $document->getId(),
            $document->getOrderId(),
            $document->getCustomerId(),
            $document->getDocumentType(),
            $document->getFileName(),
            $document->getFilePath(),
            $document->getFileSize(),
            $document->getMimeType(),
            $document->getCreatedAt()
        ]);
    }

    /**
     * Find document by ID
     */
    public function findById(string $id): ?Document
    {
        $sql = "SELECT * FROM documents WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->mapRowToDocument($row);
    }

    /**
     * Find documents by order ID
     */
    public function findByOrderId(string $orderId): array
    {
        $sql = "SELECT * FROM documents WHERE order_id = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderId]);
        
        $documents = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $documents[] = $this->mapRowToDocument($row);
        }

        return $documents;
    }

    /**
     * Find documents by customer ID
     */
    public function findByCustomerId(string $customerId): array
    {
        $sql = "SELECT * FROM documents WHERE customer_id = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$customerId]);
        
        $documents = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $documents[] = $this->mapRowToDocument($row);
        }

        return $documents;
    }

    /**
     * Delete document
     */
    public function delete(string $id): void
    {
        $sql = "DELETE FROM documents WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
    }

    /**
     * Check if document exists
     */
    public function exists(string $id): bool
    {
        $sql = "SELECT COUNT(*) FROM documents WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get document with access control
     */
    public function findWithAccessControl(string $id, string $userId, string $userRole): ?Document
    {
        $document = $this->findById($id);
        if (!$document) {
            return null;
        }

        // Check access permissions
        if (!$this->hasAccess($document, $userId, $userRole)) {
            return null;
        }

        return $document;
    }

    /**
     * Check if user has access to document
     */
    public function hasAccess(Document $document, string $userId, string $userRole): bool
    {
        // Admin can access all documents
        if ($userRole === 'Administrator') {
            return true;
        }

        // Customer can only access their own documents
        if ($userRole === 'Customer') {
            return $document->getCustomerId() === $userId;
        }

        // Vendor can access documents for their orders
        if ($userRole === 'Vendor') {
            // Need to check if the order belongs to this vendor
            $orderRepo = new OrderRepository();
            $order = $orderRepo->findById($document->getOrderId());
            
            if (!$order) {
                return false;
            }

            // Get vendor ID from user ID
            $vendorRepo = new VendorRepository();
            $vendor = $vendorRepo->findByUserId($userId);
            
            return $vendor && $order->getVendorId() === $vendor->getId();
        }

        return false;
    }

    /**
     * Map database row to Document object
     */
    private function mapRowToDocument(array $row): Document
    {
        return new Document(
            $row['id'],
            $row['order_id'],
            $row['customer_id'],
            $row['document_type'],
            $row['file_name'] ?? basename($row['file_path']),
            $row['file_path'],
            (int)$row['file_size'],
            $row['mime_type'],
            $row['created_at']
        );
    }
}