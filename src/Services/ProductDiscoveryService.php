<?php

namespace RentalPlatform\Services;

use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\CategoryRepository;

/**
 * Product Discovery Service
 * 
 * Handles product listing, filtering, and search functionality for customers
 */
class ProductDiscoveryService
{
    private ProductRepository $productRepository;
    private CategoryRepository $categoryRepository;

    public function __construct()
    {
        $this->productRepository = new ProductRepository();
        $this->categoryRepository = new CategoryRepository();
    }

    /**
     * Get products with filtering and pagination
     * 
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getProducts(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        $result = $this->productRepository->findWithFilters($filters, $perPage, $offset);
        
        return [
            'products' => $result['products'],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $result['total'],
                'total_pages' => ceil($result['total'] / $perPage),
                'has_next' => $page < ceil($result['total'] / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }

    /**
     * Search products by keyword
     * 
     * @param string $query
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function searchProducts(string $query, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $filters['search'] = $query;
        return $this->getProducts($filters, $page, $perPage);
    }

    /**
     * Get products by category
     * 
     * @param string $categoryId
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getProductsByCategory(string $categoryId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $filters['category_id'] = $categoryId;
        return $this->getProducts($filters, $page, $perPage);
    }

    /**
     * Get available filter options
     * 
     * @return array
     */
    public function getFilterOptions(): array
    {
        return $this->productRepository->getFilterOptions();
    }

    /**
     * Get category hierarchy for navigation
     * 
     * @return array
     */
    public function getCategoryHierarchy(): array
    {
        $rootCategories = $this->categoryRepository->findRootCategories();
        $hierarchy = [];
        
        foreach ($rootCategories as $category) {
            $categoryData = $category->toArray();
            $categoryData['subcategories'] = [];
            
            $subcategories = $this->categoryRepository->findByParentId($category->getId());
            foreach ($subcategories as $subcategory) {
                $categoryData['subcategories'][] = $subcategory->toArray();
            }
            
            $hierarchy[] = $categoryData;
        }
        
        return $hierarchy;
    }

    /**
     * Get product details with availability indicators
     * 
     * @param string $productId
     * @return array|null
     */
    public function getProductDetails(string $productId): ?array
    {
        $product = $this->productRepository->findById($productId);
        
        if (!$product || !$product->isActive()) {
            return null;
        }
        
        $productData = $product->toArray();
        
        // Add category information
        if ($product->getCategoryId()) {
            $category = $this->categoryRepository->findById($product->getCategoryId());
            $productData['category'] = $category ? $category->toArray() : null;
        }
        
        // Add availability indicator (simplified - would need inventory checking in full implementation)
        $productData['availability'] = [
            'status' => 'available',
            'message' => 'Available for rental'
        ];
        
        return $productData;
    }

    /**
     * Get featured/recommended products
     * 
     * @param int $limit
     * @return array
     */
    public function getFeaturedProducts(int $limit = 8): array
    {
        // For now, return recent active products
        $result = $this->productRepository->findWithFilters([], $limit, 0);
        return $result['products'];
    }

    /**
     * Get products from the same category (for "related products")
     * 
     * @param string $productId
     * @param int $limit
     * @return array
     */
    public function getRelatedProducts(string $productId, int $limit = 4): array
    {
        $product = $this->productRepository->findById($productId);
        
        if (!$product || !$product->getCategoryId()) {
            return [];
        }
        
        $result = $this->productRepository->findWithFilters([
            'category_id' => $product->getCategoryId()
        ], $limit + 1, 0);
        
        // Remove the current product from results
        $relatedProducts = array_filter($result['products'], function($p) use ($productId) {
            return $p->getId() !== $productId;
        });
        
        // Return only the requested limit
        return array_slice($relatedProducts, 0, $limit);
    }
}