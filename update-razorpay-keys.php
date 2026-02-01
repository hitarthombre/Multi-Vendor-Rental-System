<?php
/**
 * Update Razorpay API Keys
 * 
 * Run this script to update your Razorpay test API keys
 */

echo "Razorpay API Key Update Tool\n";
echo str_repeat("=", 60) . "\n\n";

echo "Current keys in config/razorpay.php:\n";
$config = require __DIR__ . '/config/razorpay.php';
echo "Key ID: " . $config['test']['key_id'] . "\n";
echo "Key Secret: " . substr($config['test']['key_secret'], 0, 8) . "..." . "\n\n";

echo "To update your Razorpay keys:\n";
echo "1. Go to https://dashboard.razorpay.com/\n";
echo "2. Sign in and switch to TEST MODE (toggle in top-right)\n";
echo "3. Go to Settings → API Keys\n";
echo "4. Copy your Test Key ID and Key Secret\n";
echo "5. Run this script with your new keys:\n\n";

echo "Usage:\n";
echo "  php update-razorpay-keys.php YOUR_KEY_ID YOUR_KEY_SECRET\n\n";

// Check if keys provided
if ($argc === 3) {
    $newKeyId = $argv[1];
    $newKeySecret = $argv[2];
    
    // Validate format
    if (!preg_match('/^rzp_test_[A-Za-z0-9]+$/', $newKeyId)) {
        die("Error: Invalid Key ID format. Should start with 'rzp_test_'\n");
    }
    
    if (strlen($newKeySecret) < 10) {
        die("Error: Key Secret seems too short. Please check.\n");
    }
    
    // Update config file
    $configContent = file_get_contents(__DIR__ . '/config/razorpay.php');
    
    // Replace key_id
    $configContent = preg_replace(
        "/'key_id' => 'rzp_test_[^']+'/",
        "'key_id' => '$newKeyId'",
        $configContent,
        1
    );
    
    // Replace key_secret
    $configContent = preg_replace(
        "/'key_secret' => '[^']+'/",
        "'key_secret' => '$newKeySecret'",
        $configContent,
        1
    );
    
    file_put_contents(__DIR__ . '/config/razorpay.php', $configContent);
    
    // Update CSV file
    file_put_contents(__DIR__ . '/rzp-key.csv', "key_id,key_secret\n$newKeyId,$newKeySecret\n");
    
    echo "✓ Keys updated successfully!\n\n";
    echo "New Key ID: $newKeyId\n";
    echo "New Key Secret: " . substr($newKeySecret, 0, 8) . "...\n\n";
    echo "You can now test the payment flow.\n";
    
} else {
    echo "Alternative: Use Mock Payment Mode\n";
    echo str_repeat("-", 60) . "\n";
    echo "If you want to test without valid Razorpay keys, I can create\n";
    echo "a mock payment mode that simulates successful payments.\n";
}
