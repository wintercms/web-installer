<?php

use Winter\Installer\Api;

// Autoload Composer dependencies
require_once __DIR__ . '/install/api/vendor/autoload.php';

$api = new Api;
$api->request();