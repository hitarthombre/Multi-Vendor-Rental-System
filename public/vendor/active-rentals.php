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
    <title>Active Rentals - Vendor Dashboard</title>
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
                        <a href="active-rentals.php" class="border-b-2 border-blue-500 text-blue-600 px-3 py-2 text-sm font-medium">
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
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" x-data="activeRentals()">
        <!-- Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Active Rentals</h1>
                    <p class="mt-1 text-sm text-gray-600">Manage ongoing rentals and mark them as completed</p>
                </div>
                <button @click="refreshRentals()" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': loading }"></i>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading && activeRentals.length === 0" class="text-center py-12">
            <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-600">Loading active rentals...</p>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && activeRentals.length === 0" class="text-center py-12">
            <i class="fas fa-calendar-check text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No active rentals</h3>
            <p class="text-gray-600">All rentals have been completed or there are no active rentals at the moment.</p>
        </div>

        <!-- Rentals Grid -->
        <div x-show="activeRentals.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <template x-for="rental in activeRentals" :key="rental.id">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Rental Header -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900" x-text="rental.order_number"></h3>
                                <p class="text-sm text-gray-600" x-text="formatDate(rental.created_at)"></p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-play-circle mr-1"></i>
                                Active Rental
                            </span>
                        </div>
                    </div>

                    <!-- Rental Details -->
                    <div class="px-6 py-4">
                        <div class="space-y-3">
                            <!-- Customer Info -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-1">Customer</h4>
                                <p class="text-sm text-gray-600" x-text="rental.customer_id"></p>
                            </div>

                            <!-- Amount -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-1">Total Amount</h4>
                                <p class="text-lg font-semibold text-green-600">₹<span x-text="rental.total_amount"></span></p>
                            </div>

                            <!-- Deposit -->
                            <div x-show="rental.deposit_amount > 0">
                                <h4 class="text-sm font-medium text-gray-900 mb-1">Security Deposit</h4>
                                <p class="text-sm font-medium text-blue-600">₹<span x-text="rental.deposit_amount"></span></p>
                            </div>

                            <!-- Duration -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-1">Rental Period</h4>
                                <p class="text-sm text-gray-600">Started: <span x-text="formatDate(rental.created_at)"></span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex space-x-3">
                            <button @click="viewRentalDetails(rental.id)" 
                                    class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-eye mr-2"></i>
                                View Details
                            </button>
                            <button @click="showCompleteModal(rental)" 
                                    class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <i class="fas fa-check-circle mr-2"></i>
                                Complete
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Complete Rental Modal -->
        <div x-show="showCompleteModalFlag" 
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
                        <h3 class="text-lg font-medium text-gray-900">Complete Rental</h3>
                        <button @click="closeCompleteModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Order: <span x-text="selectedRental?.order_number"></span></p>
                        <p class="text-sm text-gray-600 mb-3">Mark this rental as completed and process the security deposit.</p>
                    </div>

                    <!-- Deposit Processing -->
                    <div x-show="selectedRental?.deposit_amount > 0" class="mb-4 p-3 bg-blue-50 rounded-md">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Security Deposit: ₹<span x-text="selectedRental?.deposit_amount"></span></h4>
                        
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="radio" x-model="depositAction" value="release" class="mr-2">
                                <span class="text-sm text-gray-700">Release full deposit (no damages)</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="radio" x-model="depositAction" value="penalty" class="mr-2">
                                <span class="text-sm text-gray-700">Apply penalty for damages</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="radio" x-model="depositAction" value="withhold" class="mr-2">
                                <span class="text-sm text-gray-700">Withhold full deposit</span>
                            </label>
                        </div>

                        <!-- Penalty Amount Input -->
                        <div x-show="depositAction === 'penalty'" class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Penalty Amount</label>
                            <input type="number" 
                                   x-model="penaltyAmount" 
                                   :max="selectedRental?.deposit_amount"
                                   min="0" 
                                   step="0.01"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter penalty amount">
                        </div>

                        <!-- Reason Input -->
                        <div x-show="depositAction === 'penalty' || depositAction === 'withhold'" class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                            <textarea x-model="penaltyReason" 
                                      placeholder="Explain the reason for penalty/withholding..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Completion Reason -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Completion Notes (Optional)</label>
                        <textarea x-model="completionReason" 
                                  placeholder="Add any notes about the rental completion..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                  rows="3"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button @click="closeCompleteModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button @click="completeRental()" 
                                :disabled="processing || (depositAction === 'penalty' && (!penaltyAmount || !penaltyReason)) || (depositAction === 'withhold' && !penaltyReason)"
                                class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                            <span x-show="!processing">Complete Rental</span>
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
        function activeRentals() {
            return {
                activeRentals: [],
                loading: true,
                showCompleteModalFlag: false,
                selectedRental: null,
                depositAction: 'release',
                penaltyAmount: 0,
                penaltyReason: '',
                completionReason: '',
                processing: false,
                message: '',
                messageType: 'success',

                init() {
                    this.loadActiveRentals();
                },

                async loadActiveRentals() {
                    this.loading = true;
                    try {
                        const response = await fetch('/api/orders.php?action=active_rentals');
                        const data = await response.json();
                        
                        if (data.success) {
                            this.activeRentals = data.data;
                        } else {
                            this.showMessage('Failed to load active rentals: ' + data.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Error loading active rentals: ' + error.message, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async refreshRentals() {
                    await this.loadActiveRentals();
                    this.showMessage('Rentals refreshed successfully', 'success');
                },

                showCompleteModal(rental) {
                    this.selectedRental = rental;
                    this.depositAction = 'release';
                    this.penaltyAmount = 0;
                    this.penaltyReason = '';
                    this.completionReason = '';
                    this.showCompleteModalFlag = true;
                },

                closeCompleteModal() {
                    this.showCompleteModalFlag = false;
                    this.selectedRental = null;
                    this.depositAction = 'release';
                    this.penaltyAmount = 0;
                    this.penaltyReason = '';
                    this.completionReason = '';
                },

                async completeRental() {
                    if (!this.selectedRental) return;

                    // Validate penalty inputs
                    if (this.depositAction === 'penalty') {
                        if (!this.penaltyAmount || this.penaltyAmount <= 0) {
                            this.showMessage('Please enter a valid penalty amount', 'error');
                            return;
                        }
                        if (!this.penaltyReason.trim()) {
                            this.showMessage('Please provide a reason for the penalty', 'error');
                            return;
                        }
                        if (this.penaltyAmount > this.selectedRental.deposit_amount) {
                            this.showMessage('Penalty amount cannot exceed deposit amount', 'error');
                            return;
                        }
                    }

                    if (this.depositAction === 'withhold' && !this.penaltyReason.trim()) {
                        this.showMessage('Please provide a reason for withholding the deposit', 'error');
                        return;
                    }

                    this.processing = true;

                    try {
                        const formData = new FormData();
                        formData.append('action', 'complete');
                        formData.append('order_id', this.selectedRental.id);
                        formData.append('reason', this.completionReason || 'Rental completed by vendor');
                        
                        // Deposit processing parameters
                        formData.append('release_deposit', this.depositAction === 'release' ? 'true' : 'false');
                        formData.append('penalty_amount', this.depositAction === 'penalty' ? this.penaltyAmount : 0);
                        formData.append('penalty_reason', this.penaltyReason);

                        const response = await fetch('/api/orders.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            this.showMessage('Rental completed successfully! Customer has been notified.', 'success');
                            this.closeCompleteModal();
                            // Remove the completed rental from the list
                            this.activeRentals = this.activeRentals.filter(rental => rental.id !== this.selectedRental.id);
                        } else {
                            this.showMessage('Failed to complete rental: ' + data.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Error completing rental: ' + error.message, 'error');
                    } finally {
                        this.processing = false;
                    }
                },

                viewRentalDetails(rentalId) {
                    // Navigate to order details page
                    window.location.href = `order-details.php?id=${rentalId}`;
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