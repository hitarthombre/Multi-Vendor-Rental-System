<?php
/**
 * Multi-Vendor Rental Platform
 * Entry Point
 */

// Display errors in development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Set timezone
date_default_timezone_set('UTC');

// Start session
session_start();

// Simple routing for now
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Basic response
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'Multi-Vendor Rental Platform API',
    'version' => '1.0.0',
    'timestamp' => date('Y-m-d H:i:s'),
]);
