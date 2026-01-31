<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\PasswordReset;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } else {
        try {
            $userRepo = new UserRepository();
            $user = $userRepo->findByEmail($email);
            
            if ($user) {
                // Generate reset token
                $tokenData = PasswordReset::generateToken();
                $tokenHash = PasswordReset::hashToken($tokenData['token']);
                
                // Store token in database
                $userRepo->storePasswordResetToken(
                    $user->getId(),
                    $tokenHash,
                    $tokenData['expiry']
                );
                
                // Create reset link
                $resetLink = 'http://localhost:8081/Multi-Vendor-Rental-System/public/reset-password.php?token=' . $tokenData['token'] . '&email=' . urlencode($email);
                
                // TODO: Send email with reset link
                // For now, we'll just show the link (in production, this would be emailed)
                $success = 'Password reset link has been sent to your email. (Demo: ' . $resetLink . ')';
            } else {
                // Don't reveal if email exists or not (security best practice)
                $success = 'If an account exists with that email, a password reset link has been sent.';
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred. Please try again later.';
        }
    }
}

$pageTitle = 'Forgot Password';
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
            Reset your password
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Remember your password?
            <a href="/Multi-Vendor-Rental-System/public/login.php" class="font-medium text-primary-600 hover:text-primary-500">
                Sign in
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
            <?php endif; ?>

            <?php if (!$success): ?>
                <div class="mb-6">
                    <p class="text-sm text-gray-600">
                        Enter your email address and we'll send you a link to reset your password.
                    </p>
                </div>

                <form method="POST" class="space-y-6">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Email address
                        </label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input id="email" name="email" type="email" required autofocus
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                   placeholder="Enter your email">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit"
                                class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all transform hover:scale-105">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Send Reset Link
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Back to Login -->
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">Or</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="/Multi-Vendor-Rental-System/public/login.php"
                       class="w-full flex justify-center items-center py-3 px-4 border-2 border-gray-300 rounded-lg shadow-sm text-base font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Login
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
