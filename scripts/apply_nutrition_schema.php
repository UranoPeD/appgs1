<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Env.php';
require dirname(__DIR__) . '/src/ProductRepository.php';
require dirname(__DIR__) . '/src/NutritionFields.php';

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

$pdo->exec("alter table public.nutrition_facts add column if not exists serving_grams numeric(10, 2)");
$pdo->exec("alter table public.nutrition_facts add column if not exists serving_unit text not null default 'g'");

$pdo->prepare(
    "UPDATE nutrition_facts SET
        serving_size = '40 g',
        servings_per_package = '13',
        serving_grams = 40,
        serving_unit = 'g',
        energy_kcal = 44,
        carbohydrates_g = 1.5,
        sugars_g = 0,
        added_sugars_g = 0,
        protein_g = 8,
        total_fat_g = 0.7,
        saturated_fat_g = 0.2,
        trans_fat_g = 0,
        fiber_g = 0,
        sodium_mg = 281,
        updated_at = now()
     WHERE gtin = :gtin"
)->execute(['gtin' => '07893336004902']);

echo "nutrition_facts columns: ok\n";
