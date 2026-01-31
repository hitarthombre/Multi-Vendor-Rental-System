<?php
/**
 * Admin Settings Page
 * 
 * Platform configuration management interface for administrators
 */

require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Auth/Session.php';
require_once __DIR__ . '/../../src/Auth/Authorization.php';
require_once __DIR__ . '/../../src/Auth/Permission.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Authorization;
use RentalPlatform\Auth\Permission;

$session = new Session();
$auth = new Authorization($session);

// Check authentication and admin permission
if (!$auth->isAuthenticated() || !$auth->getCurrentUser()->isAdministrator()) {
    header('Location: /login.php');
    exit;
}

$currentUser = $auth->getCurrentUser();

// Check platform config permission
if (!Permission::hasPermission($currentUser->getRole(), Permission::RESOURCE_PLATFORM_CONFIG, Permission::ACTION_READ)) {
    header('Location: /admin/dashboard.php?error=insufficient_permissions');
    exit;
}

$pageTitle = 'Platform Settings';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include __DIR__ . '/../components/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Platform Settings</h1>
                    <p class="mt-2 text-gray-600">Configure platform-wide settings and preferences</p>
                </div>
                <div class="flex space-x-3">
                    <button id="resetBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-undo mr-2"></i>Reset Changes
                    </button>
                    <button id="saveBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Loading configuration...</p>
        </div>

        <!-- Error State -->
        <div id="errorState" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-1"></i>
                <div>
                    <h3 class="text-red-800 font-medium">Error Loading Configuration</h3>
                    <p id="errorMessage" class="text-red-700 mt-1"></p>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <div id="successMessage" class="hidden bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <i class="fas fa-check-circle text-green-400 mr-3 mt-1"></i>
                <div>
                    <h3 class="text-green-800 font-medium">Settings Updated</h3>
                    <p class="text-green-700 mt-1">Platform configuration has been saved successfully.</p>
                </div>
            </div>
        </div>

        <!-- Configuration Sections -->
        <div id="configSections" class="hidden space-y-8">
            <!-- General Settings -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">
                        <i class="fas fa-cog text-gray-500 mr-2"></i>General Settings
                    </h2>
                    <p class="text-gray-600 mt-1">Basic platform configuration</p>
         