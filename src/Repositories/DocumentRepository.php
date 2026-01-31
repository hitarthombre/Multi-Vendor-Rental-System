<?php

namespace App\Repositories;

use App\Models\Document;

class DocumentRepository
{
    private $document;

    public function __construct()
    {
        $this->document = new Document();
    }

    /**
     * Upload and store a document
     */
    public function uploadDocument(array $documentData): string
    {
        return $this->document->create($documentData);
    }

    /**
     * Get all documents for an order
     */
    public function getOrderDocuments(string $orderId): array
    {
        return $this->document->getByOrderId($orderId);
    }

    /**
     * Get document by ID with access control
     */
    public function getDocument(string $documentId, string $userId, string $userRole): ?array
    {
        $document = $this->document->getWithDetails($documentId);
        
        if (!$document) {
            return null;
        }

        // Access control: customer can only see their own documents
        if ($userRole === 'Customer' && $document['customer_id'] !== $userId) {
            return null;
        }

        // Access control: vendor can only see documents for their orders
        if ($userRole === 'Vendor' && $document['vendor_id'] !== $userId) {
            return null;
        }

        // Admin can see all documents
        return $document;
    }

    /**
     * Get customer's documents
     */
    public function getCustomerDocuments(string $customerId): array
    {
        return $this->document->getByCustomerId($customerId);
    }

    /**
     * Delete document with access control
     */
    public function deleteDocument(string $documentId, string $userId, string $userRole): bool
    {
        $document = $this->document->getWithDetails($documentId);
        
        if (!$document) {
            return false;
        }

        // Only customer who uploaded or admin can delete
        if ($userRole === 'Customer' && $document['customer_id'] !== $userId) {
            return false;
        }

        if ($userRole === 'Vendor') {
            return false; // Vendors cannot delete documents
        }

        // Delete the file from filesystem
        if (file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }

        return $this->document->delete($documentId);
    }

    /**
     * Check if document exists and user has access
     */
    public function hasAccess(string $documentId, string $userId, string $userRole): bool
    {
        return $this->getDocument($documentId, $userId, $userRole) !== null;
    }
}