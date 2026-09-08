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
  <link rel="stylesheet" href="/css/app.css?v=8">
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
    <nav class="foot-links" aria-label="Urano nas redes">
      <a href="https://www.facebook.com/uranobalancas" rel="noopener noreferrer" target="_blank">Facebook</a>
      <a href="https://www.instagram.com/uranobalancas/" rel="noopener noreferrer" target="_blank">Instagram</a>
      <a href="https://www.linkedin.com/company/uranobalancas/" rel="noopener noreferrer" target="_blank">LinkedIn</a>
      <a href="https://twitter.com/uranobalancas" rel="noopener noreferrer" target="_blank">X</a>
      <a href="https://www.youtube.com/user/Uranotecnologia" rel="noopener noreferrer" target="_blank">YouTube</a>
      <a href="https://www.urano.com.br/" rel="noopener noreferrer" target="_blank">urano.com.br</a>
    </nav>
  </footer>
</body>
</html>
