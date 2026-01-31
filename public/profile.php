<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\UserRepository;
use RentalPlatform\Repositories\VendorRepository;

Session::start();
Middleware::requireAuth();

$userId = Session::getUserId();
$userRepo = new UserRepository();
$user = $userRepo->findById($userId);

if (!$user) {
    header('Location: /Multi-Vendor-Rental-System/public/logout.php');
    exit;
}

$errors = [];
$success = '';
$vendorProfile = null;

// Get vendor profile if user is a vendor
if ($user->isVendor()) {
    $vendorRepo = new VendorRepository();
    $vendorProfile = $vendorRepo->findByUserId($userId);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Validation
        if (empty($username)) {
            $errors[] = 'Username is required';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Check if username/email is taken by another user
        if ($username !== $user->getUsername()) {
            if ($userRepo->usernameExists($username)) {
                $errors[] = 'Username already taken';
            }
        }
        
        if ($email !== $user->getEmail()) {
            if ($userRepo->emailExists($email)) {
                $errors[] = 'Email already registered';
            }
        }
        
        if (empty($errors)) {
            try {
                // Update user (we need to create a new User object with updated data)
                $updatedUser = new User(
                    $user->getId(),
                    $username,
                    $email,
                    $user->getPasswordHash(),
                    $user->getRole(),
                    $user->getCreatedAt(),
                    date('Y-m-d H:i:s')
                );
                
                $userRepo->update($updatedUser);
                
                // Update session
                Session::destroy();
                Session::create($updatedUser);
                
                $success = 'Profile updated successfully!';
                $user = $updatedUser;
            } catch (Exception $e) {
                $errors[] = 'Failed to update profile: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        } elseif (!$user->verifyPassword($currentPassword)) {
            $errors[] = 'Current password is incorrect';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        if (empty($errors)) {
            try {
                $newPasswordHash = User::hashPassword($newPassword);
                $userRepo->updatePassword($userId, $newPasswordHash);
                $success = 'Password changed successfully!';
            } catch (Exception $e) {
                $errors[] = 'Failed to change password: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'update_vendor_profile' && $user->isVendor() && $vendorProfile) {
        $businessName = trim($_POST['business_name'] ?? '');
        $legalName = trim($_POST['legal_name'] ?? '');
        $taxId = trim($_POST['tax_id'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        $brandColor = trim($_POST['brand_color'] ?? '#3b82f6');
        
        // Validation
        if (empty($businessName)) {
            $errors[] = 'Business name is required';
        }
        
        if (empty($legalName)) {
            $errors[] = 'Legal name is required';
        }
        
        if (!empty($contactEmail) && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid contact email format';
        }
        
        if (empty($errors)) {
            try {
                $vendorProfile->setBusinessName($businessName);
                $vendorProfile->setLegalName($legalName);
                $vendorProfile->setTaxId($taxId ?: null);
                $vendorProfile->setContactEmail($contactEmail ?: null);
                $vendorProfile->setContactPhone($contactPhone ?: null);
                $vendorProfile->setBrandColor($brandColor);
                
                $vendorRepo = new VendorRepository();
                $vendorRepo->update($vendorProfile);
                
                $success = 'Business profile updated successfully!';
            } catch (Exception $e) {
                $errors[] = 'Failed to update business profile: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'My Profile';
$showNav = true;
$showContainer = true;

ob_start();
?>

<!-- Success/Error Messages -->
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
                <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
    <p class="mt-2 text-gray-600">Manage your account settings and preferences</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile Sidebar -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="text-center">
                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-3xl font-bold mx-auto mb-4">
                    <?= strtoupper(substr($user->getUsername(), 0, 2)) ?>
                </div>
                <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($user->getUsername()) ?></h2>
                <p class="text-sm text-gray-500"><?= htmlspecialchars($user->getEmail()) ?></p>
                <span class="inline-flex items-center rounded-full bg-primary-100 px-3 py-1 text-sm font-medium text-primary-800 mt-3">
                    <?= htmlspecialchars($user->getRole()) ?>
                </span>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Member since</span>
                        <span class="font-medium text-gray-900"><?= date('M Y', strtotime($user->getCreatedAt())) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Last updated</span>
                        <span class="font-medium text-gray-900"><?= date('M d, Y', strtotime($user->getUpdatedAt())) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Forms -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Account Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Information</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_profile">
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" id="username" name="username" required
                           value="<?= htmlspecialchars($user->getUsername()) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($user->getEmail()) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                
                <div>
                    <button type="submit"
                            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="change_password">
                
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <div class="relative">
                        <input type="password" id="current_password" name="current_password" required
                               class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <button type="button" onclick="togglePasswordVisibility('current_password')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i id="current_password_icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        <input type="password" id="new_password" name="new_password" required
                               class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <button type="button" onclick="togglePasswordVisibility('new_password')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i id="new_password_icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters</p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <button type="button" onclick="togglePasswordVisibility('confirm_password')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i id="confirm_password_icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div>
                    <button type="submit"
                            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-key mr-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>

        <!-- Vendor Business Profile (if vendor) -->
        <?php if ($user->isVendor() && $vendorProfile): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Business Profile</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_vendor_profile">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                        <input type="text" id="business_name" name="business_name" required
                               value="<?= htmlspecialchars($vendorProfile->getBusinessName()) ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="legal_name" class="block text-sm font-medium text-gray-700 mb-1">Legal Name</label>
                        <input type="text" id="legal_name" name="legal_name" required
                               value="<?= htmlspecialchars($vendorProfile->getLegalName()) ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>
                
                <div>
                    <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-1">Tax ID / GST Number</label>
                    <input type="text" id="tax_id" name="tax_id"
                           value="<?= htmlspecialchars($vendorProfile->getTaxId() ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email"
                               value="<?= htmlspecialchars($vendorProfile->getContactEmail() ?? '') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                        <input type="tel" id="contact_phone" name="contact_phone"
                               value="<?= htmlspecialchars($vendorProfile->getContactPhone() ?? '') ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>
                
                <div>
                    <label for="brand_color" class="block text-sm font-medium text-gray-700 mb-1">Brand Color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" id="brand_color" name="brand_color"
                               value="<?= htmlspecialchars($vendorProfile->getBrandColor() ?? '#3b82f6') ?>"
                               class="h-10 w-20 border border-gray-300 rounded cursor-pointer">
                        <span class="text-sm text-gray-500">Choose your brand color for dashboard theming</span>
                    </div>
                </div>
                
                <div>
                    <button type="submit"
                            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>Save Business Profile
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Password visibility toggle
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/modern-base.php';
?>
