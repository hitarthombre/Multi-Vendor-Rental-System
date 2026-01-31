<?php
/**
 * Web-based Database Seeder
 * 
 * Access this file via browser to seed demo data
 * URL: http://localhost:8081/Multi-Vendor-Rental-System/seed-data.php
 */

// Set execution time limit for seeding
set_time_limit(300);

// Output buffering for real-time feedback
if (ob_get_level() == 0) ob_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Seeder - Multi-Vendor Rental Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .log-line { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn {
            from { transform: translateX(-10px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-8 py-6">
                    <h1 class="text-3xl font-bold text-white flex items-center">
                        <i class="fas fa-database mr-3"></i>
                        Database Seeder
                    </h1>
                    <p class="text-primary-100 mt-2">Populate your database with demo data</p>
                </div>

                <!-- Content -->
                <div class="p-8">
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    This will create 5 vendors, 23 products, 2 customers, and 5 categories.
                                    <strong>Note:</strong> This can only be run once. To re-seed, clear the database first.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Log Output -->
                    <div class="bg-gray-900 rounded-lg p-6 mb-6 font-mono text-sm text-green-400 h-96 overflow-y-auto" id="log-output">
                        <div class="log-line">🌱 Initializing seeder...</div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4">
                        <button onclick="startSeeding()" id="seed-btn" 
                                class="flex-1 bg-primary-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-700 transition-colors">
                            <i class="fas fa-play mr-2"></i>
                            Start Seeding
                        </button>
                        <a href="/Multi-Vendor-Rental-System/public/login.php" 
                           class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition-colors text-center">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Go to Login
                        </a>
                    </div>
                </div>
            </div>

            <!-- Credentials Preview -->
            <div class="mt-8 bg-white rounded-xl shadow-lg p-8" id="credentials-section" style="display: none;">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-key text-primary-600 mr-2"></i>
                    Demo Credentials
                </h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Vendor Accounts</h3>
                        <div class="space-y-2 text-sm">
                            <div class="bg-gray-50 p-3 rounded">
                                <strong>houserentals</strong> - Premium House Rentals
                            </div>
                            <div class="bg-gray-50 p-3 rounded">
                                <strong>soundwave</strong> - SoundWave Audio
                            </div>
                            <div class="bg-gray-50 p-3 rounded">
                                <strong>driveaway</strong> - DriveAway Cars
                            </div>
                            <div class="bg-gray-50 p-3 rounded">
                                <strong>furnishpro</strong> - FurnishPro
                            </div>
                            <div class="bg-gray-50 p-3 rounded">
                                <strong>techrent</strong> - TechRent Computers
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Customer Accounts</h3>
                        <div class="space-y-2 text-sm">
                            <div class="bg-gray-50 p-3 rounded">
                                <strong>john_doe</strong> - John Doe
                            </div>
                            <div class="bg-gray-50 p-3 rounded">
                                <strong>jane_smith</strong> - Jane Smith
                            </div>
                        </div>
                        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded p-3">
                            <p class="text-sm text-yellow-800">
                                <i class="fas fa-lock mr-1"></i>
                                All accounts use password: <strong>password123</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }

        function addLog(message, type = 'info') {
            const logOutput = document.getElementById('log-output');
            const colors = {
                'info': 'text-green-400',
                'success': 'text-green-300',
                'error': 'text-red-400',
                'warning': 'text-yellow-400'
            };
            const line = document.createElement('div');
            line.className = `log-line ${colors[type]}`;
            line.textContent = message;
            logOutput.appendChild(line);
            logOutput.scrollTop = logOutput.scrollHeight;
        }

        async function startSeeding() {
            const btn = document.getElementById('seed-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Seeding...';
            
            addLog('');
            addLog('🚀 Starting database seeding process...', 'info');
            
            try {
                const response = await fetch('database/seed-demo-data.php');
                const text = await response.text();
                
                // Parse output line by line
                const lines = text.split('\n');
                for (const line of lines) {
                    if (line.trim()) {
                        if (line.includes('✓') || line.includes('✅')) {
                            addLog(line, 'success');
                        } else if (line.includes('❌') || line.includes('Error')) {
                            addLog(line, 'error');
                        } else if (line.includes('📊') || line.includes('🔑')) {
                            addLog(line, 'warning');
                        } else {
                            addLog(line, 'info');
                        }
                        await new Promise(resolve => setTimeout(resolve, 50));
                    }
                }
                
                addLog('');
                addLog('✅ Seeding completed successfully!', 'success');
                addLog('🔑 Check database/DEMO_CREDENTIALS.md for login details', 'warning');
                
                btn.innerHTML = '<i class="fas fa-check mr-2"></i>Completed';
                btn.className = 'flex-1 bg-green-600 text-white px-6 py-3 rounded-lg font-semibold';
                
                // Show credentials section
                document.getElementById('credentials-section').style.display = 'block';
                
            } catch (error) {
                addLog('', 'error');
                addLog('❌ Error: ' + error.message, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-redo mr-2"></i>Retry';
            }
        }
    </script>
</body>
</html>
