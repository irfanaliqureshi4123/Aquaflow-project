<?php
/**
 * Router for PHP Built-in Server
 * 
 * This router serves static files directly and routes everything else to index.php
 * Usage: php -S localhost:3000 router.php
 */

$requested_file = $_SERVER['REQUEST_URI'];
$requested_path = parse_url($requested_file, PHP_URL_PATH);

// Remove /aquaWater prefix if it exists in the path
$request_path = str_replace('/aquaWater', '', $requested_path);

// Define the public root
$public_root = __DIR__;

// Handle static files
$static_extensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'mp4', 'webm', 'woff', 'woff2', 'ttf', 'eot', 'ico'];

foreach ($static_extensions as $ext) {
    if (preg_match("/\." . $ext . "$/i", $request_path)) {
        // Check if file exists
        $file_path = $public_root . $request_path;
        if (file_exists($file_path) && is_file($file_path)) {
            // Serve the file
            return false;
        }
    }
}

// Route everything else to index.php
require_once __DIR__ . '/index.php';
