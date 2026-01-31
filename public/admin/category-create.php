<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Repositories\CategoryRepository;
use RentalPlatform\Models\Category;

Session::start();
Middleware::requireAdministrator();

header('Content-Type: application/json');

$categoryRepo = new CategoryRepository();
$response = ['success' => false, 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $parentId = !empty($data['parent_id']) ? $data['parent_id'] : null;

    // Validation
    if (empty($name)) {
        $response['message'] = 'Category name is required';
        echo json_encode($response);
        exit;
    }

    // Check if category name already exists
    if ($categoryRepo->findByName($name)) {
        $response['message'] = 'A category with this name already exists';
        echo json_encode($response);
        exit;
    }

    // Validate parent category exists if provided
    if ($parentId !== null) {
        $parentCategory = $categoryRepo->findById($parentId);
        if (!$parentCategory) {
            $response['message'] = 'Parent category not found';
            echo json_encode($response);
            exit;
        }
    }

    // Create category
    $category = Category::create($name, $description, $parentId);
    
    if ($categoryRepo->create($category)) {
        $response['success'] = true;
        $response['message'] = 'Category created successfully';
        $response['data'] = $category->toArray();
    } else {
        $response['message'] = 'Failed to create category';
    }

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
