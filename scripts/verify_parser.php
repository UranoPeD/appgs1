<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Gs1/ApplicationIdentifiers.php';
require dirname(__DIR__) . '/src/Gs1/DigitalLinkParser.php';

use App\Gs1\DigitalLinkParser;

putenv('APP_CURRENCY=BRL');

$uri = '/01/07893336004902/10/0000000280222/21/1395270000270?11=220218&17=220228&3103=000500&3922=2495';
$query = [
    '11' => '220218',
    '17' => '220228',
    '3103' => '000500',
    '3922' => '2495',
];

$link = DigitalLinkParser::fromRequest($uri, $query);
$byLabel = [];
foreach ($link->displayRows() as $row) {
    $byLabel[$row['ai']] = $row['display'];
}

$checks = [
    'gtin' => $link->gtin() === '07893336004902',
    'lote' => ($byLabel['10'] ?? '') === '0000000280222',
    'serie' => ($byLabel['21'] ?? '') === '1395270000270',
    'prod' => ($byLabel['11'] ?? '') === '18/02/2022',
    'valid' => ($byLabel['17'] ?? '') === '28/02/2022',
    'peso' => ($byLabel['3103'] ?? '') === '0,500 kg',
    'preco' => ($byLabel['3922'] ?? '') === 'R$ 24,95',
    'expired' => $link->expiryStatus() === 'expired',
    'grams' => $link->netWeightGrams() === 500.0,
];

$failed = array_keys(array_filter($checks, static fn ($ok) => $ok === false));
if ($failed !== []) {
    fwrite(STDERR, "FAIL: " . implode(', ', $failed) . PHP_EOL);
    fwrite(STDERR, json_encode($byLabel, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
    exit(1);
}

echo "OK parser exemplo GS1\n";

require dirname(__DIR__) . '/src/NutritionFields.php';

$nutrition = ['serving_grams' => '40', 'serving_unit' => 'g'];
$weightProduct = ['sale_type' => 'weight', 'net_weight_g' => ''];
$unitProduct = ['sale_type' => 'unit', 'package_quantity' => '8'];

$portionChecks = [
    'p13' => App\NutritionFields::servingsPerPackage($weightProduct, $nutrition, 500.0, null) === 13,
    'p25' => App\NutritionFields::servingsPerPackage($weightProduct, $nutrition, 1000.0, null) === 25,
    'u8' => App\NutritionFields::servingsPerPackage($unitProduct, $nutrition, null, null) === 8,
];
$failedPortions = array_keys(array_filter($portionChecks, static fn ($ok) => $ok === false));
if ($failedPortions !== []) {
    fwrite(STDERR, 'FAIL portions: ' . implode(', ', $failedPortions) . PHP_EOL);
    exit(1);
}

echo "OK porções por embalagem\n";
