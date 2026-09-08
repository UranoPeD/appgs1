<?php

declare(strict_types=1);

/**
 * @var string $appName
 * @var string $logoUrl
 * @var \App\Gs1\DigitalLinkParser $link
 * @var array|null $product
 * @var string|null $dbError
 * @var string $template
 */

$gtin = $link->gtin();
$rows = $link->displayRows();
$expiry = $link->expiryStatus();
$registered = is_array($product);
$title = $registered ? (string) $product['name'] : ($gtin ? 'GTIN ' . $gtin : 'Digital Link');
$inner = __DIR__ . '/' . $template . '.php';
if (!is_file($inner)) {
    $inner = __DIR__ . '/home.php';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title . ' · ' . $appName, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/css/app.css?v=7">
</head>
<body>
  <header class="top">
    <?php if ($logoUrl !== ''): ?>
      <img class="logo" src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="">
    <?php endif; ?>
    <p class="brand"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></p>
  </header>
  <main class="page">
    <?php require $inner; ?>
  </main>
  <footer class="foot">
    <p><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> · GS1 Digital Link</p>
  </footer>
</body>
</html>
