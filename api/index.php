<?php
/**
 * Vercel Serverless Function Router for Luxury Homes of India (LHI)
 * Dispatches dynamic blog routing and PHP form/email handlers.
 */

// Parse the request URI path
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri_parts = parse_url($request_uri);
$path = urldecode($uri_parts['path'] ?? '/');

// Set root directory path
$root_dir = realpath(__DIR__ . '/..');

// 1. Direct Blog listing (/blog or /blog/)
if ($path === '/blog' || $path === '/blog/') {
    require $root_dir . '/blog.php';
    exit;
}

// 2. Blog category (/blog/category/<name>)
if (preg_match('#^/blog/category/([^/]+)/?$#', $path, $matches)) {
    $_GET['category'] = $matches[1];
    require $root_dir . '/blog.php';
    exit;
}

// 3. Blog post detail (/blog/<slug>)
if (preg_match('#^/blog/([^/]+?)(?:\.html)?/?$#', $path, $matches)) {
    $_GET['slug'] = $matches[1];
    require $root_dir . '/blog-detail.php';
    exit;
}

// 4. Direct PHP script execution (e.g., /career_mail.php, /sendmail.php, /submit-lead.php, etc.)
$script_file = $root_dir . '/' . ltrim($path, '/');
if (file_exists($script_file) && is_file($script_file) && pathinfo($script_file, PATHINFO_EXTENSION) === 'php') {
    require $script_file;
    exit;
}

// 5. Fallback 404
http_response_code(404);
echo "404 Not Found";
?>
