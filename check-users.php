<?php
require 'vendor/autoload.php';

$db = \RentalPlatform\Database\Connection::getInstance();
$stmt = $db->query("SELECT id, username, role FROM users WHERE role = 'Customer' LIMIT 5");

echo "Available Customer Users:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['id'] . ' - ' . $row['username'] . ' (' . $row['role'] . ')' . "\n";
}
