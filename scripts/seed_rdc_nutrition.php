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

$sql = "INSERT INTO nutrition_facts (
            gtin, serving_size, servings_per_package, serving_grams, serving_unit,
            energy_kcal, carbohydrates_g, sugars_g, added_sugars_g, protein_g,
            total_fat_g, saturated_fat_g, trans_fat_g, fiber_g, sodium_mg
        ) VALUES (
            '07893000973305', '40 g', '13', 40, 'g',
            44, 1.5, 0, 0, 8, 0.7, 0.2, 0, 0, 281
        )
        ON CONFLICT (gtin) DO UPDATE SET
            serving_size = EXCLUDED.serving_size,
            servings_per_package = EXCLUDED.servings_per_package,
            serving_grams = EXCLUDED.serving_grams,
            serving_unit = EXCLUDED.serving_unit,
            energy_kcal = EXCLUDED.energy_kcal,
            carbohydrates_g = EXCLUDED.carbohydrates_g,
            sugars_g = EXCLUDED.sugars_g,
            added_sugars_g = EXCLUDED.added_sugars_g,
            protein_g = EXCLUDED.protein_g,
            total_fat_g = EXCLUDED.total_fat_g,
            saturated_fat_g = EXCLUDED.saturated_fat_g,
            trans_fat_g = EXCLUDED.trans_fat_g,
            fiber_g = EXCLUDED.fiber_g,
            sodium_mg = EXCLUDED.sodium_mg";
$pdo->exec($sql);
echo "nutrition upsert ok\n";
