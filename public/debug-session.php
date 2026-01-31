<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Auth\Session;

Session::start();

echo "<h1>Session Debug Information</h1>";
echo "<pre>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "ACTIVE" : "INACTIVE") . "\n";
echo "Session ID: " . session_id() . "\n\n";

echo "Session Data:\n";
print_r($_SESSION);

echo "\n\nSession::isAuthenticated(): " . (Session::isAuthenticated() ? "TRUE" : "FALSE") . "\n";
echo "Session::getUserId(): " . (Session::getUserId() ?? "NULL") . "\n";
echo "Session::getUsername(): " . (Session::getUsername() ?? "NULL") . "\n";
echo "Session::getRole(): " . (Session::getRole() ?? "NULL") . "\n";

echo "\n\nServer Info:\n";
echo "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "\n";

echo "\n\nSession Array:\n";
print_r(Session::toArray());

echo "</pre>";
?>
