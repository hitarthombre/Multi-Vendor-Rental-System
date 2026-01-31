<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Auth\UnauthorizedException;
use RentalPlatform\Repositories\CategoryRepository;

Session::start();

// Debug: Check session before authentication
error_log("Categories page - Session authenticated: " . (Session::isAuthenticated() ? "YES" : "NO"));
error_log("Categories page - User ID: " . (Session::getUserId() ?? "NULL"));
error_log("Categories page - Role: " . (Session::getRole() ?? "NULL"));

try {
    Middleware::requireAdministrator();
} catch (UnauthorizedException $e) {
    // Log the error
    error_log("Categories page - Auth failed: " . $e->getMessage());
    // Redirect to login if not authenticated
    header('Location: /Multi-Vendor-Rental-System/public/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$categoryRepo = new CategoryRepository();

// Get all categories
$allCategories = $categoryRepo->findAll();
$rootCategories = $categoryRepo->findRootCategories();

// Build category tree
function buildCategoryTree($categories, $parentId = null) {
    $tree = [];
    foreach ($categories as $category) {
        if ($category->getParentId() === $parentId) {
            $tree[] = [
                'category' => $category,
                'children' => buildCategoryTree($categories, $category->getId())
            ];
        }
    }
    return $tree;
}

$categoryTree = buildCategoryTree($allCategories, null);

$pageTitle = 'Category Management';
$showNav = true;
$showContainer = true;
$showFooter = true;

ob_start();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold">Category Management</h1>
            <p class="text-muted-foreground mt-2">Organize products into categories and subcategories</p>
        </div>
        <button onclick="openCreateModal()" 
                class="btn-modern btn-primary">
            <i class="fas fa-plus mr-2"></i>Create Category
        </button>
    </div>
    
    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">Total Categories</p>
                    <p class="text-3xl font-bold mt-2"><?= count($allCategories) ?></p>
                </div>
                <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-folder text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-muted-foreground">Root Categories</p>
                    <p class="text-3xl font-bold mt-2"><?= count($rootCategories) ?></p>
                </div>
                <div class="h-12 w-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-folder-open text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Subcategories</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?= count($allCategories) - count($rootCategories) ?></p>
                </div>
                <div class="w-14 h-14 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-sitemap text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Tree -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-sitemap text-blue-600 mr-2"></i>Category Hierarchy
        </h3>

        <?php if (empty($categoryTree)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-folder-open text-6xl mb-4 text-gray-300"></i>
                <p class="text-lg">No categories yet</p>
                <p class="text-sm mt-2">Create your first category to get started</p>
            </div>
        <?php else: ?>
            <div class="space-y-2">
                <?php
                function renderCategoryTree($tree, $level = 0) {
                    foreach ($tree as $node) {
                        $category = $node['category'];
                        $hasChildren = !empty($node['children']);
                        $indent = $level * 2;
                        $categoryId = $category->getId();
                        ?>
                        <div class="group hover:bg-gray-50 rounded-lg transition-colors" 
                             style="padding-left: <?= $indent ?>rem">
                            <div class="flex items-center justify-between p-3">
                                <div class="flex items-center space-x-3 flex-1">
                                    <?php if ($hasChildren): ?>
                                        <button onclick="toggleCategory('<?= $categoryId ?>')" 
                                                class="text-gray-400 hover:text-gray-600">
                                            <i id="chevron-<?= $categoryId ?>" class="fas fa-chevron-right transition-transform"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="w-4"></span>
                                    <?php endif; ?>
                                    
                                    <i class="fas <?= $hasChildren ? 'fa-folder' : 'fa-folder-open' ?> text-blue-600"></i>
                                    
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900"><?= htmlspecialchars($category->getName()) ?></h4>
                                        <?php if ($category->getDescription()): ?>
                                            <p class="text-sm text-gray-500"><?= htmlspecialchars($category->getDescription()) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button onclick='openEditModal(<?= json_encode([
                                        "id" => $category->getId(),
                                        "name" => $category->getName(),
                                        "description" => $category->getDescription(),
                                        "parent_id" => $category->getParentId()
                                    ]) ?>)' 
                                            class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </button>
                                    <button onclick="deleteCategory('<?= $categoryId ?>', '<?= htmlspecialchars($category->getName(), ENT_QUOTES) ?>')" 
                                            class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </div>
                            </div>
                            
                            <?php if ($hasChildren): ?>
                                <div id="children-<?= $categoryId ?>" style="display: none;" class="ml-4">
                                    <?php renderCategoryTree($node['children'], $level + 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                }
                renderCategoryTree($categoryTree);
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="categoryModal" style="display: none;" 
     onclick="if(event.target === this) closeModal()"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div onclick="event.stopPropagation()" 
         class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-900">Create Category</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <form id="categoryForm" onsubmit="saveCategory(event)" class="p-6 space-y-4">
            <input type="hidden" id="categoryId" name="id">
            
            <div>
                <label for="categoryName" class="block text-sm font-medium text-gray-700 mb-1">
                    Category Name <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="categoryName" 
                       name="name"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="e.g., Electronics, Furniture">
            </div>
            
            <div>
                <label for="categoryDescription" class="block text-sm font-medium text-gray-700 mb-1">
                    Description
                </label>
                <textarea id="categoryDescription" 
                          name="description"
                          rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Brief description of this category"></textarea>
            </div>
            
            <div>
                <label for="parentCategory" class="block text-sm font-medium text-gray-700 mb-1">
                    Parent Category
                </label>
                <select id="parentCategory" 
                        name="parent_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">None (Root Category)</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= $cat->getId() ?>"><?= htmlspecialchars($cat->getName()) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-xs text-gray-500">Select a parent to create a subcategory</p>
            </div>
            
            <!-- Messages -->
            <div id="formMessage" style="display: none;" class="border-l-4 p-4 rounded">
                <p id="messageText" class="text-sm font-medium"></p>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" 
                        onclick="closeModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        id="submitBtn"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <span id="submitBtnText">Create Category</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let editMode = false;
let expandedCategories = [];

function toggleCategory(categoryId) {
    const childrenDiv = document.getElementById('children-' + categoryId);
    const chevron = document.getElementById('chevron-' + categoryId);
    
    if (childrenDiv.style.display === 'none') {
        childrenDiv.style.display = 'block';
        chevron.classList.add('rotate-90');
        expandedCategories.push(categoryId);
    } else {
        childrenDiv.style.display = 'none';
        chevron.classList.remove('rotate-90');
        expandedCategories = expandedCategories.filter(id => id !== categoryId);
    }
}

function openCreateModal() {
    editMode = false;
    document.getElementById('modalTitle').textContent = 'Create Category';
    document.getElementById('submitBtnText').textContent = 'Create Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    hideMessage();
    document.getElementById('categoryModal').style.display = 'flex';
}

function openEditModal(data) {
    editMode = true;
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('submitBtnText').textContent = 'Update Category';
    document.getElementById('categoryId').value = data.id;
    document.getElementById('categoryName').value = data.name;
    document.getElementById('categoryDescription').value = data.description || '';
    document.getElementById('parentCategory').value = data.parent_id || '';
    hideMessage();
    document.getElementById('categoryModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

function showMessage(message, type) {
    const messageDiv = document.getElementById('formMessage');
    const messageText = document.getElementById('messageText');
    
    messageText.textContent = message;
    messageDiv.className = 'border-l-4 p-4 rounded ';
    
    if (type === 'success') {
        messageDiv.className += 'bg-green-50 border-green-400 text-green-800';
    } else {
        messageDiv.className += 'bg-red-50 border-red-400 text-red-800';
    }
    
    messageDiv.style.display = 'block';
}

function hideMessage() {
    document.getElementById('formMessage').style.display = 'none';
}

async function saveCategory(event) {
    event.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const originalText = submitBtnText.textContent;
    
    submitBtn.disabled = true;
    submitBtnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    hideMessage();
    
    const formData = {
        id: document.getElementById('categoryId').value,
        name: document.getElementById('categoryName').value,
        description: document.getElementById('categoryDescription').value,
        parent_id: document.getElementById('parentCategory').value
    };
    
    try {
        const url = editMode 
            ? '/Multi-Vendor-Rental-System/public/admin/category-update.php'
            : '/Multi-Vendor-Rental-System/public/admin/category-create.php';
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(result.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showMessage(result.message, 'error');
            submitBtn.disabled = false;
            submitBtnText.textContent = originalText;
        }
    } catch (error) {
        showMessage('An error occurred. Please try again.', 'error');
        submitBtn.disabled = false;
        submitBtnText.textContent = originalText;
    }
}

async function deleteCategory(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"?\n\nThis will also delete all subcategories and unassign products.`)) {
        return;
    }
    
    try {
        const response = await fetch('/Multi-Vendor-Rental-System/public/admin/category-delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toastManager.success(result.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            toastManager.error(result.message);
        }
    } catch (error) {
        toastManager.error('An error occurred. Please try again.');
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/modern-base.php';
?>
