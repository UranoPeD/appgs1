<?php
/** @var array<string, mixed> $nutrition */

use App\NutritionFields;

$grams = NutritionFields::servingGrams($nutrition);
$unit = NutritionFields::servingUnit($nutrition);
$portionLabel = $grams > 0 ? NutritionFields::formatNumber($grams, $grams == (int) $grams ? 0 : 1) . ' ' . $unit : '';
$qrWeight = isset($link) ? $link->netWeightGrams() : null;
$qrVolume = isset($link) ? $link->netVolumeMl() : null;
$servings = isset($product) && is_array($product)
    ? NutritionFields::servingsPerPackage($product, $nutrition, $qrWeight, $qrVolume)
    : null;
$fopCode = NutritionFields::fopCode($nutrition);
$fopLabels = NutritionFields::fopLabels($fopCode);
$col100 = '100 ' . $unit;
$colPortion = $portionLabel !== '' ? $portionLabel : 'Porção';
?>
<section class="card nutrition-card">
  <?php if ($fopLabels !== []): ?>
    <div class="fop" aria-label="Alertas na frente da embalagem">
      <?php foreach ($fopLabels as $label): ?>
        <div class="fop-seal">
          <svg class="fop-icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2.2"/>
            <path d="M15.4 15.4 L21 21" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
          </svg>
          <span>
            <small>Alto em</small>
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <p class="rdc-title">INFORMAÇÃO NUTRICIONAL</p>
  <p class="rdc-meta">
    <?php if ($servings !== null): ?>
      Porções por embalagem: <?= (int) $servings ?> porções<br>
    <?php endif; ?>
    <?php if ($portionLabel !== ''): ?>
      Porção: <?= htmlspecialchars($portionLabel, ENT_QUOTES, 'UTF-8') ?>
    <?php endif; ?>
  </p>
  <table class="rdc-table">
    <thead>
      <tr>
        <th></th>
        <th><?= htmlspecialchars($col100, ENT_QUOTES, 'UTF-8') ?></th>
        <th><?= htmlspecialchars($colPortion, ENT_QUOTES, 'UTF-8') ?></th>
        <th>%VD*</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (NutritionFields::NUMBERS as $key => $label): ?>
        <?php
        $portion = $nutrition[$key] ?? '';
        if ($portion === null || $portion === '') {
            $per100 = null;
            $portionDisplay = '–';
        } else {
            $per100 = $grams > 0 ? NutritionFields::per100g($portion, $grams) : null;
            $portionDisplay = NutritionFields::formatNumber($portion, 1);
        }
        $vd = NutritionFields::vdPercent($portion === '' ? null : $portion, NutritionFields::VDR[$key]);
        $per100Display = $per100 === null ? '–' : NutritionFields::formatNumber($per100, 1);
        $vdDisplay = NutritionFields::VDR[$key] === null
            ? ''
            : ($vd === null ? '–' : $vd . '%');
        ?>
        <tr>
          <td><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($per100Display, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($portionDisplay, ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($vdDisplay, ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p class="rdc-foot">*Percentual de valores diários fornecidos pela porção.</p>
</section>
