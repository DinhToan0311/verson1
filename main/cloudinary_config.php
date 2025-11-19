<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Chỉnh đường dẫn nếu cần
require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Configuration\Configuration;

Configuration::instance([
    'cloud' => [
        'cloud_name' => 'dz5rz7doo',
        'api_key'    => '647596293119175',
        'api_secret' => 'quIaPT1lkvOvQz8xT7CicRJ4yd0'
    ],
    'url' => [
        'secure' => true
    ]
]);
