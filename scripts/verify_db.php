<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Env.php';
require dirname(__DIR__) . '/src/NutritionFields.php';
require dirname(__DIR__) . '/src/ProductRepository.php';

use App\Env;

Env::load(dirname(__DIR__) . '/.env');

$hasUrl = Env::get('DATABASE_URL') !== '';
$hasToken = Env::get('APP_ADMIN_TOKEN') !== '';
$pdo = extension_loaded('pdo_pgsql');
$pgsql = extension_loaded('pgsql');

echo 'env_database_url: ' . ($hasUrl ? 'ok' : 'missing') . PHP_EOL;
echo 'env_admin_token: ' . ($hasToken ? 'ok' : 'missing') . PHP_EOL;
echo 'ext_pdo_pgsql: ' . ($pdo ? 'ok' : 'missing') . PHP_EOL;
echo 'ext_pgsql: ' . ($pgsql ? 'ok' : 'missing') . PHP_EOL;

if (!$hasUrl) {
    fwrite(STDERR, "Preencha DATABASE_URL no .env\n");
    exit(2);
}

if (!$pdo) {
    fwrite(STDERR, "Ative pdo_pgsql no php.ini do XAMPP\n");
    exit(3);
}

$repo = new App\ProductRepository();
$rows = $repo->all();
$err = $repo->lastError();

if ($err !== null) {
    $safe = preg_replace('/:[^:@]+@/', ':***@', $err);
    echo 'db: error' . PHP_EOL;
    echo 'db_message: ' . $safe . PHP_EOL;
    exit(4);
}

echo 'db: ok' . PHP_EOL;
echo 'products: ' . count($rows) . PHP_EOL;
exit(0);
