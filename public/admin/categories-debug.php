<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Auth\UnauthorizedException;

Session::start();

echo "<h1>Categories Page Debug</h1>";
echo "<pre>";

echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "ACTIVE" : "INACTIVE") . "\n";
echo "Session ID: " . session_id() . "\n\n";

echo "Session::isAuthenticated(): " . (Session::isAuthenticated() ? "TRUE" : "FALSE") . "\n";
echo "Session::getUserId(): " . (Session::getUserId() ?? "NULL") . "\n";
echo "Session::getRole(): " . (Session::getRole() ?? "NULL") . "\n";
echo "Session::isAdministrator(): " . (Session::isAdministrator() ? "TRUE" : "FALSE") . "\n\n";

echo "Raw Session Data:\n";
print_r($_SESSION);

echo "\n\nTrying Middleware::requireAdministrator()...\n";

try {
    Middleware::requireAdministrator();
    echo "SUCCESS! Authentication passed.\n";
} catch (UnauthorizedException $e) {
    echo "FAILED! Exception: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}

echo "</pre>";
?>
