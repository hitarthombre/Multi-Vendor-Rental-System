<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\AuthService;
use RentalPlatform\Models\User;
use RentalPlatform\Models\Vendor;
use RentalPlatform\Repositories\UserRepository;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Database\Connection;

Session::start();

// Redirect if already logged in
if (Session::isAuthenticated()) {
    $role = Session::getRole();
    header('Location: /Multi-Vendor-Rental-System/public/' . strtolower($role) . '/dashboard.php');
    exit;
}

$errors = [];
$success = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'role' => $_POST['role'] ?? 'Customer',
        'terms' => isset($_POST['terms']),
        // Vendor-specific fields
        'business_name' => trim($_POST['business_name'] ?? ''),
        'legal_name' => trim($_POST['legal_name'] ?? ''),
        'tax_id' => trim($_POST['tax_id'] ?? ''),
        'contact_email' => trim($_POST['contact_email'] ?? ''),
        'contact_phone' => trim($_POST['contact_phone'] ?? '')
    ];
    
    // Validation
    if (empty($formData['username'])) {
        $errors[] = 'Username is required';
    } elseif (strlen($formData['username']) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }
    
    if (empty($formData['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($formData['password'])) {
        $errors[] = 'Password is required';
    } elseif (strlen($formData['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    if ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!in_array($formData['role'], ['Customer', 'Vendor'])) {
        $errors[] = 'Invalid role selected';
    }
    
    // Vendor-specific validation
    if ($formData['role'] === 'Vendor') {
        if (empty($formData['business_name'])) {
            $errors[] = 'Business name is required for vendors';
        }
        if (empty($formData['legal_name'])) {
            $errors[] = 'Legal name is required for vendors';
        }
        if (!empty($formData['contact_email']) && !filter_var($formData['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid contact email format';
        }
    }
    
    if (!$formData['terms']) {
        $errors[] = 'You must accept the terms and conditions';
    }
    
    // If no errors, create user
    if (empty($errors)) {
        try {
            $userRepo = new UserRepository();
            $authService = new AuthService($userRepo);
            
            // Check if username or email exists
            if ($userRepo->usernameExists($formData['username'])) {
                $errors[] = 'Username already taken';
            } elseif ($userRepo->emailExists($formData['email'])) {
                $errors[] = 'Email already registered';
            } else {
                // Create user
                $user = User::create(
                    $formData['username'],
                    $formData['email'],
                    $formData['password'],
                    $formData['role']
                );
                
                $userRepo->create($user);
                
                // If vendor, create vendor profile
                if ($formData['role'] === 'Vendor') {
                    $vendorRepo = new VendorRepository();
                    $vendor = Vendor::create(
                        $user->getId(),
                        $formData['business_name'],
                        $formData['legal_name'],
                        !empty($formData['tax_id']) ? $formData['tax_id'] : null,
                        !empty($formData['contact_email']) ? $formData['contact_email'] : null,
                        !empty($formData['contact_phone']) ? $formData['contact_phone'] : null
                    );
                    $vendorRepo->create($vendor);
                }
                
                // Auto-login
                $result = $authService->login($formData['username'], $formData['password']);
                
                if ($result['success']) {
                    // Redirect to dashboard
                    header('Location: /Multi-Vendor-Rental-System/public/' . strtolower($formData['role']) . '/dashboard.php?welcome=1');
                    exit;
                } else {
                    $errors[] = 'Registration successful but login failed. Please try logging in.';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Create Account';
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
            Create your account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Already have an account?
            <a href="/Multi-Vendor-Rental-System/public/login.php" class="font-medium text-primary-600 hover:text-primary-500">
                Sign in
            </a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-2xl">
        <div class="bg-white py-8 px-4 shadow-2xl sm:rounded-2xl sm:px-10">
            <?php if (!empty($errors)): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded animate-slide-in">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" x-data="{ role: '<?= $formData['role'] ?? 'Customer' ?>', showPassword: false, showConfirmPassword: false }">
                <!-- Role Selection -->
                <div>
                    <label class="text-base font-medium text-gray-900 block mb-4">I want to:</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Customer Card -->
                        <label class="relative flex cursor-pointer rounded-xl border-2 p-6 transition-all"
                               :class="role === 'Customer' ? 'border-primary-600 bg-primary-50 ring-2 ring-primary-600' : 'border-gray-200 hover:border-primary-300'">
                            <input type="radio" name="role" value="Customer" class="sr-only" x-model="role">
                            <div class="flex flex-col flex-1">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                        <i class="fas fa-shopping-bag text-white text-xl"></i>
                                    </div>
                                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                         :class="role === 'Customer' ? 'border-primary-600 bg-primary-600' : 'border-gray-300'">
                                        <i class="fas fa-check text-white text-xs" x-show="role === 'Customer'"></i>
                                    </div>
                                </div>
                                <span class="block text-lg font-semibold text-gray-900">Rent Products</span>
                                <span class="mt-1 text-sm text-gray-500">Browse and rent from trusted vendors</span>
                            </div>
                        </label>

                        <!-- Vendor Card -->
                        <label class="relative flex cursor-pointer rounded-xl border-2 p-6 transition-all"
                               :class="role === 'Vendor' ? 'border-primary-600 bg-primary-50 ring-2 ring-primary-600' : 'border-gray-200 hover:border-primary-300'">
                            <input type="radio" name="role" value="Vendor" class="sr-only" x-model="role">
                            <div class="flex flex-col flex-1">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                                        <i class="fas fa-store text-white text-xl"></i>
                                    </div>
                                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                         :class="role === 'Vendor' ? 'border-primary-600 bg-primary-600' : 'border-gray-300'">
                                        <i class="fas fa-check text-white text-xs" x-show="role === 'Vendor'"></i>
                                    </div>
                                </div>
                                <span class="block text-lg font-semibold text-gray-900">List Products</span>
                                <span class="mt-1 text-sm text-gray-500">Start earning by renting your items</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">
                        Username <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input id="username" name="username" type="text" required
                               value="<?= htmlspecialchars($formData['username'] ?? '') ?>"
                               class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="johndoe">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email address <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input id="email" name="email" type="email" required
                               value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                               class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="john@example.com">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="password" name="password" :type="showPassword ? 'text' : 'password'" required
                               class="appearance-none block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="••••••••">
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
                        Confirm Password <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="confirm_password" name="confirm_password" :type="showConfirmPassword ? 'text' : 'password'" required
                               class="appearance-none block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="••••••••">
                        <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i class="fas" :class="showConfirmPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Vendor-specific fields -->
                <div x-show="role === 'Vendor'" x-cloak class="space-y-6 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900">Business Information</h3>
                    
                    <!-- Business Name -->
                    <div>
                        <label for="business_name" class="block text-sm font-medium text-gray-700">
                            Business Name <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-store text-gray-400"></i>
                            </div>
                            <input id="business_name" name="business_name" type="text"
                                   value="<?= htmlspecialchars($formData['business_name'] ?? '') ?>"
                                   :required="role === 'Vendor'"
                                   class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                   placeholder="Your Business Name">
                        </div>
                    </div>

                    <!-- Legal Name -->
                    <div>
                        <label for="legal_name" class="block text-sm font-medium text-gray-700">
                            Legal Name <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-building text-gray-400"></i>
                            </div>
                            <input id="legal_name" name="legal_name" type="text"
                                   value="<?= htmlspecialchars($formData['legal_name'] ?? '') ?>"
                                   :required="role === 'Vendor'"
                                   class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                   placeholder="Legal Business Name">
                        </div>
                    </div>

                    <!-- Tax ID (Optional) -->
                    <div>
                        <label for="tax_id" class="block text-sm font-medium text-gray-700">
                            Tax ID / GST Number <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-file-invoice text-gray-400"></i>
                            </div>
                            <input id="tax_id" name="tax_id" type="text"
                                   value="<?= htmlspecialchars($formData['tax_id'] ?? '') ?>"
                                   class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                   placeholder="Enter your tax ID">
                        </div>
                    </div>

                    <!-- Contact Email (Optional) -->
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-gray-700">
                            Business Contact Email <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input id="contact_email" name="contact_email" type="email"
                                   value="<?= htmlspecialchars($formData['contact_email'] ?? '') ?>"
                                   class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                   placeholder="business@example.com">
                        </div>
                    </div>

                    <!-- Contact Phone (Optional) -->
                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-gray-700">
                            Business Contact Phone <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-400"></i>
                            </div>
                            <input id="contact_phone" name="contact_phone" type="tel"
                                   value="<?= htmlspecialchars($formData['contact_phone'] ?? '') ?>"
                                   class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                   placeholder="+1 (555) 123-4567">
                        </div>
                    </div>
                </div>

                <!-- Terms -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input id="terms" name="terms" type="checkbox" required
                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded cursor-pointer">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="terms" class="font-medium text-gray-700 cursor-pointer">
                            I agree to the <a href="/Multi-Vendor-Rental-System/public/terms.php" class="text-primary-600 hover:text-primary-500">Terms and Conditions</a> and <a href="/Multi-Vendor-Rental-System/public/privacy.php" class="text-primary-600 hover:text-primary-500">Privacy Policy</a>
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit"
                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all transform hover:scale-105">
                        <i class="fas fa-user-plus mr-2"></i>
                        Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/base.php';
?>
