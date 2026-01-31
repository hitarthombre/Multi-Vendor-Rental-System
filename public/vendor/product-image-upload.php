<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Services\ImageUploadService;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Database\Connection;

Session::start();
Middleware::requireAuthentication();
Middleware::requireRole('Vendor');

header('Content-Type: application/json');

$db = Connection::getInstance();
$productRepo = new ProductRepository();
$imageService = new ImageUploadService();

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Check if file was uploaded
    if (!isset($_FILES['image'])) {
        $response['message'] = 'No image file provided';
        echo json_encode($response);
        exit;
    }

    // Get product ID
    $productId = $_POST['product_id'] ?? '';
    if (empty($productId)) {
        $response['message'] = 'Product ID is required';
        echo json_encode($response);
        exit;
    }

    // Verify product belongs to vendor
    $vendorId = Session::getUserId();
    if (!$productRepo->belongsToVendor($productId, $vendorId)) {
        $response['message'] = 'Unauthorized: Product does not belong to you';
        echo json_encode($response);
        exit;
    }

    // Upload image
    $uploadResult = $imageService->upload($_FILES['image']);
    
    if (!$uploadResult['success']) {
        $response['message'] = $uploadResult['error'];
        echo json_encode($response);
        exit;
    }

    // Get product and update images
    $product = $productRepo->findById($productId);
    if (!$product) {
        $response['message'] = 'Product not found';
        echo json_encode($response);
        exit;
    }

    $images = $product->getImages();
    $images[] = [
        'filename' => $uploadResult['filename'],
        'thumbnail' => $uploadResult['thumbnail'],
        'path' => $uploadResult['path'],
        'thumbnail_path' => $uploadResult['thumbnail_path'],
        'uploaded_at' => date('Y-m-d H:i:s')
    ];

    $product->setImages($images);
    $productRepo->update($product);

    $response['success'] = true;
    $response['message'] = 'Image uploaded successfully';
    $response['data'] = [
        'filename' => $uploadResult['filename'],
        'thumbnail' => $uploadResult['thumbnail'],
        'path' => $uploadResult['path'],
        'thumbnail_path' => $uploadResult['thumbnail_path']
    ];

} catch (Exception $e) {
    $response['message'] = 'Upload failed: ' . $e->getMessage();
}

echo json_encode($response);
