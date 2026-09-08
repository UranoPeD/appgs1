<?php

declare(strict_types=1);

namespace App;

final class NutritionFields
{
    /** @var list<string> */
    public const TEXT = ['servings_per_package', 'serving_grams', 'serving_unit'];

    /** @var array<string, string> */
    public const NUMBERS = [
        'energy_kcal' => 'Valor energético (kcal)',
        'carbohydrates_g' => 'Carboidratos (g)',
        'sugars_g' => 'Açúcares totais (g)',
        'added_sugars_g' => 'Açúcares adicionados (g)',
        'protein_g' => 'Proteínas (g)',
        'total_fat_g' => 'Gorduras totais (g)',
        'saturated_fat_g' => 'Gorduras saturadas (g)',
        'trans_fat_g' => 'Gorduras trans (g)',
        'fiber_g' => 'Fibras alimentares (g)',
        'sodium_mg' => 'Sódio (mg)',
    ];

    /** @var array<string, float|null> VDR da porção — IN 75 / RDC 429 */
    public const VDR = [
        'energy_kcal' => 2000.0,
        'carbohydrates_g' => 300.0,
        'sugars_g' => null,
        'added_sugars_g' => 50.0,
        'protein_g' => 50.0,
        'total_fat_g' => 65.0,
        'saturated_fat_g' => 20.0,
        'trans_fat_g' => 2.0,
        'fiber_g' => 25.0,
        'sodium_mg' => 2000.0,
    ];

