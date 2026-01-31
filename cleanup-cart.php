<?php
require_once 'vendor/autoload.php';

use RentalPlatform\Database\Connection;

$db = Connection::getInstance();

// Delete cart items with NULL product_id or variant_id
$stmt1 = $db->exec('DELETE FROM cart_items WHERE product_id IS NULL');
echo "Deleted cart items with NULL product_id\n";

$stmt2 = $db->exec('DELETE FROM cart_items WHERE variant_id IS NULL');
echo "Deleted cart items with NULL variant_id\n";

echo "Cart cleanup completed successfully!\n";
