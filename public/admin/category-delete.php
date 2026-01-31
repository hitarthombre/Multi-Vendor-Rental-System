<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Repositories\CategoryRepository;

Session::start();
Middleware::requireAdministrator();

header('Content-Type: application/json');

$categoryRepo = new CategoryRepository();
$response = ['success' => false, 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'] ?? '';

    if (empty($id)) {
        $response['message'] = 'Category ID is required';
        echo json_encode($response);
        exit;
    }

    // Get category
    $category = $categoryRepo->findById($id);
    if (!$category) {
        $response['message'] = 'Category not found';
        echo json_encode($response);
        exit;
    }

    // Check if category has subcategories
    if ($categoryRepo->hasSubcategories($id)) {
        $response['message'] = 'Cannot delete category with subcategories. Please delete or move subcategories first.';
        echo json_encode($response);
        exit;
    }

    // Check if category has products
    if ($categoryRepo->hasProducts($id)) {
        $response['message'] = 'Cannot delete category with products. Please reassign or delete products first.';
        echo json_encode($response);
        exit;
    }

    // Delete category
    if ($categoryRepo->delete($id)) {
        $response['success'] = true;
        $response['message'] = 'Category deleted successfully';
    } else {
        $response['message'] = 'Failed to delete category';
    }

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