    /**
     * @return array<string, string>
     */
    public static function empty(): array
    {
        $row = [
            'serving_size' => '',
            'servings_per_package' => '',
            'serving_grams' => '',
            'serving_unit' => 'g',
        ];
        foreach (array_keys(self::NUMBERS) as $key) {
            $row[$key] = '';
        }
        return $row;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function hasValues(array $row): bool
    {
        if (isset($row['serving_grams']) && trim((string) $row['serving_grams']) !== '') {
            return true;
        }
        if (isset($row['servings_per_package']) && trim((string) $row['servings_per_package']) !== '') {
            return true;
        }
        foreach (array_keys(self::NUMBERS) as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param mixed $value
     */
    public static function formatNumber($value, int $decimals = 1): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $number = (float) $value;
        if ($decimals === 0) {
            return (string) (int) round($number);
        }
        $formatted = number_format($number, $decimals, ',', '.');
        return rtrim(rtrim($formatted, '0'), ',');
    }

    /**
     * @param mixed $portion
     */
    public static function per100g($portion, float $servingGrams): ?float
    {
        if ($portion === null || $portion === '' || $servingGrams <= 0) {
            return null;
        }
        return round(((float) $portion) * 100 / $servingGrams, 1);
    }

    /**
     * @param mixed $portion
     */
    public static function vdPercent($portion, ?float $vdr): ?int
    {
        if ($vdr === null || $vdr <= 0 || $portion === null || $portion === '') {
            return null;
        }
        return (int) round(((float) $portion) * 100 / $vdr);
    }

    public static function servingGrams(array $nutrition): float
    {
        $raw = str_replace(',', '.', trim((string) ($nutrition['serving_grams'] ?? '')));
        if ($raw !== '' && is_numeric($raw)) {
            return (float) $raw;
        }
        if (preg_match('/(\d+(?:[.,]\d+)?)/', (string) ($nutrition['serving_size'] ?? ''), $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }
        return 0.0;
    }

    public static function servingUnit(array $nutrition): string
    {
        $unit = strtolower(trim((string) ($nutrition['serving_unit'] ?? 'g')));
        return $unit === 'ml' ? 'ml' : 'g';
    }

    /**
     * Código 1–7 da planilha ALERTAS (0 = sem alerta).
     */
    public static function fopCode(array $nutrition): int
    {
        $grams = self::servingGrams($nutrition);
        if ($grams <= 0) {
            return 0;
        }
        $unit = self::servingUnit($nutrition);
        $added = self::per100g($nutrition['added_sugars_g'] ?? '', $grams);
        $sat = self::per100g($nutrition['saturated_fat_g'] ?? '', $grams);
        $sodium = self::per100g($nutrition['sodium_mg'] ?? '', $grams);

        $sugarLimit = $unit === 'ml' ? 7.5 : 15.0;
        $satLimit = $unit === 'ml' ? 3.0 : 6.0;
        $sodiumLimit = $unit === 'ml' ? 300.0 : 600.0;

        $cSugar = ($added !== null && $added >= $sugarLimit) ? 1 : 0;
        $cSat = ($sat !== null && $sat >= $satLimit) ? 1 : 0;
        $cSodium = ($sodium !== null && $sodium >= $sodiumLimit) ? 1 : 0;

        if ($cSugar && $cSat && $cSodium) {
            return 7;
        }
        if (!$cSugar && $cSat && $cSodium) {
            return 6;
        }
        if ($cSugar && !$cSat && $cSodium) {
            return 5;
        }
        if ($cSugar && $cSat && !$cSodium) {
            return 4;
        }
        if (!$cSugar && !$cSat && $cSodium) {
            return 3;
        }
        if (!$cSugar && $cSat && !$cSodium) {
            return 2;
        }
        if ($cSugar && !$cSat && !$cSodium) {
            return 1;
        }
        return 0;
    }

    /**
     * Selo FOP oficial (RDC 429), códigos 1–7.
     */
    public static function fopImage(int $code): ?string
    {
        if ($code < 1 || $code > 7) {
            return null;
        }
        return '/img/fop/' . $code . '.png';
    }

    public static function fopAlt(int $code): string
    {
        $map = [
            1 => 'Alto em açúcar adicionado',
            2 => 'Alto em gordura saturada',
            3 => 'Alto em sódio',
            4 => 'Alto em açúcar adicionado e gordura saturada',
            5 => 'Alto em açúcar adicionado e sódio',
            6 => 'Alto em gordura saturada e sódio',
            7 => 'Alto em açúcar adicionado, gordura saturada e sódio',
        ];
        return $map[$code] ?? 'Alerta nutricional';
    }

    /**
     * kJ a partir de kcal (fator 4,184 da RDC 429).
     *
     * @param mixed $kcal
     */
    public static function energyKj($kcal): ?float
    {
        if ($kcal === null || $kcal === '') {
            return null;
        }
        return round(((float) $kcal) * 4.184, 0);
    }

    public static function saleType(array $product): string
    {
        $type = strtolower(trim((string) ($product['sale_type'] ?? 'weight')));
        return $type === 'unit' ? 'unit' : 'weight';
    }

    /**
     * Conteúdo do item (g ou ml) para cálculo de porções: QR (310n/315n) ou cadastro.
     *
     * @param array<string, mixed> $product
     * @param array<string, mixed> $nutrition
     */
    public static function itemContentAmount(array $product, array $nutrition, ?float $qrWeightGrams, ?float $qrVolumeMl): ?float
    {
        $unit = self::servingUnit($nutrition);
        if ($unit === 'ml') {
            if ($qrVolumeMl !== null && $qrVolumeMl > 0) {
                return $qrVolumeMl;
            }
            if ($qrWeightGrams !== null && $qrWeightGrams > 0) {
                return $qrWeightGrams;
            }
        } elseif ($qrWeightGrams !== null && $qrWeightGrams > 0) {
            return $qrWeightGrams;
        }

        $fallback = str_replace(',', '.', trim((string) ($product['net_weight_g'] ?? '')));
        if ($fallback !== '' && is_numeric($fallback) && (float) $fallback > 0) {
            return (float) $fallback;
        }
        return null;
    }

    /**
     * Porções por embalagem: pesável = arredonda(conteúdo / porção); unidade = quantidade.
     *
     * @param array<string, mixed> $product
     * @param array<string, mixed> $nutrition
     */
    public static function servingsPerPackage(array $product, array $nutrition, ?float $qrWeightGrams, ?float $qrVolumeMl): ?int
    {
        if (self::saleType($product) === 'unit') {
            $qty = (int) ($product['package_quantity'] ?? 0);
            return $qty > 0 ? $qty : null;
        }

        $serving = self::servingGrams($nutrition);
        $amount = self::itemContentAmount($product, $nutrition, $qrWeightGrams, $qrVolumeMl);
        if ($serving <= 0 || $amount === null || $amount <= 0) {
            return null;
        }

        $rounded = (int) round($amount / $serving, 0, PHP_ROUND_HALF_UP);
        return $rounded > 0 ? $rounded : 1;
    }
}
