<?php
/** @var \App\Gs1\DigitalLinkParser $link */
/** @var array|null $product */
/** @var string|null $dbError */
/** @var string $appName */
/** @var string|null $gtinDisplay */
/** @var array $rows */
/** @var string|null $expiry */
/** @var bool $registered */

$highlightAis = ['10' => true, '21' => true, '17' => true, '15' => true, '11' => true];
$highlight = [];
foreach ($rows as $row) {
    $type = (string) ($row['type'] ?? '');
    $isMeasure = strpos($type, 'decimal:') === 0 || strpos($type, 'amount') === 0;
    if (isset($highlightAis[$row['ai']]) || $isMeasure) {
        $highlight[] = $row;
    }
}

$hasFicha = $registered && (
    !empty($product['description'])
    || !empty($product['ingredients'])
    || !empty($product['allergens'])
    || !empty($product['origin'])
    || !empty($product['manufacturer'])
    || !empty($product['website_url'])
    || !empty($product['recycling_notes'])
    || (!empty($product['extra']) && is_array($product['extra']))
);
?>
<?php if ($expiry === 'expired'): ?>
  <p class="badge badge-alert badge-expiry">Validade vencida</p>
<?php elseif ($expiry === 'valid'): ?>
  <p class="badge badge-ok badge-expiry">Validade vigente</p>
<?php endif; ?>

<?php if (!$registered): ?>
  <p class="badge badge-warn">Produto não cadastrado</p>
<?php endif; ?>

<section class="hero card">
  <?php if ($registered && !empty($product['image_url'])): ?>
    <img class="photo" src="<?= htmlspecialchars((string) $product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="">
  <?php endif; ?>
  <div>
    <p class="kicker"><?= htmlspecialchars((string) ($registered && !empty($product['brand']) ? $product['brand'] : $appName), ENT_QUOTES, 'UTF-8') ?></p>
    <h1><?= htmlspecialchars($registered ? (string) $product['name'] : 'Item identificado pelo QR Code', ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if (!empty($gtinDisplay)): ?>
      <p class="gtin">GTIN <?= htmlspecialchars($gtinDisplay, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>
</section>

<?php if ($highlight !== []): ?>
<section class="card">
  <h2>Este item</h2>
  <p class="muted">Lote, série e datas lidos no QR Code.</p>
  <dl class="facts">
    <?php foreach ($highlight as $row): ?>
      <div>
        <dt><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') ?></dt>
        <dd><?= htmlspecialchars($row['display'], ENT_QUOTES, 'UTF-8') ?></dd>
      </div>
    <?php endforeach; ?>
  </dl>
</section>
<?php endif; ?>

<section class="card">
  <h2>Informações do produto</h2>
  <?php if ($registered && $hasFicha): ?>
    <?php if (!empty($product['description'])): ?>
      <p><?= nl2br(htmlspecialchars((string) $product['description'], ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>

    <?php if (!empty($product['ingredients'])): ?>
      <h3>Ingredientes</h3>
      <p><?= nl2br(htmlspecialchars((string) $product['ingredients'], ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>

    <?php if (!empty($product['allergens'])): ?>
      <h3>Alérgenos</h3>
      <p><?= nl2br(htmlspecialchars((string) $product['allergens'], ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>

    <dl class="facts">
      <?php if (!empty($product['origin'])): ?>
        <div>
          <dt>Origem</dt>
          <dd><?= htmlspecialchars((string) $product['origin'], ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
      <?php endif; ?>
      <?php if (!empty($product['manufacturer'])): ?>
        <div>
          <dt>Fabricante</dt>
          <dd><?= htmlspecialchars((string) $product['manufacturer'], ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
      <?php endif; ?>
    </dl>

    <?php if (!empty($product['website_url'])): ?>
      <p><a href="<?= htmlspecialchars((string) $product['website_url'], ENT_QUOTES, 'UTF-8') ?>" rel="noopener noreferrer">Site do fabricante</a></p>
    <?php endif; ?>

    <?php if (!empty($product['recycling_notes'])): ?>
      <h3>Descarte</h3>
      <p><?= nl2br(htmlspecialchars((string) $product['recycling_notes'], ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>

    <?php if (!empty($product['extra']) && is_array($product['extra'])): ?>
      <dl class="facts">
        <?php foreach ($product['extra'] as $key => $value): ?>
          <?php if (!is_scalar($value)) { continue; } ?>
          <div>
            <dt><?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?></dt>
            <dd><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>
    <?php endif; ?>
  <?php elseif ($registered): ?>
    <p>Produto encontrado no cadastro, ainda sem ficha complementar.</p>
  <?php else: ?>
    <p>O GTIN não foi encontrado no cadastro deste site. Os dados do QR Code acima continuam visíveis.</p>
    <?php if ($dbError): ?>
      <p class="muted">Não foi possível consultar o banco agora.</p>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php
$nutrition = ($registered && isset($product['nutrition']) && is_array($product['nutrition'])) ? $product['nutrition'] : [];
if ($registered && \App\NutritionFields::hasValues($nutrition)) {
    require __DIR__ . '/nutrition.php';
}
?>
