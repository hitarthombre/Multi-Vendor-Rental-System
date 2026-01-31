<?php
session_start();

// For demo purposes, use hardcoded vendor ID
// In a real application, this would come from the session
$vendorId = 'demo-vendor-456';
$vendorName = 'Demo Vendor';

$orderId = $_GET['id'] ?? '';
if (empty($orderId)) {
    header('Location: approval-queue.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Vendor Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-xl font-semibold text-gray-900">Vendor Dashboard</h1>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="dashboard.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="approval-queue.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                            Approval Queue
                        </a>
                        <a href="active-rentals.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                            Active Rentals
                        </a>
                        <a href="orders.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                            All Orders
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-700 mr-4">Welcome, <?php echo htmlspecialchars($vendorName); ?></span>
                    <a href="../logout.php" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" x-data="orderDetails('<?php echo htmlspecialchars($orderId); ?>')">
        <!-- Back Button -->
        <div class="px-4 mb-6 sm:px-0">
            <a href="approval-queue.php" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Approval Queue
            </a>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="text-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-600">Loading order details...</p>
        </div>

        <!-- Error State -->
        <div x-show="error && !loading" class="text-center py-12">
            <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Error Loading Order</h3>
            <p class="text-gray-600" x-text="error"></p>
            <button @click="loadOrderDetails()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Try Again
            </button>
        </div>

        <!-- Order Details -->
        <div x-show="order && !loading" class="space-y-6">
            <!-- Header -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900" x-text="order?.order_number"></h1>
                            <p class="text-sm text-gray-600">
                                Created on <span x-text="formatDate(order?.created_at)"></span>
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span :class="getStatusBadgeClass(order?.status)" 
                                  class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                                <i :class="getStatusIcon(order?.status)" class="mr-1"></i>
                                <span x-text="order?.status_label || order?.status"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Order Info Grid -->
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Customer Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Customer Information</h3>
                            <div class="space-y-2">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Customer ID:</span>
                                    <p class="text-sm text-gray-900" x-text="order?.customer_id"></p>
                                </div>
                                <!-- Note: In a real application, you would fetch customer details -->
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Contact:</span>
                                    <p class="text-sm text-gray-900">customer@example.com</p>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Payment Information</h3>
                            <div class="space-y-2">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Total Amount:</span>
                                    <p class="text-lg font-semibold text-green-600">₹<span x-text="order?.total_amount"></span></p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Payment ID:</span>
                                    <p class="text-sm text-gray-900" x-text="order?.payment_id"></p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Payment Status:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Verified
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Order Status -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Order Status</h3>
                            <div class="space-y-2">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Current Status:</span>
                                    <p class="text-sm text-gray-900" x-text="order?.status_label || order?.status"></p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Last Updated:</span>
                                    <p class="text-sm text-gray-900" x-text="formatDate(order?.updated_at)"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Order Items</h2>
                </div>
                <div class="px-6 py-4">
                    <div x-show="orderItems && orderItems.length > 0">
                        <div class="space-y-4">
                            <template x-for="item in orderItems" :key="item.id">
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900" x-text="item.product_name || 'Product ID: ' + item.product_id"></h4>
                                        <p class="text-sm text-gray-600">
                                            Quantity: <span x-text="item.quantity"></span>
                                            <span x-show="item.variant_id"> | Variant: <span x-text="item.variant_id"></span></span>
                                        </p>
                                        <p class="text-sm text-gray-600">Rental Period ID: <span x-text="item.rental_period_id"></span></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-gray-900">₹<span x-text="item.total_price"></span></p>
                                        <p class="text-sm text-gray-600">₹<span x-text="item.unit_price"></span> each</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div x-show="!orderItems || orderItems.length === 0" class="text-center py-8 text-gray-500">
                        No items found for this order.
                    </div>
                </div>
            </div>

            <!-- Documents Section -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Verification Documents</h2>
                </div>
                <div class="px-6 py-4">
                    <!-- Loading Documents -->
                    <div x-show="documentsLoading" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600">Loading documents...</p>
                    </div>

                    <!-- Documents List -->
                    <div x-show="!documentsLoading && documents && documents.length > 0" class="space-y-3">
                        <template x-for="doc in documents" :key="doc.id">
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                <div class="flex items-center space-x-4">
                                    <!-- File Icon -->
                                    <div class="flex-shrink-0">
                                        <i :class="getFileIcon(doc.mime_type)" class="text-3xl text-gray-400"></i>
                                    </div>
                                    
                                    <!-- Document Info -->
                                    <div>
                                        <h4 class="font-medium text-gray-900" x-text="doc.document_type"></h4>
                                        <p class="text-sm text-gray-600">
                                            <span x-text="formatFileSize(doc.file_size)"></span>
                                            <span class="mx-2">•</span>
                                            Uploaded <span x-text="formatDate(doc.uploaded_at)"></span>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex items-center space-x-2">
                                    <!-- Preview Button (for images and PDFs) -->
                                    <button x-show="canPreview(doc.mime_type)" 
                                            @click="previewDocument(doc)"
                                            class="px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md">
                                        <i class="fas fa-eye mr-1"></i>
                                        Preview
                                    </button>
                                    
                                    <!-- Download Button -->
                                    <a :href="`/api/documents.php?document_id=${doc.id}`"
                                       download
                                       class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-md">
                                        <i class="fas fa-download mr-1"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- No Documents -->
                    <div x-show="!documentsLoading && (!documents || documents.length === 0)" class="text-center py-8 text-gray-500">
                        <i class="fas fa-file-alt text-3xl mb-2"></i>
                        <p>No verification documents uploaded yet.</p>
                        <p class="text-sm mt-1">Customer will upload required documents before approval.</p>
                    </div>
                </div>
            </div>

            <!-- Document Preview Modal -->
            <div x-show="previewModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click.self="closePreview()"
                 class="fixed inset-0 bg-black bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
                 style="display: none;">
                <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900" x-text="previewDoc?.document_type"></h3>
                        <button @click="closePreview()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <!-- Modal Body -->
                    <div class="p-6 overflow-auto max-h-[calc(90vh-120px)]">
                        <!-- Image Preview -->
                        <div x-show="previewDoc && previewDoc.mime_type.startsWith('image/')" class="text-center">
                            <img :src="`/api/documents.php?document_id=${previewDoc?.id}`" 
                                 :alt="previewDoc?.document_type"
                                 class="max-w-full h-auto rounded-lg shadow-lg">
                        </div>
                        
                        <!-- PDF Preview -->
                        <div x-show="previewDoc && previewDoc.mime_type === 'application/pdf'" class="w-full h-[70vh]">
                            <iframe :src="`/api/documents.php?document_id=${previewDoc?.id}`"
                                    class="w-full h-full border-0 rounded-lg"></iframe>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end px-6 py-4 border-t border-gray-200 space-x-3">
                        <a :href="`/api/documents.php?document_id=${previewDoc?.id}`"
                           download
                           class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-download mr-2"></i>
                            Download
                        </a>
                        <button @click="closePreview()" 
                                class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div x-show="order?.status === 'Pending_Vendor_Approval'" class="bg-white shadow rounded-lg">
                <div class="px-6 py-4">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Actions</h2>
                    <div class="flex space-x-4">
                        <button @click="showApproveConfirmation()" 
                                class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-transparent rounded-md text-base font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-check mr-2"></i>
                            Approve Order
                        </button>
                        <button @click="showRejectModal()" 
                                class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-transparent rounded-md text-base font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-times mr-2"></i>
                            Reject Order
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve Confirmation Modal -->
        <div x-show="showApproveModal" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
             style="display: none;">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Approve Order</h3>
                        <button @click="closeApproveModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Order: <span x-text="order?.order_number"></span></p>
                        <p class="text-sm text-gray-600">Are you sure you want to approve this order? This will activate the rental and the customer will be notified.</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button @click="closeApproveModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button @click="approveOrder()" 
                                :disabled="processing"
                                class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                            <span x-show="!processing">Approve Order</span>
                            <span x-show="processing">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Processing...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div x-show="showRejectModalFlag" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
             style="display: none;">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Reject Order</h3>
                        <button @click="closeRejectModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Order: <span x-text="order?.order_number"></span></p>
                        <p class="text-sm text-gray-600 mb-3">Please provide a reason for rejection. The customer will be notified and a refund will be initiated.</p>
                        
                        <textarea x-model="rejectionReason" 
                                  placeholder="Enter rejection reason..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                  rows="4"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button @click="closeRejectModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button @click="rejectOrder()" 
                                :disabled="!rejectionReason.trim() || processing"
                                class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                            <span x-show="!processing">Reject Order</span>
                            <span x-show="processing">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Processing...
                            </span>
                        </button>
                    </div>
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
        function orderDetails(orderId) {
            return {
                orderId: orderId,
                order: null,
                orderItems: [],
                documents: [],
                documentsLoading: false,
                previewModal: false,
                previewDoc: null,
                loading: true,
                error: '',
                showApproveModal: false,
                showRejectModalFlag: false,
                rejectionReason: '',
                processing: false,
                message: '',
                messageType: 'success',

                init() {
                    this.loadOrderDetails();
                    this.loadDocuments();
                },

                async loadOrderDetails() {
                    this.loading = true;
                    this.error = '';
                    
                    try {
                        const response = await fetch(`/api/orders.php?action=order_details&order_id=${this.orderId}`);
                        const data = await response.json();
                        
                        if (data.success) {
                            this.order = data.data.order;
                            this.orderItems = data.data.items || [];
                        } else {
                            this.error = data.error || 'Failed to load order details';
                        }
                    } catch (error) {
                        this.error = 'Error loading order details: ' + error.message;
                    } finally {
                        this.loading = false;
                    }
                },

                showApproveConfirmation() {
                    this.showApproveModal = true;
                },

                closeApproveModal() {
                    this.showApproveModal = false;
                },

                async approveOrder() {
                    this.processing = true;
                    
                    try {
                        const formData = new FormData();
                        formData.append('action', 'approve');
                        formData.append('order_id', this.orderId);
                        formData.append('reason', 'Approved by vendor after review');

                        const response = await fetch('/api/orders.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.showMessage('Order approved successfully! Customer has been notified.', 'success');
                            this.closeApproveModal();
                            // Reload order details to show updated status
                            setTimeout(() => {
                                this.loadOrderDetails();
                            }, 1000);
                        } else {
                            this.showMessage('Failed to approve order: ' + data.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Error approving order: ' + error.message, 'error');
                    } finally {
                        this.processing = false;
                    }
                },

                showRejectModal() {
                    this.rejectionReason = '';
                    this.showRejectModalFlag = true;
                },

                closeRejectModal() {
                    this.showRejectModalFlag = false;
                    this.rejectionReason = '';
                },

                async rejectOrder() {
                    if (!this.rejectionReason.trim()) {
                        this.showMessage('Please provide a rejection reason', 'error');
                        return;
                    }

                    this.processing = true;
                    
                    try {
                        const formData = new FormData();
                        formData.append('action', 'reject');
                        formData.append('order_id', this.orderId);
                        formData.append('reason', this.rejectionReason);

                        const response = await fetch('/api/orders.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.showMessage('Order rejected successfully! Customer has been notified and refund initiated.', 'success');
                            this.closeRejectModal();
                            // Reload order details to show updated status
                            setTimeout(() => {
                                this.loadOrderDetails();
                            }, 1000);
                        } else {
                            this.showMessage('Failed to reject order: ' + data.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Error rejecting order: ' + error.message, 'error');
                    } finally {
                        this.processing = false;
                    }
                },

                getStatusBadgeClass(status) {
                    const classes = {
                        'Pending_Vendor_Approval': 'bg-yellow-100 text-yellow-800',
                        'Active_Rental': 'bg-green-100 text-green-800',
                        'Completed': 'bg-gray-100 text-gray-800',
                        'Rejected': 'bg-red-100 text-red-800',
                        'Refunded': 'bg-purple-100 text-purple-800',
                        'Auto_Approved': 'bg-blue-100 text-blue-800',
                        'Payment_Successful': 'bg-indigo-100 text-indigo-800'
                    };
                    return classes[status] || 'bg-gray-100 text-gray-800';
                },

                getStatusIcon(status) {
                    const icons = {
                        'Pending_Vendor_Approval': 'fas fa-clock',
                        'Active_Rental': 'fas fa-play-circle',
                        'Completed': 'fas fa-check-circle',
                        'Rejected': 'fas fa-times-circle',
                        'Refunded': 'fas fa-undo',
                        'Auto_Approved': 'fas fa-check',
                        'Payment_Successful': 'fas fa-credit-card'
                    };
                    return icons[status] || 'fas fa-info-circle';
                },

                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },

                showMessage(text, type = 'success') {
                    this.message = text;
                    this.messageType = type;
                    
                    // Auto-hide after 5 seconds
                    setTimeout(() => {
                        this.message = '';
                    }, 5000);
                },

                async loadDocuments() {
                    this.documentsLoading = true;
                    
                    try {
                        const response = await fetch(`/api/documents.php?order_id=${this.orderId}`);
                        const data = await response.json();
                        
                        if (data.documents) {
                            this.documents = data.documents;
                        }
                    } catch (error) {
                        console.error('Error loading documents:', error);
                    } finally {
                        this.documentsLoading = false;
                    }
                },

                getFileIcon(mimeType) {
                    if (mimeType.startsWith('image/')) {
                        return 'fas fa-file-image text-blue-500';
                    } else if (mimeType === 'application/pdf') {
                        return 'fas fa-file-pdf text-red-500';
                    } else {
                        return 'fas fa-file text-gray-500';
                    }
                },

                formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
                },

                canPreview(mimeType) {
                    return mimeType.startsWith('image/') || mimeType === 'application/pdf';
                },

                previewDocument(doc) {
                    this.previewDoc = doc;
                    this.previewModal = true;
                },

                closePreview() {
                    this.previewModal = false;
                    this.previewDoc = null;
                }
            }
        }
    </script>
</body>
</html>