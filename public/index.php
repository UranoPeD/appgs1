<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_string($path) && $path !== '/' && is_file(__DIR__ . $path)) {
        return false;
    }
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = dirname(__DIR__) . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\AdminController;
use App\Env;
use App\Http;
use App\ProductRepository;
use App\Gs1\DigitalLinkParser;

Env::load(dirname(__DIR__) . '/.env');

$requestPath = Http::path();

if ($requestPath === '/health') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    exit;
}

if ($requestPath === '/admin' || strpos($requestPath, '/admin/') === 0) {
    AdminController::handle($requestPath);
    exit;
}

$appName = Env::get('APP_NAME', 'Smart Label Urano');
$logoUrl = Env::get('APP_LOGO_URL');
$link = DigitalLinkParser::fromRequest($_SERVER['REQUEST_URI'] ?? '/', $_GET);

$product = null;
$dbError = null;
$template = 'home';

if (!$link->isEmpty()) {
    if (!$link->hasGtin()) {
        $template = 'not-found';
        http_response_code(400);
    } else {
        $template = 'product';
        $repo = new ProductRepository();
        $product = $repo->findByGtin((string) $link->gtin());
        $dbError = $repo->lastError();
    }
}

require dirname(__DIR__) . '/templates/layout.php';
