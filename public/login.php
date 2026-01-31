<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\AuthService;
use RentalPlatform\Repositories\UserRepository;

Session::start();

// If already logged in, redirect to dashboard
if (Session::isAuthenticated()) {
    $role = Session::getRole();
    header('Location: /Multi-Vendor-Rental-System/public/' . strtolower($role) . '/dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $errors[] = 'Please enter both username and password';
    } else {
        try {
            $userRepo = new UserRepository();
            $authService = new AuthService($userRepo);
            
            $result = $authService->login($username, $password);
            
            if ($result['success']) {
                // Redirect based on role
                $role = Session::getRole();
                if ($role === 'Vendor') {
                    header('Location: /Multi-Vendor-Rental-System/public/vendor/dashboard.php');
                } elseif ($role === 'Administrator') {
                    header('Location: /Multi-Vendor-Rental-System/public/admin/dashboard.php');
                } else {
                    header('Location: /Multi-Vendor-Rental-System/public/customer/dashboard.php');
                }
                exit;
            } else {
                $errors[] = $result['message'];
            }
        } catch (Exception $e) {
            $errors[] = 'Login failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Sign In';
$showNav = false;
$showContainer = false;

ob_start();
?>

<div class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-purple-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <a href="/Multi-Vendor-Rental-System/public/index.php" class="flex justify-center">
            <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg">
                <i class="fas fa-store text-white text-3xl"></i>
            </div>
        </a>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Welcome back
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Don't have an account?
            <a href="/Multi-Vendor-Rental-System/public/register.php" class="font-medium text-primary-600 hover:text-primary-500">
                Sign up for free
            </a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-2xl sm:rounded-2xl sm:px-10">
            <?php if (!empty($errors)): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded animate-slide-in">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Login failed</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" x-data="{ showPassword: false }">
                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">
                        Username or Email
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input id="username" name="username" type="text" required autofocus
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="Enter your username or email">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="password" name="password" :type="showPassword ? 'text' : 'password'" required
                               class="appearance-none block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="Enter your password">
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" name="remember_me" type="checkbox"
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded cursor-pointer">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-700 cursor-pointer">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="/Multi-Vendor-Rental-System/public/forgot-password.php" class="font-medium text-primary-600 hover:text-primary-500">
                            Forgot password?
                        </a>
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit"
                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all transform hover:scale-105">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Sign In
                    </button>
                </div>
            </form>

            <!-- Divider -->
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">New to our platform?</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="/Multi-Vendor-Rental-System/public/register.php"
                       class="w-full flex justify-center items-center py-3 px-4 border-2 border-primary-600 rounded-lg shadow-sm text-base font-medium text-primary-600 bg-white hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all">
                        <i class="fas fa-user-plus mr-2"></i>
                        Create an Account
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/base.php';
?>
