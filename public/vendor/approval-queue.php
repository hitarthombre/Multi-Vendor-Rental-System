<?php
session_start();

// For demo purposes, use hardcoded vendor ID
// In a real application, this would come from the session
$vendorId = 'demo-vendor-456';
$vendorName = 'Demo Vendor';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Queue - Vendor Dashboard</title>
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
                        <a href="approval-queue.php" class="border-b-2 border-blue-500 text-blue-600 px-3 py-2 text-sm font-medium">
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
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" x-data="approvalQueue()">
        <!-- Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Approval Queue</h1>
                    <p class="mt-1 text-sm text-gray-600">Review and approve pending rental requests</p>
                </div>
                <button @click="refreshQueue()" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': loading }"></i>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading && pendingOrders.length === 0" class="text-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-600">Loading pending orders...</p>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && pendingOrders.length === 0" class="text-center py-12">
            <i class="fas fa-clipboard-check text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No pending approvals</h3>
            <p class="text-gray-600">All orders have been processed. Great job!</p>
        </div>

        <!-- Orders Grid -->
        <div x-show="pendingOrders.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <template x-for="order in pendingOrders" :key="order.id">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Order Header -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900" x-text="order.order_number"></h3>
                                <p class="text-sm text-gray-600" x-text="formatDate(order.created_at)"></p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>
                                Pending Approval
                            </span>
                        </div>
                    </div>

                    <!-- Order Details -->
                    <div class="px-6 py-4">
                        <div class="space-y-3">
                            <!-- Customer Info -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-1">Customer</h4>
                                <p class="text-sm text-gray-600" x-text="order.customer_id"></p>
                            </div>

                            <!-- Amount -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-1">Total Amount</h4>
                                <p class="text-lg font-semibold text-green-600">₹<span x-text="order.total_amount"></span></p>
                            </div>

                            <!-- Payment Status -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-1">Payment</h4>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Verified
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex space-x-3">
                            <button @click="viewOrderDetails(order.id)" 
                                    class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-eye mr-2"></i>
                                View Details
                            </button>
                            <button @click="approveOrder(order.id)" 
                                    class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-check mr-2"></i>
                                Approve
                            </button>
                            <button @click="showRejectModal(order)" 
                                    class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <i class="fas fa-times mr-2"></i>
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </template>
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
                        <p class="text-sm text-gray-600 mb-2">Order: <span x-text="selectedOrder?.order_number"></span></p>
                        <p class="text-sm text-gray-600">Please provide a reason for rejection:</p>
                    </div>
                    
                    <textarea x-model="rejectionReason" 
                              placeholder="Enter rejection reason..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              rows="4"></textarea>
                    
                    <div class="flex justify-end space-x-3 mt-4">
                        <button @click="closeRejectModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button @click="rejectOrder()" 
                                :disabled="!rejectionReason.trim()"
                                class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                            Reject Order
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
        function approvalQueue() {
            return {
                pendingOrders: [],
                loading: true,
                showRejectModalFlag: false,
                selectedOrder: null,
                rejectionReason: '',
                message: '',
                messageType: 'success',

                init() {
                    this.loadPendingOrders();
                },

                async loadPendingOrders() {
                    this.loading = true;
                    try {
                        const response = await fetch('/api/orders.php?action=pending_approvals');
                        const data = await response.json();
                        
                        if (data.success) {
                            this.pendingOrders = data.data;
                        } else {
                            this.showMessage('Failed to load pending orders: ' + data.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Error loading pending orders: ' + error.message, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async refreshQueue() {
                    await this.loadPendingOrders();
                    this.showMessage('Queue refreshed successfully', 'success');
                },

                async approveOrder(orderId) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'approve');
                        formData.append('order_id', orderId);
                        formData.append('reason', 'Approved by vendor');

                        const response = await fetch('/api/orders.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.showMessage('Order approved successfully', 'success');
                            // Remove the approved order from the list
                            this.pendingOrders = this.pendingOrders.filter(order => order.id !== orderId);
                        } else {
                            this.showMessage('Failed to approve order: ' + data.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Error approving order: ' + error.message, 'error');
                    }
                },

                showRejectModal(order) {
                    this.selectedOrder = order;
                    this.rejectionReason = '';
                    this.showRejectModalFlag = true;
                },

                closeRejectModal() {
                    this.showRejectModalFlag = false;
                    this.selectedOrder = null;
                    this.rejectionReason = '';
                },

                async rejectOrder() {
                    if (!this.rejectionReason.trim()) {
                        this.showMessage('Please provide a rejection reason', 'error');
                        return;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('action', 'reject');
                        formData.append('order_id', this.selectedOrder.id);
                        formData.append('reason', this.rejectionReason);

                        const response = await fetch('/api/orders.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.showMessage('Order rejected successfully', 'success');
                            // Remove the rejected order from the list
                            this.pendingOrders = this.pendingOrders.filter(order => order.id !== this.selectedOrder.id);
                            this.closeRejectModal();
                        } else {
                            this.showMessage('Failed to reject order: ' + data.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Error rejecting order: ' + error.message, 'error');
                    }
                },

                viewOrderDetails(orderId) {
                    // Navigate to order details page
                    window.location.href = `order-details.php?id=${orderId}`;
                },

                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
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
                }
            }
        }
    </script>
</body>
</html>