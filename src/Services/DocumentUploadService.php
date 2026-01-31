<?php

namespace App\Services;

use App\Repositories\DocumentRepository;

class DocumentUploadService
{
    private $documentRepository;
    private $uploadPath;
    private $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    private $maxFileSize = 10 * 1024 * 1024; // 10MB

    public function __construct()
    {
        $this->documentRepository = new DocumentRepository();
        $this->uploadPath = __DIR__ . '/../../uploads/documents/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Upload a document file
     */
    public function uploadDocument(array $fileData, string $orderId, string $customerId, string $documentType): array
    {
        // Validate file
        $validation = $this->validateFile($fileData);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error']
            ];
        }

        try {
            // Generate unique filename
            $extension = $this->getFileExtension($fileData['type']);
            $filename = uniqid('doc_') . '_' . time() . '.' . $extension;
            $filePath = $this->uploadPath . $filename;

            // Move uploaded file
            if (!move_uploaded_file($fileData['tmp_name'], $filePath)) {
                return [
                    'success' => false,
                    'error' => 'Failed to save file'
                ];
            }

            // Store document record in database
            $documentId = $this->documentRepository->uploadDocument([
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'document_type' => $documentType,
                'file_path' => $filePath,
                'file_size' => $fileData['size'],
                'mime_type' => $fileData['type']
            ]);

            return [
                'success' => true,
                'document_id' => $documentId,
                'filename' => $filename
            ];

        } catch (Exception $e) {
            // Clean up file if database insert failed
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }

            return [
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(array $fileData): array
    {
        // Check for upload errors
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'error' => $this->getUploadErrorMessage($fileData['error'])
            ];
        }

        // Check file size
        if ($fileData['size'] > $this->maxFileSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum limit of 10MB'
            ];
        }

        // Check file type
        if (!in_array($fileData['type'], $this->allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type. Only PDF, JPG, and PNG files are allowed'
            ];
        }

        // Additional security check - verify file content matches MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actualMimeType = finfo_file($finfo, $fileData['tmp_name']);
        finfo_close($finfo);

        if ($actualMimeType !== $fileData['type']) {
            return [
                'valid' => false,
                'error' => 'File content does not match declared type'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get file extension from MIME type
     */
    private function getFileExtension(string $mimeType): string
    {
        $extensions = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png'
        ];

        return $extensions[$mimeType] ?? 'bin';
    }

    /**
     * Get human-readable upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];

        return $errors[$errorCode] ?? 'Unknown upload error';
    }

    /**
     * Get document for download
     */
    public function getDocumentForDownload(string $documentId, string $userId, string $userRole): ?array
    {
        return $this->documentRepository->getDocument($documentId, $userId, $userRole);
    }

    /**
     * Get documents for an order
     */
    public function getOrderDocuments(string $orderId): array
    {
        return $this->documentRepository->getOrderDocuments($orderId);
    }
}