<?php
require_once __DIR__ . '/../../src/Auth/Session.php';
require_once __DIR__ . '/../../src/Services/DocumentUploadService.php';
require_once __DIR__ . '/../../src/Repositories/OrderRepository.php';

use App\Auth\Session;
use App\Services\DocumentUploadService;
use App\Repositories\OrderRepository;

// Check authentication
if (!Session::isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$user = Session::getCurrentUser();

// Only customers can access this page
if ($user['role'] !== 'Customer') {
    header('Location: /dashboard.php');
    exit;
}

$orderId = $_GET['order_id'] ?? null;
if (!$orderId) {
    header('Location: /customer/dashboard.php');
    exit;
}

// Verify order belongs to customer
$orderRepository = new OrderRepository();
$order = $orderRepository->getById($orderId);

if (!$order || $order['customer_id'] !== $user['id']) {
    header('Location: /customer/dashboard.php');
    exit;
}

$documentService = new DocumentUploadService();
$existingDocuments = $documentService->getOrderDocuments($orderId);

$pageTitle = 'Upload Documents - Order #' . $order['order_number'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .upload-area {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }
        .upload-area.dragover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
        .progress-bar {
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../components/modern-navigation.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Upload Documents</h1>
                    <p class="text-gray-600 mt-2">Order #<?= htmlspecialchars($order['order_number']) ?></p>
                </div>
                <a href="/customer/dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                    Back to Dashboard
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Upload Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Upload New Document</h2>
                
                <form id="uploadForm" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($orderId) ?>">
                    
                    <!-- Document Type -->
                    <div>
                        <label for="document_type"