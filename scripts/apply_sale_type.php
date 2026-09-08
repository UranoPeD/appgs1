<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Env.php';
require dirname(__DIR__) . '/src/ProductRepository.php';

use App\Env;

Env::load(dirname(__DIR__) . '/.env');
$repo = new App\ProductRepository();
$ref = new ReflectionClass($repo);
$method = $ref->getMethod('connection');
$method->setAccessible(true);
$pdo = $method->invoke($repo);
if (!$pdo instanceof PDO) {
    fwrite(STDERR, ($repo->lastError() ?: 'sem conexão') . PHP_EOL);
    exit(1);
}

$pdo->exec("alter table public.products add column if not exists sale_type text not null default 'weight'");
$pdo->exec('alter table public.products add column if not exists package_quantity integer');
$pdo->exec('alter table public.products add column if not exists net_weight_g numeric(12, 3)');
$pdo->exec("update public.products set sale_type = 'weight' where sale_type is null or sale_type = ''");

echo "sale_type columns: ok\n";
