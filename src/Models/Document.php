<?php

namespace App\Models;

use App\Database\Connection;
use App\Helpers\UUID;
use PDO;

class Document
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    /**
     * Create a new document record
     */
    public function create(array $data): string
    {
        $id = UUID::generate();
        
        $sql = "INSERT INTO documents (
            id, order_id, customer_id, document_type, 
            file_path, file_size, mime_type, uploaded_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $id,
            $data['order_id'],
            $data['customer_id'],
            $data['document_type'],
            $data['file_path'],
            $data['file_size'],
            $data['mime_type']
        ]);
        
        return $id;
    }

    /**
     * Get documents by order ID
     */
    public function getByOrderId(string $orderId): array
    {
        $sql = "SELECT * FROM documents WHERE order_id = ? ORDER BY uploaded_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get document by ID
     */
    public function getById(string $id): ?array
    {
        $sql = "SELECT * FROM documents WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get documents by customer ID
     */
    public function getByCustomerId(string $customerId): array
    {
        $sql = "SELECT * FROM documents WHERE customer_id = ? ORDER BY uploaded_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete document by ID
     */
    public function delete(string $id): bool
    {
        $sql = "DELETE FROM documents WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([$id]);
    }

    /**
     * Check if document exists
     */
    public function exists(string $id): bool
    {
        $sql = "SELECT COUNT(*) FROM documents WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get document with order and customer details
     */
    public function getWithDetails(string $id): ?array
    {
        $sql = "SELECT d.*, o.order_number, o.vendor_id, u.username as customer_username
                FROM documents d
                JOIN orders o ON d.order_id = o.id
                JOIN users u ON d.customer_id = u.id
                WHERE d.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}