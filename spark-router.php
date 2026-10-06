<?php

declare(strict_types=1);

// Match the Apache configuration: old CodeIgniter 3 releases emit PHP 8.2
// deprecation notices in development mode, corrupting JSON API responses.
$_SERVER['CI_ENV'] = $_SERVER['CI_ENV'] ?? getenv('CI_ENV') ?: 'production';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, rawurldecode($path ?: '/'));

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
