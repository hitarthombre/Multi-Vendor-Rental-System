<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Repositories\WishlistRepository;
use RentalPlatform\Services\ProductDiscoveryService;

// Start session
Session::start();

// For demo purposes, if no user is logged in, use the first customer in the database
$customerId = null;
if (Session::isAuthenticated()) {
    $customerId = Session::getUserId();
} else {
    // Get first customer from database for demo
    try {
        $db = \RentalPlatform\Database\Connection::getInstance();
        $stmt = $db->query("SELECT id FROM users WHERE role = 'Customer' LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $customerId = $row['id'];
        }
    } catch (Exception $e) {
        $error = "Unable to determine customer ID";
        $customerId = null;
    }
}

if (!$customerId) {
    header('Location: login.php');
    exit;
}

$wishlistRepo = new WishlistRepository();
$discoveryService = new ProductDiscoveryService();

// Handle wishlist actions
if (($_POST['action'] ?? '') === 'remove' && !empty($_POST['product_id'])) {
    $wishlistRepo->remove($customerId, $_POST['product_id']);
    header('Location: wishlist.php');
    exit;
}

if (($_POST['action'] ?? '') === 'clear') {
    $wishlistRepo->clearByCustomer($customerId);
    header('Location: wishlist.php');
    exit;
}

try {
    $wishlistItems = $wishlistRepo->findWithProductDetails($customerId);
    $wishlistCount = count($wishlistItems);
} catch (Exception $e) {
    $error = "Error loading wishlist: " . $e->getMessage();
    $wishlistItems = [];
    $wishlistCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Multi-Vendor Rental Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .nav-links {
            display: flex;
            gap: 1rem;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #333;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .nav-links a:hover {
            background: #f8f9fa;
        }
        
        .wishlist-header {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .wishlist-title {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .wishlist-subtitle {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .wishlist-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-outline {
            background: transparent;
            color: #007bff;
            border: 2px solid #007bff;
        }
        
        .btn-outline:hover {
            background: #007bff;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .wishlist-item {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .wishlist-item:hover {
            transform: translateY(-2px);
        }
        
        .item-image {
            width: 100%;
            height: 200px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 0.9rem;
        }
        
        .item-info {
            padding: 1.5rem;
        }
        
        .item-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .item-description {
            color: #666;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .item-badges {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .badge-available {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-verification {
            background: #fff3cd;
            color: #856404;
        }
        
        .item-meta {
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        
        .item-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .empty-wishlist {
            text-align: center;
            padding: 4rem 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .empty-wishlist h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #666;
        }
        
        .empty-wishlist p {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .wishlist-actions {
                flex-direction: column;
            }
            
            .item-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">RentalHub</div>
                <nav class="nav-links">
                    <a href="customer/products.php">Browse Products</a>
                    <a href="index.php">Home</a>
                    <a href="wishlist.php">Wishlist (<?= $wishlistCount ?>)</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="wishlist-header">
            <h1 class="wishlist-title">My Wishlist</h1>
            <p class="wishlist-subtitle">
                <?= $wishlistCount ?> item<?= $wishlistCount !== 1 ? 's' : '' ?> saved for later
            </p>
            
            <?php if ($wishlistCount > 0): ?>
                <div class="wishlist-actions">
                    <a href="customer/products.php" class="btn btn-outline">Continue Shopping</a>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear your entire wishlist?')">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-danger">Clear Wishlist</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($wishlistItems)): ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlistItems as $item): ?>
                    <div class="wishlist-item">
                        <div class="item-image">
                            <?php if (!empty($item['images'])): ?>
                                <img src="<?= htmlspecialchars($item['images'][0]) ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                No image available
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-info">
                            <h3 class="item-name"><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="item-description"><?= htmlspecialchars($item['description']) ?></p>
                            
                            <div class="item-badges">
                                <span class="badge badge-available">Available</span>
                                <?php if ($item['verification_required']): ?>
                                    <span class="badge badge-verification">Verification Required</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-meta">
                                Added on <?= date('M j, Y', strtotime($item['added_at'])) ?>
                            </div>
                            
                            <div class="item-actions">
                                <a href="customer/product-details.php?id=<?= htmlspecialchars($item['product_id']) ?>" 
                                   class="btn btn-primary btn-sm">View Details</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['product_id']) ?>">
                                    <button type="submit" class="btn btn-outline btn-sm">Remove</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist">
                <h3>Your wishlist is empty</h3>
                <p>Save items you're interested in to your wishlist so you can easily find them later.</p>
                <a href="customer/products.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add some interactivity
        document.querySelectorAll('form[method="POST"]').forEach(form => {
            if (form.querySelector('input[value="remove"]')) {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Remove this item from your wishlist?')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>