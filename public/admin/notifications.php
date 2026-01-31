<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Management - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen" x-data="notificationManager()">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <h1 class="text-3xl font-bold text-gray-900">Notification Management</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button @click="refreshData()" 
                                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Health Status -->
            <div class="mb-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">System Health</h3>
                        <div x-show="health" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold" 
                                     :class="health?.status === 'healthy' ? 'text-green-600' : 
                                            health?.status === 'warning' ? 'text-yellow-600' : 'text-red-600'">
                                    <i class="fas" 
                                       :class="health?.status === 'healthy' ? 'fa-check-circle' : 
                                              health?.status === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle'"></i>
                                </div>
                                <div class="text-sm text-gray-500">Status</div>
                                <div class="font-medium" x-text="health?.status || 'Unknown'"></div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="text-sm text-gray-500">Email Service</div>
                                <div class="font-medium" x-text="health?.email_service || 'Unknown'"></div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-600">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="text-sm text-gray-500">Last Check</div>
                                <div class="font-medium text-sm" x-text="health?.timestamp || 'Never'"></div>
                            </div>
                        </div>
                        
                        <!-- Warnings -->
                        <div x-show="health?.warnings && health.warnings.length > 0" class="mt-4">
                            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                <div class="flex">
                                    <i class="fas fa-exclamation-triangle text-yellow-400 mr-2 mt-0.5"></i>
                                    <div>
                                        <h4 class="text-sm font-medium text-yellow-800">Warnings</h4>
                                        <ul class="mt-2 text-sm text-yellow-700">
                                            <template x-for="warning in health.warnings">
                                                <li x-text="warning"></li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="mb-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Notification Statistics</h3>
                        <div x-show="statistics" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <div class="text-3xl font-bold text-green-600" x-text="statistics?.Sent?.count || 0"></div>
                                <div class="text-sm text-gray-500">Sent Successfully</div>
                            </div>
                            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                                <div class="text-3xl font-bold text-yellow-600" x-text="statistics?.Pending?.count || 0"></div>
                                <div class="text-sm text-gray-500">Pending</div>
                            </div>
                            <div class="text-center p-4 bg-red-50 rounded-lg">
                                <div class="text-3xl font-bold text-red-600" x-text="statistics?.Failed?.count || 0"></div>
                                <div class="text-sm text-gray-500">Failed</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mb-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Actions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <button @click="processPending()" 
                                    :disabled="processing"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50">
                                <i class="fas fa-play mr-2"></i>Process Pending
                            </button>
                            <button @click="retryFailed()" 
                                    :disabled="processing"
                                    class="bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700 disabled:opacity-50">
                                <i class="fas fa-redo mr-2"></i>Retry Failed
                            </button>
                            <button @click="cleanupOld()" 
                                    :disabled="processing"
                                    class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 disabled:opacity-50">
                                <i class="fas fa-trash mr-2"></i>Cleanup Old
                            </button>
                            <button @click="sendTest()" 
                                    :disabled="processing"
                                    class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 disabled:opacity-50">
                                <i class="fas fa-envelope mr-2"></i>Send Test
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div x-show="message" class="mb-6">
                <div class="rounded-md p-4" 
                     :class="messageType === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                    <div class="flex">
                        <i class="fas mr-2 mt-0.5" 
                           :class="messageType === 'success' ? 'fa-check-circle text-green-400' : 'fa-times-circle text-red-400'"></i>
                        <div x-text="message"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function notificationManager() {
            return {
                statistics: null,
                health: null,
                processing: false,
                message: '',
                messageType: 'success',

                async init() {
                    await this.refreshData();
                },

                async refreshData() {
                    try {
                        // Get statistics
                        const statsResponse = await fetch('/api/notifications.php?action=statistics');
                        const statsResult = await statsResponse.json();
                        if (statsResult.success) {
                            this.statistics = statsResult.data;
                        }

                        // Get health
                        const healthResponse = await fetch('/api/notifications.php?action=health');
                        const healthResult = await healthResponse.json();
                        if (healthResult.success) {
                            this.health = healthResult.data;
                        }
                    } catch (error) {
                        this.showMessage('Failed to load data: ' + error.message, 'error');
                    }
                },

                async processPending() {
                    this.processing = true;
                    try {
                        const formData = new FormData();
                        formData.append('action', 'process_pending');
                        formData.append('limit', '100');

                        const response = await fetch('/api/notifications.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();

                        if (result.success) {
                            this.showMessage(result.message, 'success');
                            await this.refreshData();
                        } else {
                            this.showMessage(result.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Failed to process pending notifications: ' + error.message, 'error');
                    } finally {
                        this.processing = false;
                    }
                },

                async retryFailed() {
                    this.processing = true;
                    try {
                        const formData = new FormData();
                        formData.append('action', 'retry_failed');
                        formData.append('limit', '50');
                        formData.append('backoff_minutes', '30');

                        const response = await fetch('/api/notifications.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();

                        if (result.success) {
                            this.showMessage(result.message, 'success');
                            await this.refreshData();
                        } else {
                            this.showMessage(result.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Failed to retry failed notifications: ' + error.message, 'error');
                    } finally {
                        this.processing = false;
                    }
                },

                async cleanupOld() {
                    if (!confirm('Are you sure you want to delete old notifications? This cannot be undone.')) {
                        return;
                    }

                    this.processing = true;
                    try {
                        const formData = new FormData();
                        formData.append('action', 'cleanup_old');
                        formData.append('days_old', '30');

                        const response = await fetch('/api/notifications.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();

                        if (result.success) {
                            this.showMessage(result.message, 'success');
                            await this.refreshData();
                        } else {
                            this.showMessage(result.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Failed to cleanup old notifications: ' + error.message, 'error');
                    } finally {
                        this.processing = false;
                    }
                },

                async sendTest() {
                    const userId = prompt('Enter user ID to send test notification to:');
                    if (!userId) return;

                    this.processing = true;
                    try {
                        const formData = new FormData();
                        formData.append('action', 'send_test');
                        formData.append('user_id', userId);

                        const response = await fetch('/api/notifications.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();

                        if (result.success) {
                            this.showMessage(result.message, 'success');
                        } else {
                            this.showMessage(result.error, 'error');
                        }
                    } catch (error) {
                        this.showMessage('Failed to send test notification: ' + error.message, 'error');
                    } finally {
                        this.processing = false;
                    }
                },

                showMessage(msg, type = 'success') {
                    this.message = msg;
                    this.messageType = type;
                    setTimeout(() => {
                        this.message = '';
                    }, 5000);
                }
            }
        }
    </script>
</body>
</html>