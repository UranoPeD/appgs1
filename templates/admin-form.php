<?php
/** @var array<string, mixed> $product */
/** @var string|null $error */
/** @var bool $isNew */
/** @var string $csrf */

$val = static function (string $key) use ($product): string {
    if ($key === 'extra_json' && isset($product['extra']) && is_array($product['extra']) && $product['extra'] !== []) {
        $json = json_encode($product['extra'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return is_string($json) ? $json : '';
    }
    if (!isset($product[$key]) || $product[$key] === null) {
        return '';
    }
    return (string) $product[$key];
};
?>
<section class="card">
  <h1><?= $isNew ? 'Novo produto' : 'Editar produto' ?></h1>
  <?php if (!empty($error)): ?>
    <p class="badge badge-alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <form method="post" action="/admin/save" class="form">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="is_new" value="<?= $isNew ? '1' : '0' ?>">
    <label>
      GTIN
      <input name="gtin" value="<?= htmlspecialchars($val('gtin'), ENT_QUOTES, 'UTF-8') ?>" <?= $isNew ? '' : 'readonly' ?> required inputmode="numeric">
    </label>
    <label>
      Nome
      <input name="name" value="<?= htmlspecialchars($val('name'), ENT_QUOTES, 'UTF-8') ?>" required>
    </label>
    <label>
      Marca
      <input name="brand" value="<?= htmlspecialchars($val('brand'), ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <label>
      Tipo de venda
      <select name="sale_type">
        <option value="weight" <?= $val('sale_type') === 'unit' ? '' : 'selected' ?>>Pesável (peso)</option>
        <option value="unit" <?= $val('sale_type') === 'unit' ? 'selected' : '' ?>>Unidade / quantidade</option>
      </select>
    </label>
    <label>
      Quantidade na embalagem
      <input name="package_quantity" value="<?= htmlspecialchars($val('package_quantity'), ENT_QUOTES, 'UTF-8') ?>" inputmode="numeric" placeholder="Só para venda por unidade">
    </label>
    <label>
      Peso de referência (g)
      <input name="net_weight_g" value="<?= htmlspecialchars($val('net_weight_g'), ENT_QUOTES, 'UTF-8') ?>" inputmode="decimal" placeholder="Usado no pesável se o QR não tiver o peso">
    </label>
    <label>
      Descrição
      <textarea name="description" rows="4"><?= htmlspecialchars($val('description'), ENT_QUOTES, 'UTF-8') ?></textarea>
    </label>
    <label>
      URL da imagem
      <input name="image_url" value="<?= htmlspecialchars($val('image_url'), ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <label>
      Ingredientes
      <textarea name="ingredients" rows="4"><?= htmlspecialchars($val('ingredients'), ENT_QUOTES, 'UTF-8') ?></textarea>
    </label>
    <label>
      Alérgenos
      <textarea name="allergens" rows="2"><?= htmlspecialchars($val('allergens'), ENT_QUOTES, 'UTF-8') ?></textarea>
    </label>
    <label>
      Origem
      <input name="origin" value="<?= htmlspecialchars($val('origin'), ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <label>
      Fabricante
      <input name="manufacturer" value="<?= htmlspecialchars($val('manufacturer'), ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <label>
      Site do fabricante
      <input name="website_url" value="<?= htmlspecialchars($val('website_url'), ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <label>
      Descarte / reciclagem
      <textarea name="recycling_notes" rows="2"><?= htmlspecialchars($val('recycling_notes'), ENT_QUOTES, 'UTF-8') ?></textarea>
    </label>
    <label>
      Extra (JSON)
      <textarea name="extra_json" rows="5" class="mono"><?= htmlspecialchars($val('extra_json'), ENT_QUOTES, 'UTF-8') ?></textarea>
    </label>

    <?php
    $nutrition = isset($product['nutrition']) && is_array($product['nutrition']) ? $product['nutrition'] : [];
    $nval = static function (string $key) use ($nutrition): string {
        if (!isset($nutrition[$key]) || $nutrition[$key] === null || $nutrition[$key] === '') {
            return '';
        }
        if ($key === 'serving_size' || $key === 'servings_per_package' || $key === 'serving_unit') {
            return (string) $nutrition[$key];
        }
        $formatted = \App\NutritionFields::formatNumber($nutrition[$key]);
        return $formatted !== '' ? $formatted : (string) $nutrition[$key];
    };
    ?>
    <h2>Tabela nutricional RDC 429 (opcional)</h2>
    <p class="muted">Informe a porção em g ou ml e os valores <strong>por porção</strong>. A coluna 100 g/ml é referência da RDC 429. Porções por embalagem: no pesável = peso do item ÷ porção (arredondado); na unidade = quantidade da embalagem.</p>
    <div class="form-grid">
      <label>
        Porção (quantidade)
        <input name="nutrition[serving_grams]" value="<?= htmlspecialchars($nval('serving_grams'), ENT_QUOTES, 'UTF-8') ?>" inputmode="decimal" placeholder="Ex.: 40">
      </label>
      <label>
        Unidade
        <select name="nutrition[serving_unit]">
          <option value="g" <?= $nval('serving_unit') === 'ml' ? '' : 'selected' ?>>g (sólido)</option>
          <option value="ml" <?= $nval('serving_unit') === 'ml' ? 'selected' : '' ?>>ml (líquido)</option>
        </select>
      </label>
    </div>
    <p class="muted">Valores por porção declarada</p>
    <div class="form-grid">
      <?php foreach (\App\NutritionFields::NUMBERS as $key => $label): ?>
        <label>
          <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
          <input name="nutrition[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($nval($key), ENT_QUOTES, 'UTF-8') ?>" inputmode="decimal">
        </label>
      <?php endforeach; ?>
    </div>

    <div class="row-between">
      <button type="submit">Salvar</button>
      <a href="/admin">Cancelar</a>
    </div>
  </form>
</section>
