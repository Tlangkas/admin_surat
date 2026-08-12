<?php

/**
 * Router script for PHP built-in development server.
 *
 * Digunakan saat menjalankan `php -S localhost:8000 -t public server.php`.
 * Berfungsi untuk:
 *   1. Serve static files langsung dari folder public/ jika ada.
 *   2. Meneruskan request ke public/index.php untuk ditangani Laravel.
 *   3. Meneruskan request /livewire/update ke Laravel (tanpa 404).
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files if they exist.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

// Otherwise let Laravel handle the request.
require_once __DIR__.'/public/index.php';
