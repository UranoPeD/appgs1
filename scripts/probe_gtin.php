<?php

declare(strict_types=1);

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

use App\Env;
use App\ProductRepository;
use App\Gs1\DigitalLinkParser;

Env::load(dirname(__DIR__) . '/.env');

$link = DigitalLinkParser::fromRequest('/01/07893336004902', []);
echo 'gtin=' . var_export($link->gtin(), true) . PHP_EOL;
echo 'empty=' . ($link->isEmpty() ? '1' : '0') . PHP_EOL;
$repo = new ProductRepository();
$row = $repo->findByGtin((string) $link->gtin());
echo 'found=' . ($row ? $row['name'] : 'null') . PHP_EOL;
echo 'err=' . ($repo->lastError() ?: 'none') . PHP_EOL;
