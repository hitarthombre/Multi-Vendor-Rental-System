<?php
require 'vendor/autoload.php';

$db = \RentalPlatform\Database\Connection::getInstance();
$stmt = $db->query('DESCRIBE variants');

echo "Variants table structure:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
