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
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $parentId = !empty($data['parent_id']) ? $data['parent_id'] : null;

    // Validation
    if (empty($id)) {
        $response['message'] = 'Category ID is required';
        echo json_encode($response);
        exit;
    }

    if (empty($name)) {
        $response['message'] = 'Category name is required';
        echo json_encode($response);
        exit;
    }

    // Get existing category
    $category = $categoryRepo->findById($id);
    if (!$category) {
        $response['message'] = 'Category not found';
        echo json_encode($response);
        exit;
    }

    // Check if name is taken by another category
    $existingCategory = $categoryRepo->findByName($name);
    if ($existingCategory && $existingCategory->getId() !== $id) {
        $response['message'] = 'A category with this name already exists';
        echo json_encode($response);
        exit;
    }

    // Prevent setting self as parent
    if ($parentId === $id) {
        $response['message'] = 'A category cannot be its own parent';
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
        
        // Prevent circular references (parent cannot be a child of this category)
        $checkParent = $parentCategory;
        while ($checkParent->getParentId() !== null) {
            if ($checkParent->getParentId() === $id) {
                $response['message'] = 'Cannot create circular category hierarchy';
                echo json_encode($response);
                exit;
            }
            $checkParent = $categoryRepo->findById($checkParent->getParentId());
            if (!$checkParent) break;
        }
    }

    // Update category
    $category->setName($name);
    $category->setDescription($description);
    $category->setParentId($parentId);
    
    if ($categoryRepo->update($category)) {
        $response['success'] = true;
        $response['message'] = 'Category updated successfully';
        $response['data'] = $category->toArray();
    } else {
        $response['message'] = 'Failed to update category';
    }

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
