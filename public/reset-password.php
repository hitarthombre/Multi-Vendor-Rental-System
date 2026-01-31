<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\PasswordReset;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\UserRepository;

Session::start();

// Redirect if already logged in
if (Session::isAuthenticated()) {
    $role = Session::getRole();
    header('Location: /Multi-Vendor-Rental-System/public/' . strtolower($role) . '/dashboard.php');
    exit;
}

$errors = [];
$success = '';
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

// Validate token and email are present
if (empty($token) || empty($email)) {
    $errors[] = 'Invalid password reset link';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        try {
            $userRepo = new UserRepository();
            $user = $userRepo->findByEmail($email);
            
            if (!$user) {
                $errors[] = 'Invalid password reset link';
            } else {
                // Get stored token data
                $tokenData = $userRepo->getPasswordResetToken($user->getId());
                
                if (!$tokenData) {
                    $errors[] = 'Invalid or expired password reset link';
                } elseif (!PasswordReset::isTokenValid($tokenData['expiry'])) {
                    $errors[] = 'Password reset link has expired. Please request a new one.';
                    // Clean up expired token
                    $userRepo->deletePasswordResetToken($user->getId());
                } elseif (!PasswordReset::verifyToken($token, $tokenData['token_hash'])) {
                    $errors[] = 'Invalid password reset link';
                } else {
                    // Token is valid, update password
                    $newPasswordHash = User::hashPassword($password);
                    $userRepo->updatePassword($user->getId(), $newPasswordHash);
                    
                    // Delete used token
                    $userRepo->deletePasswordResetToken($user->getId());
                    
                    $success = 'Your password has been reset successfully. You can now login with your new password.';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred. Please try again later.';
        }
    }
}

$pageTitle = 'Reset Password';
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
            Set new password
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Enter your new password below
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
                            <h3 class="text-sm font-medium text-red-800">Error</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded animate-slide-in">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Success!</h3>
                            <p class="mt-2 text-sm text-green-700"><?= htmlspecialchars($success) ?></p>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="/Multi-Vendor-Rental-System/public/login.php"
                       class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all transform hover:scale-105">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Go to Login
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-6" x-data="{ showPassword: false, showConfirmPassword: false }">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

                    <!-- New Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            New Password
                        </label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="password" name="password" :type="showPassword ? 'text' : 'password'" required autofocus
                                   class="appearance-none block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                   placeholder="Enter new password">
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                            Confirm New Password
                        </label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input id="confirm_password" name="confirm_password" :type="showConfirmPassword ? 'text' : 'password'" required
                                   class="appearance-none block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                   placeholder="Confirm new password">
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i class="fas" :class="showConfirmPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit"
                                class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all transform hover:scale-105">
                            <i class="fas fa-key mr-2"></i>
                            Reset Password
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/base.php';
?>
