<?php

require_once __DIR__ . '/../../src/Auth/Session.php';
require_once __DIR__ . '/../../src/Services/DocumentUploadService.php';

use App\Auth\Session;
use App\Services\DocumentUploadService;

header('Content-Type: application/json');

// Check authentication
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$user = Session::getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];

try {
    $documentService = new DocumentUploadService();

    switch ($method) {
        case 'POST':
            // Upload document
            if (!isset($_FILES['document']) || !isset($_POST['order_id']) || !isset($_POST['document_type'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }

            // Only customers can upload documents
            if ($user['role'] !== 'Customer') {
                http_response_code(403);
                echo json_encode(['error' => 'Only customers can upload documents']);
                exit;
            }

            $result = $documentService->uploadDocument(
                $_FILES['document'],
                $_POST['order_id'],
                $user['id'],
                $_POST['document_type']
            );

            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'document_id' => $result['document_id'],
                    'message' => 'Document uploaded successfully'
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => $result['error']]);
            }
            break;

        case 'GET':
            // Get documents for an order or download a specific document
            if (isset($_GET['order_id'])) {
                // Get all documents for an order
                $documents = $documentService->getOrderDocuments($_GET['order_id']);
                echo json_encode(['documents' => $documents]);
            } elseif (isset($_GET['document_id'])) {
                // Download specific document
                $document = $documentService->getDocumentForDownload(
                    $_GET['document_id'],
                    $user['id'],
                    $user['role']
                );

                if (!$document) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Document not found or access denied']);
                    exit;
                }

                // Serve file for download
                if (file_exists($document['file_path'])) {
                    header('Content-Type: ' . $document['mime_type']);
                    header('Content-Disposition: attachment; filename="' . basename($document['file_path']) . '"');
                    header('Content-Length: ' . filesize($document['file_path']));
                    readfile($document['file_path']);
                    exit;
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'File not found']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Missing order_id or document_id parameter']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>