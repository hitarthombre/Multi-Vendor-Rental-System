<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Database\Connection;

Session::start();
Middleware::requireAuthentication();
Middleware::requireRole('Vendor');

$db = Connection::getInstance();
$productRepo = new ProductRepository();
$vendorId = Session::getUserId();

// Get product ID from URL
$productId = $_GET['id'] ?? '';

if (empty($productId)) {
    header('Location: /Multi-Vendor-Rental-System/public/vendor/products.php');
    exit;
}

// Get product and verify ownership
$product = $productRepo->findById($productId);

if (!$product || !$product->belongsToVendor($vendorId)) {
    header('Location: /Multi-Vendor-Rental-System/public/vendor/products.php');
    exit;
}

$images = $product->getImages();

$pageTitle = 'Manage Images - ' . htmlspecialchars($product->getName());
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Manage Product Images</h1>
            <p class="mt-2 text-gray-600"><?= htmlspecialchars($product->getName()) ?></p>
        </div>
        <a href="/Multi-Vendor-Rental-System/public/vendor/products.php" 
           class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Products
        </a>
    </div>
</div>

<div x-data="imageManager()" class="space-y-6">
    <!-- Upload Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-cloud-upload-alt text-primary-600 mr-2"></i>Upload Images
        </h3>
        
        <!-- Drag and Drop Zone -->
        <div @drop.prevent="handleDrop($event)" 
             @dragover.prevent="dragOver = true"
             @dragleave.prevent="dragOver = false"
             :class="dragOver ? 'border-primary-500 bg-primary-50' : 'border-gray-300'"
             class="border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer"
             @click="$refs.fileInput.click()">
            <div class="space-y-3">
                <i class="fas fa-cloud-upload-alt text-5xl text-gray-400"></i>
                <div>
                    <p class="text-lg font-medium text-gray-700">Drop images here or click to browse</p>
                    <p class="text-sm text-gray-500 mt-1">Supports: JPEG, PNG, WebP (Max 5MB each)</p>
                </div>
            </div>
            <input type="file" 
                   x-ref="fileInput" 
                   @change="handleFileSelect($event)" 
                   accept="image/jpeg,image/png,image/jpg,image/webp" 
                   multiple 
                   class="hidden">
        </div>

        <!-- Upload Progress -->
        <div x-show="uploading" class="mt-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Uploading...</span>
                <span class="text-sm text-gray-500" x-text="`${uploadProgress}%`"></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" 
                     :style="`width: ${uploadProgress}%`"></div>
            </div>
        </div>

        <!-- Upload Messages -->
        <div x-show="uploadMessage" 
             :class="uploadSuccess ? 'bg-green-50 border-green-400 text-green-800' : 'bg-red-50 border-red-400 text-red-800'"
             class="mt-4 border-l-4 p-4 rounded">
            <p class="text-sm font-medium" x-text="uploadMessage"></p>
        </div>
    </div>

    <!-- Image Gallery -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-images text-primary-600 mr-2"></i>Image Gallery
                <span class="text-sm font-normal text-gray-500">(<span x-text="images.length"></span> images)</span>
            </h3>
            <button @click="saveOrder()" 
                    x-show="orderChanged"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-save mr-2"></i>Save Order
            </button>
        </div>

        <template x-if="images.length === 0">
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-image text-6xl mb-4 text-gray-300"></i>
                <p class="text-lg">No images uploaded yet</p>
                <p class="text-sm mt-2">Upload your first image to get started</p>
            </div>
        </template>

        <!-- Sortable Image Grid -->
        <div x-show="images.length > 0" 
             class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
             x-ref="imageGrid">
            <template x-for="(image, index) in images" :key="image.filename">
                <div class="relative group bg-gray-100 rounded-lg overflow-hidden aspect-square cursor-move"
                     :data-filename="image.filename"
                     draggable="true"
                     @dragstart="dragStart($event, index)"
                     @dragend="dragEnd($event)"
                     @dragover.prevent="dragOverImage($event, index)"
                     @drop.prevent="dropImage($event, index)">
                    
                    <!-- Image -->
                    <img :src="'/Multi-Vendor-Rental-System/public' + image.path" 
                         :alt="'Product image ' + (index + 1)"
                         class="w-full h-full object-cover">
                    
                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-200 flex items-center justify-center">
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 space-x-2">
                            <!-- View Button -->
                            <button @click="viewImage(image)" 
                                    class="px-3 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                                <i class="fas fa-eye"></i>
                            </button>
                            
                            <!-- Delete Button -->
                            <button @click="deleteImage(image.filename, index)" 
                                    class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Primary Badge -->
                    <div x-show="index === 0" 
                         class="absolute top-2 left-2 px-2 py-1 bg-primary-600 text-white text-xs font-medium rounded">
                        Primary
                    </div>
                    
                    <!-- Order Number -->
                    <div class="absolute bottom-2 right-2 w-8 h-8 bg-white rounded-full flex items-center justify-center text-sm font-bold text-gray-700 shadow">
                        <span x-text="index + 1"></span>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="images.length > 0" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Tip:</strong> Drag and drop images to reorder them. The first image will be used as the primary product image.
            </p>
        </div>
    </div>
