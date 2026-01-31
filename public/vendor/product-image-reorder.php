<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Database\Connection;

Session::start();
Middleware::requireAuthentication();
Middleware::requireRole('Vendor');

header('Content-Type: application/json');

$db = Connection::getInstance();
$productRepo = new ProductRepository();

$response = ['success' => false, 'message' => ''];

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    $productId = $data['product_id'] ?? '';
    $imageOrder = $data['image_order'] ?? [];

    if (empty($productId) || empty($imageOrder)) {
        $response['message'] = 'Product ID and image order are required';
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

    // Get product
    $product = $productRepo->findById($productId);
    if (!$product) {
        $response['message'] = 'Product not found';
        echo json_encode($response);
        exit;
    }

    $currentImages = $product->getImages();
    $reorderedImages = [];

    // Reorder images based on provided order
    foreach ($imageOrder as $filename) {
        foreach ($currentImages as $img) {
            if ($img['filename'] === $filename) {
                $reorderedImages[] = $img;
                break;
            }
        }
    }

    // Add any images not in the order array (safety check)
    foreach ($currentImages as $img) {
        $found = false;
        foreach ($reorderedImages as $reordered) {
            if ($reordered['filename'] === $img['filename']) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $reorderedImages[] = $img;
        }
    }

    $product->setImages($reorderedImages);
    $productRepo->update($product);

    $response['success'] = true;
    $response['message'] = 'Images reordered successfully';

} catch (Exception $e) {
    $response['message'] = 'Reorder failed: ' . $e->getMessage();
}

echo json_encode($response);
