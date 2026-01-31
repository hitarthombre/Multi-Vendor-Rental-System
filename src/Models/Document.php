<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * Document Model
 * 
 * Represents an uploaded document for order verification
 */
class Document
{
    private string $id;
    private string $orderId;
    private string $customerId;
    private string $documentType;
    private string $fileName;
    private string $filePath;
    private int $fileSize;
    private string $mimeType;
    private string $createdAt;

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        string $orderId,
        string $customerId,
        string $documentType,
        string $fileName,
        string $filePath,
        int $fileSize,
        string $mimeType,
        string $createdAt = ''
    ) {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->customerId = $customerId;
        $this->documentType = $documentType;
        $this->fileName = $fileName;
        $this->filePath = $filePath;
        $this->fileSize = $fileSize;
        $this->mimeType = $mimeType;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
    }

    /**
     * Create a new document with generated ID
     */
    public static function create(
        string $orderId,
        string $customerId,
        string $documentType,
        string $fileName,
        string $filePath,
        int $fileSize,
        string $mimeType
    ): self {
        $id = UUID::generate();
        
        return new self(
            $id,
            $orderId,
            $customerId,
            $documentType,
            $fileName,
            $filePath,
            $fileSize,
            $mimeType
        );
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'customer_id' => $this->customerId,
            'document_type' => $this->documentType,
            'file_name' => $this->fileName,
            'file_path' => $this->filePath,
            'file_size' => $this->fileSize,
            'mime_type' => $this->mimeType,
            'created_at' => $this->createdAt
        ];
    }
}