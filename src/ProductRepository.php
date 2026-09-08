<?php

declare(strict_types=1);

namespace App;

require_once __DIR__ . '/NutritionFields.php';

use PDO;
use PDOException;

final class ProductRepository
{
    private const COLUMNS = 'gtin, name, brand, description, image_url, ingredients, allergens, origin, manufacturer, website_url, recycling_notes, extra_json, sale_type, package_quantity, net_weight_g';

    private ?PDO $pdo = null;
    private ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $pdo = $this->connection();
        if ($pdo === null) {
            return [];
        }

        try {
            $stmt = $pdo->query(
                'SELECT ' . self::COLUMNS . ' FROM products ORDER BY name ASC, gtin ASC'
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $row) {
                $out[] = $this->hydrate($row, false);
            }
            return $out;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByGtin(string $gtin): ?array
    {
        $pdo = $this->connection();
        if ($pdo === null) {
            return null;
        }

        try {
            $stmt = $pdo->prepare(
                'SELECT ' . self::COLUMNS . ' FROM products WHERE gtin = :gtin LIMIT 1'
            );
            $stmt->execute(['gtin' => $gtin]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                return null;
            }
            return $this->hydrate($row, true);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data): bool
    {
        $pdo = $this->connection();
        if ($pdo === null) {
            return false;
        }

        try {
            $pdo->beginTransaction();
            $original = isset($data['original_gtin']) ? (string) $data['original_gtin'] : '';
            $gtin = (string) $data['gtin'];
            if ($original !== '' && $original !== $gtin) {
                $conflict = $this->findByGtin($gtin);
                if ($conflict !== null) {
                    $pdo->rollBack();
                    $this->lastError = 'Já existe um produto com este GTIN.';
                    return false;
                }
                $this->insertProduct($pdo, $data);
                $move = $pdo->prepare('UPDATE nutrition_facts SET gtin = :new_gtin WHERE gtin = :old_gtin');
                $move->execute(['new_gtin' => $gtin, 'old_gtin' => $original]);
                $del = $pdo->prepare('DELETE FROM products WHERE gtin = :gtin');
                $del->execute(['gtin' => $original]);
            } else {
                $this->upsertProduct($pdo, $data);
            }
            $this->syncNutrition($pdo, $gtin, isset($data['nutrition']) && is_array($data['nutrition']) ? $data['nutrition'] : []);
            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function upsertProduct(PDO $pdo, array $data): void
    {
        $sql = 'INSERT INTO products (
                    gtin, name, brand, description, image_url, ingredients, allergens,
                    origin, manufacturer, website_url, recycling_notes, extra_json,
                    sale_type, package_quantity, net_weight_g, updated_at
                ) VALUES (
                    :gtin, :name, :brand, :description, :image_url, :ingredients, :allergens,
                    :origin, :manufacturer, :website_url, :recycling_notes, CAST(:extra_json AS jsonb),
                    :sale_type, :package_quantity, :net_weight_g, now()
                )
                ON CONFLICT (gtin) DO UPDATE SET
                    name = EXCLUDED.name,
                    brand = EXCLUDED.brand,
                    description = EXCLUDED.description,
                    image_url = EXCLUDED.image_url,
                    ingredients = EXCLUDED.ingredients,
                    allergens = EXCLUDED.allergens,
                    origin = EXCLUDED.origin,
                    manufacturer = EXCLUDED.manufacturer,
                    website_url = EXCLUDED.website_url,
                    recycling_notes = EXCLUDED.recycling_notes,
                    extra_json = EXCLUDED.extra_json,
                    sale_type = EXCLUDED.sale_type,
                    package_quantity = EXCLUDED.package_quantity,
                    net_weight_g = EXCLUDED.net_weight_g,
                    updated_at = now()';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($this->productParams($data));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function insertProduct(PDO $pdo, array $data): void
    {
        $sql = 'INSERT INTO products (
                    gtin, name, brand, description, image_url, ingredients, allergens,
                    origin, manufacturer, website_url, recycling_notes, extra_json,
                    sale_type, package_quantity, net_weight_g, updated_at
                ) VALUES (
                    :gtin, :name, :brand, :description, :image_url, :ingredients, :allergens,
                    :origin, :manufacturer, :website_url, :recycling_notes, CAST(:extra_json AS jsonb),
                    :sale_type, :package_quantity, :net_weight_g, now()
                )';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($this->productParams($data));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function productParams(array $data): array
    {
        return [
            'gtin' => $data['gtin'],
            'name' => $data['name'],
            'brand' => $data['brand'] !== '' ? $data['brand'] : null,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'image_url' => $data['image_url'] !== '' ? $data['image_url'] : null,
            'ingredients' => $data['ingredients'] !== '' ? $data['ingredients'] : null,
            'allergens' => $data['allergens'] !== '' ? $data['allergens'] : null,
            'origin' => $data['origin'] !== '' ? $data['origin'] : null,
            'manufacturer' => $data['manufacturer'] !== '' ? $data['manufacturer'] : null,
            'website_url' => $data['website_url'] !== '' ? $data['website_url'] : null,
            'recycling_notes' => $data['recycling_notes'] !== '' ? $data['recycling_notes'] : null,
            'extra_json' => $data['extra_json'] !== '' ? $data['extra_json'] : null,
            'sale_type' => $data['sale_type'] === 'unit' ? 'unit' : 'weight',
            'package_quantity' => $data['package_quantity'] !== '' && $data['package_quantity'] !== null
                ? (int) $data['package_quantity']
                : null,
            'net_weight_g' => $data['net_weight_g'] !== '' && $data['net_weight_g'] !== null
                ? $data['net_weight_g']
                : null,
        ];
    }

    public function delete(string $gtin): bool
    {
        $pdo = $this->connection();
        if ($pdo === null) {
            return false;
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM products WHERE gtin = :gtin');
            $stmt->execute(['gtin' => $gtin]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrate(array $row, bool $withNutrition): array
    {
        if (isset($row['extra_json']) && is_string($row['extra_json']) && $row['extra_json'] !== '') {
            $decoded = json_decode($row['extra_json'], true);
            $row['extra'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['extra'] = [];
        }
        $row['nutrition'] = $withNutrition ? $this->findNutrition((string) $row['gtin']) : NutritionFields::empty();
        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function findNutrition(string $gtin): array
    {
        $empty = NutritionFields::empty();
        $pdo = $this->pdo;
        if (!$pdo instanceof PDO) {
            return $empty;
        }

        try {
            $stmt = $pdo->prepare(
                'SELECT serving_size, servings_per_package, serving_grams, serving_unit,
                        energy_kcal, carbohydrates_g, sugars_g, added_sugars_g, protein_g,
                        total_fat_g, saturated_fat_g, trans_fat_g, fiber_g, sodium_mg
                 FROM nutrition_facts
                 WHERE gtin = :gtin
                 LIMIT 1'
            );
            $stmt->execute(['gtin' => $gtin]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                return $empty;
            }
            $out = $empty;
            foreach ($out as $key => $_) {
                if (array_key_exists($key, $row) && $row[$key] !== null) {
                    $out[$key] = (string) $row[$key];
                }
            }
            return $out;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return $empty;
        }
    }

    /**
     * @param array<string, mixed> $nutrition
     */
    private function syncNutrition(PDO $pdo, string $gtin, array $nutrition): void
    {
        if (!NutritionFields::hasValues($nutrition)) {
            $stmt = $pdo->prepare('DELETE FROM nutrition_facts WHERE gtin = :gtin');
            $stmt->execute(['gtin' => $gtin]);
            return;
        }

        $params = ['gtin' => $gtin];
        $params['serving_size'] = trim((string) ($nutrition['serving_size'] ?? ''));
        if ($params['serving_size'] === '') {
            $gramsLabel = trim((string) ($nutrition['serving_grams'] ?? ''));
            $unit = NutritionFields::servingUnit($nutrition);
            $params['serving_size'] = $gramsLabel !== '' ? $gramsLabel . ' ' . $unit : $unit;
        }
        $params['servings_per_package'] = trim((string) ($nutrition['servings_per_package'] ?? ''));
        if ($params['servings_per_package'] === '') {
            $params['servings_per_package'] = null;
        }
        $params['serving_grams'] = trim((string) ($nutrition['serving_grams'] ?? ''));
        $params['serving_grams'] = $params['serving_grams'] === '' ? null : $params['serving_grams'];
        $params['serving_unit'] = NutritionFields::servingUnit($nutrition);
        foreach (array_keys(NutritionFields::NUMBERS) as $key) {
            $value = isset($nutrition[$key]) ? trim((string) $nutrition[$key]) : '';
            $params[$key] = $value === '' ? null : $value;
        }

        $kj = NutritionFields::energyKj($params['energy_kcal'] ?? null);
        $params['energy_kj'] = $kj === null ? null : (string) $kj;

        $sql = 'INSERT INTO nutrition_facts (
                    gtin, serving_size, servings_per_package, serving_grams, serving_unit,
                    energy_kcal, energy_kj, carbohydrates_g, sugars_g, added_sugars_g, protein_g,
                    total_fat_g, saturated_fat_g, trans_fat_g, fiber_g, sodium_mg, updated_at
                ) VALUES (
                    :gtin, :serving_size, :servings_per_package, :serving_grams, :serving_unit,
                    :energy_kcal, :energy_kj, :carbohydrates_g, :sugars_g, :added_sugars_g, :protein_g,
                    :total_fat_g, :saturated_fat_g, :trans_fat_g, :fiber_g, :sodium_mg, now()
                )
                ON CONFLICT (gtin) DO UPDATE SET
                    serving_size = EXCLUDED.serving_size,
                    servings_per_package = EXCLUDED.servings_per_package,
                    serving_grams = EXCLUDED.serving_grams,
                    serving_unit = EXCLUDED.serving_unit,
                    energy_kcal = EXCLUDED.energy_kcal,
                    energy_kj = EXCLUDED.energy_kj,
                    carbohydrates_g = EXCLUDED.carbohydrates_g,
                    sugars_g = EXCLUDED.sugars_g,
                    added_sugars_g = EXCLUDED.added_sugars_g,
                    protein_g = EXCLUDED.protein_g,
                    total_fat_g = EXCLUDED.total_fat_g,
                    saturated_fat_g = EXCLUDED.saturated_fat_g,
                    trans_fat_g = EXCLUDED.trans_fat_g,
                    fiber_g = EXCLUDED.fiber_g,
                    sodium_mg = EXCLUDED.sodium_mg,
                    updated_at = now()';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    private function connection(): ?PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $url = Env::get('DATABASE_URL');
        if ($url === '') {
            $this->lastError = 'DATABASE_URL não configurada.';
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'], $parts['user'], $parts['path'])) {
            $this->lastError = 'DATABASE_URL inválida.';
            return null;
        }

        $db = ltrim((string) $parts['path'], '/');
        $host = $parts['host'];
        $port = (string) ($parts['port'] ?? 5432);
        $user = rawurldecode((string) $parts['user']);
        $pass = rawurldecode((string) $parts['pass'] ?? '');
        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $sslmode = is_string($query['sslmode'] ?? null) ? $query['sslmode'] : 'require';

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', $host, $port, $db, $sslmode);

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return $this->pdo;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }
}
