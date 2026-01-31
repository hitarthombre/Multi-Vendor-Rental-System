<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\VendorRepository;

Session::start();
Middleware::requireRole(User::ROLE_VENDOR);

$userId = Session::getUserId();

// Get vendor profile
$vendorRepo = new VendorRepository();
$vendor = $vendorRepo->findByUserId($userId);

if (!$vendor) {
    die('Vendor profile not found. Please contact support.');
}

$pageTitle = 'Branding Settings';
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Branding Settings</h1>
            <p class="mt-2 text-gray-600">Customize your brand appearance across the platform</p>
        </div>
        <div class="flex items-center space-x-4">
            <a href="/Multi-Vendor-Rental-System/public/vendor/dashboard.php" 
               class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8" x-data="brandingSettings()">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Brand Color Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Brand Color</h2>
            <p class="text-gray-600 mb-6">Choose a primary color that represents your brand. This color will be used in your dashboard and invoices.</p>
            
            <div class="space-y-4">
                <div>
                    <label for="brandColor" class="block text-sm font-medium text-gray-700 mb-2">
                        Primary Brand Color
                    </label>
                    <div class="flex items-center space-x-4">
                        <input type="color" 
                               id="brandColor" 
                               x-model="brandColor"
                               class="w-16 h-16 border border-gray-300 rounded-lg cursor-pointer">
                        <div class="flex-1">
                            <input type="text" 
                                   x-model="brandColor"
                                   placeholder="#3b82f6"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Enter a hex color code (e.g., #3b82f6)</p>
                        </div>
                    </div>
                </div>
                
                <!-- Color Preview -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Preview</label>
                    <div class="flex items-center space-x-4">
                        <button :style="`background-color: ${brandColor}`" 
                                class="px-4 py-2 text-white rounded-lg font-medium">
                            Sample Button
                        </button>
                        <div :style="`background-color: ${brandColor}20; border-left: 4px solid ${brandColor}`" 
                             class="px-4 py-2 rounded">
                            <span class="text-sm font-medium" :style="`color: ${brandColor}`">Sample Alert</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button @click="updateBrandColor()" 
                            :disabled="saving"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                        <span x-show="!saving">Update Color</span>
                        <span x-show="saving">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Updating...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Logo Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Business Logo</h2>
            <p class="text-gray-600 mb-6">Upload your business logo. It will appear in your dashboard header and on invoices.</p>
            
            <div class="space-y-6">
                <!-- Current Logo -->
                <div x-show="currentLogo">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Logo</label>
                    <div class="flex items-center space-x-4">
                        <div class="w-24 h-24 border border-gray-300 rounded-lg flex items-center justify-center bg-gray-50">
                            <img x-show="currentLogo" 
                                 :src="currentLogo" 
                                 alt="Current Logo" 
                                 class="max-w-full max-h-full object-contain">
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 mb-2">Current logo</p>
                            <button @click="removeLogo()" 
                                    class="text-red-600 hover:text-red-700 text-sm">
                                <i class="fas fa-trash mr-1"></i>Remove Logo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upload New Logo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <span x-text="currentLogo ? 'Replace Logo' : 'Upload Logo'"></span>
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                        <input type="file" 
                               id="logoUpload" 
                               accept="image/*" 
                               @change="handleLogoUpload($event)"
                               class="hidden">
                        <label for="logoUpload" class="cursor-pointer">
                            <div class="space-y-2">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
                                <p class="text-gray-600">Click to upload or drag and drop</p>
                                <p class="text-xs text-gray-500">PNG, JPG, WebP up to 5MB</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Upload Progress -->
                <div x-show="uploading" class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Uploading logo...</span>
                        <span class="text-gray-600" x-text="uploadProgress + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                             :style="`width: ${uploadProgress}%`"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Branding Guidelines -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Branding Guidelines</h3>
            <div class="space-y-4 text-sm">
                <div>
                    <h4 class="font-medium text-gray-900 mb-1">Brand Color</h4>
                    <p class="text-gray-600">Choose a color that represents your brand identity. It will be used for buttons, highlights, and accents.</p>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 mb-1">Logo Requirements</h4>
                    <ul class="text-gray-600 space-y-1">
                        <li>• Maximum file size: 5MB</li>
                        <li>• Supported formats: PNG, JPG, WebP</li>
                        <li>• Recommended size: 300x300px</li>
                        <li>• Use transparent background for PNG</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 mb-1">Where It Appears</h4>
                    <ul class="text-gray-600 space-y-1">
                        <li>• Vendor dashboard header</li>
                        <li>• Invoice documents</li>
                        <li>• Email notifications</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="/Multi-Vendor-Rental-System/public/vendor/dashboard.php" 
                   class="block w-full text-center bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-tachometer-alt mr-2"></i>View Dashboard
                </a>
                <button @click="previewBranding()" 
                        class="block w-full text-center bg-blue-100 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-200 transition-colors">
                    <i class="fas fa-eye mr-2"></i>Preview Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div x-show="message" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed top-4 right-4 z-50"
         style="display: none;">
        <div :class="messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'"
             class="border px-4 py-3 rounded-md shadow-md">
            <div class="flex items-center">
                <i :class="messageType === 'success' ? 'fas fa-check-circle text-green-500' : 'fas fa-exclamation-circle text-red-500'" 
                   class="mr-2"></i>
                <span x-text="message"></span>
            </div>
        </div>
    </div>
</div>

<script>
function brandingSettings() {
    return {
        brandColor: '<?= htmlspecialchars($vendor->getBrandColor() ?? '#3b82f6') ?>',
        currentLogo: '<?= $vendor->getLogo() ? '/Multi-Vendor-Rental-System/public' . htmlspecialchars($vendor->getLogo()) : '' ?>',
        saving: false,
        uploading: false,
        uploadProgress: 0,
        message: '',
        messageType: 'success',

        init() {
            // Load current branding settings
            this.loadBrandingSettings();
        },

        async loadBrandingSettings() {
            try {
                const response = await fetch('/Multi-Vendor-Rental-System/public/api/vendor-branding.php?action=get_branding');
                const data = await response.json();
                
                if (data.success) {
                    this.brandColor = data.data.brand_color || '#3b82f6';
                    this.currentLogo = data.data.logo ? '/Multi-Vendor-Rental-System/public' + data.data.logo : '';
                }
            } catch (error) {
                console.error('Error loading branding settings:', error);
            }
        },

        async updateBrandColor() {
            this.saving = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'update_brand_color');
                formData.append('brand_color', this.brandColor);

                const response = await fetch('/Multi-Vendor-Rental-System/public/api/vendor-branding.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (data.success) {
                    this.showMessage('Brand color updated successfully!', 'success');
                } else {
                    this.showMessage('Failed to update brand color: ' + data.error, 'error');
                }
            } catch (error) {
                this.showMessage('Error updating brand color: ' + error.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async handleLogoUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.uploading = true;
            this.uploadProgress = 0;

            try {
                const formData = new FormData();
                formData.append('action', 'upload_logo');
                formData.append('logo', file);

                const xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                    }
                });

                xhr.onload = () => {
                    if (xhr.status === 200) {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            this.currentLogo = '/Multi-Vendor-Rental-System/public' + data.data.logo;
                            this.showMessage('Logo uploaded successfully!', 'success');
                        } else {
                            this.showMessage('Failed to upload logo: ' + data.error, 'error');
                        }
                    } else {
                        this.showMessage('Upload failed. Please try again.', 'error');
                    }
                    this.uploading = false;
                };

                xhr.onerror = () => {
                    this.showMessage('Upload failed. Please try again.', 'error');
                    this.uploading = false;
                };

                xhr.open('POST', '/Multi-Vendor-Rental-System/public/api/vendor-branding.php');
                xhr.send(formData);

            } catch (error) {
                this.showMessage('Error uploading logo: ' + error.message, 'error');
                this.uploading = false;
            }

            // Reset file input
            event.target.value = '';
        },

        async removeLogo() {
            if (!confirm('Are you sure you want to remove your logo?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'remove_logo');

                const response = await fetch('/Multi-Vendor-Rental-System/public/api/vendor-branding.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (data.success) {
                    this.currentLogo = '';
                    this.showMessage('Logo removed successfully!', 'success');
                } else {
                    this.showMessage('Failed to remove logo: ' + data.error, 'error');
                }
            } catch (error) {
                this.showMessage('Error removing logo: ' + error.message, 'error');
            }
        },

        previewBranding() {
            // Open dashboard in new tab to preview changes
            window.open('/Multi-Vendor-Rental-System/public/vendor/dashboard.php', '_blank');
        },

        showMessage(text, type = 'success') {
            this.message = text;
            this.messageType = type;
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                this.message = '';
            }, 5000);
        }
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/base.php';
?>