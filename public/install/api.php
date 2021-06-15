<?php

use Winter\Installer\Api;

// Autoload Composer dependencies
require_once __DIR__ . '/api/vendor/autoload.php';

// Process API request
$api = new Api;
$api->request();