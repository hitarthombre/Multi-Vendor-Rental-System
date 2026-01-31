<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Auth\Session;

Session::start();
Session::destroy();

header('Location: /Multi-Vendor-Rental-System/public/index.php?logout=1');
exit;