</div>

<!-- Image View Modal -->
<div x-show="viewingImage" 
     x-cloak
     @click="viewingImage = null"
     class="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div @click.stop class="relative max-w-4xl max-h-[90vh]">
        <button @click="viewingImage = null" 
                class="absolute -top-10 right-0 text-white hover:text-gray-300">
            <i class="fas fa-times text-2xl"></i>
        </button>
        <img x-show="viewingImage" 
             :src="viewingImage ? '/Multi-Vendor-Rental-System/public' + viewingImage.path : ''" 
             class="max-w-full max-h-[90vh] rounded-lg shadow-2xl">
    </div>
</div>

<script>
function imageManager() {
    return {
        productId: '<?= $productId ?>',
        images: <?= json_encode($images) ?>,
        dragOver: false,
        uploading: false,
        uploadProgress: 0,
        uploadMessage: '',
        uploadSuccess: false,
        orderChanged: false,
        draggedIndex: null,
        viewingImage: null,

        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.uploadFiles(files);
        },

        handleDrop(event) {
            this.dragOver = false;
            const files = Array.from(event.dataTransfer.files).filter(file => 
                file.type.startsWith('image/')
            );
            this.uploadFiles(files);
        },

        async uploadFiles(files) {
            if (files.length === 0) return;

            this.uploading = true;
            this.uploadProgress = 0;
            this.uploadMessage = '';

            const totalFiles = files.length;
            let completed = 0;

            for (const file of files) {
                try {
                    const formData = new FormData();
                    formData.append('image', file);
                    formData.append('product_id', this.productId);

                    const response = await fetch('/Multi-Vendor-Rental-System/public/vendor/product-image-upload.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.images.push({
                            filename: result.data.filename,
                            thumbnail: result.data.thumbnail,
                            path: result.data.path,
                            thumbnail_path: result.data.thumbnail_path,
                            uploaded_at: new Date().toISOString()
                        });
                    } else {
                        console.error('Upload failed:', result.message);
                    }

                    completed++;
                    this.uploadProgress = Math.round((completed / totalFiles) * 100);

                } catch (error) {
                    console.error('Upload error:', error);
                }
            }

            this.uploading = false;
            this.uploadSuccess = true;
            this.uploadMessage = `Successfully uploaded ${completed} of ${totalFiles} images`;

            setTimeout(() => {
                this.uploadMessage = '';
            }, 5000);
        },

        async deleteImage(filename, index) {
            if (!confirm('Are you sure you want to delete this image?')) {
                return;
            }

            try {
                const response = await fetch('/Multi-Vendor-Rental-System/public/vendor/product-image-delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: this.productId,
                        filename: filename
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.images.splice(index, 1);
                    this.uploadSuccess = true;
                    this.uploadMessage = 'Image deleted successfully';
                } else {
                    this.uploadSuccess = false;
                    this.uploadMessage = result.message;
                }

                setTimeout(() => {
                    this.uploadMessage = '';
                }, 3000);

            } catch (error) {
                console.error('Delete error:', error);
                this.uploadSuccess = false;
                this.uploadMessage = 'Failed to delete image';
            }
        },

        dragStart(event, index) {
            this.draggedIndex = index;
            event.dataTransfer.effectAllowed = 'move';
        },

        dragEnd(event) {
            this.draggedIndex = null;
        },

        dragOverImage(event, index) {
            event.preventDefault();
        },

        dropImage(event, dropIndex) {
            if (this.draggedIndex === null || this.draggedIndex === dropIndex) {
                return;
            }

            const draggedImage = this.images[this.draggedIndex];
            this.images.splice(this.draggedIndex, 1);
            this.images.splice(dropIndex, 0, draggedImage);
            
            this.draggedIndex = null;
            this.orderChanged = true;
        },

        async saveOrder() {
            try {
                const imageOrder = this.images.map(img => img.filename);

                const response = await fetch('/Multi-Vendor-Rental-System/public/vendor/product-image-reorder.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: this.productId,
                        image_order: imageOrder
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.orderChanged = false;
                    this.uploadSuccess = true;
                    this.uploadMessage = 'Image order saved successfully';
                } else {
                    this.uploadSuccess = false;
                    this.uploadMessage = result.message;
                }

                setTimeout(() => {
                    this.uploadMessage = '';
                }, 3000);

            } catch (error) {
                console.error('Save order error:', error);
                this.uploadSuccess = false;
                this.uploadMessage = 'Failed to save image order';
            }
        },

        viewImage(image) {
            this.viewingImage = image;
        }
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>
