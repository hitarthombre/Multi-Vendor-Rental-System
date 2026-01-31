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

$response = ['success' => false, 'message' => ''];

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    $productId = $data['product_id'] ?? '';
    $filename = $data['filename'] ?? '';

    if (empty($productId) || empty($filename)) {
        $response['message'] = 'Product ID and filename are required';
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

    // Get product and remove image from array
    $product = $productRepo->findById($productId);
    if (!$product) {
        $response['message'] = 'Product not found';
        echo json_encode($response);
        exit;
    }

    $images = $product->getImages();
    $updatedImages = array_filter($images, function($img) use ($filename) {
        return $img['filename'] !== $filename;
    });

    // Re-index array
    $updatedImages = array_values($updatedImages);

    $product->setImages($updatedImages);
    $productRepo->update($product);

    // Delete physical files
    $imageService->deleteImage($filename);

    $response['success'] = true;
    $response['message'] = 'Image deleted successfully';

} catch (Exception $e) {
    $response['message'] = 'Delete failed: ' . $e->getMessage();
}

echo json_encode($response);
