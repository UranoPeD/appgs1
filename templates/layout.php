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
$gtinDisplay = $gtin !== null ? \App\Gs1\DigitalLinkParser::displayGtin($gtin) : null;
$rows = $link->displayRows();
$expiry = $link->expiryStatus();
$registered = is_array($product);
$title = $registered ? (string) $product['name'] : ($gtinDisplay ? 'GTIN ' . $gtinDisplay : 'Digital Link');
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
  <link rel="stylesheet" href="/css/app.css?v=13">
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
      <a class="foot-icon" href="https://www.facebook.com/uranobalancas" rel="noopener noreferrer" target="_blank" aria-label="Facebook">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.84c0-2.52 1.49-3.91 3.77-3.91 1.09 0 2.24.2 2.24.2v2.48h-1.26c-1.24 0-1.63.78-1.63 1.57v1.89h2.78l-.44 2.91h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94z"/></svg>
      </a>
      <a class="foot-icon" href="https://www.instagram.com/uranobalancas/" rel="noopener noreferrer" target="_blank" aria-label="Instagram">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm10 1.8H7A2.2 2.2 0 0 0 4.8 7v10A2.2 2.2 0 0 0 7 19.2h10a2.2 2.2 0 0 0 2.2-2.2V7A2.2 2.2 0 0 0 17 4.8zM12 8.2A3.8 3.8 0 1 1 8.2 12 3.8 3.8 0 0 1 12 8.2zm0 1.6A2.2 2.2 0 1 0 14.2 12 2.2 2.2 0 0 0 12 9.8zm5.05-3.55a1.05 1.05 0 1 1-1.05 1.05 1.05 1.05 0 0 1 1.05-1.05z"/></svg>
      </a>
      <a class="foot-icon" href="https://www.linkedin.com/company/uranobalancas/" rel="noopener noreferrer" target="_blank" aria-label="LinkedIn">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6.54 21H3.56V8.74h2.98V21zM5.05 7.4A1.73 1.73 0 1 1 5.04 4a1.73 1.73 0 0 1 .01 3.4zM21 21h-2.97v-6.0c0-1.43-.03-3.27-2-3.27-2 0-2.3 1.56-2.3 3.17V21H10.76V8.74h2.85v1.67h.04c.4-.75 1.37-1.54 2.82-1.54 3.01 0 3.57 1.98 3.57 4.56V21z"/></svg>
      </a>
      <a class="foot-icon" href="https://twitter.com/uranobalancas" rel="noopener noreferrer" target="_blank" aria-label="X">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.24 3H21l-6.52 7.45L22 21h-6.17l-4.82-6.3L5.6 21H2.82l6.97-7.97L2 3h6.32l4.35 5.76L18.24 3zm-1.08 16.2h1.7L6.92 4.7H5.1l12.06 14.5z"/></svg>
      </a>
      <a class="foot-icon" href="https://www.youtube.com/user/Uranotecnologia" rel="noopener noreferrer" target="_blank" aria-label="YouTube">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M23.5 7.2a3.02 3.02 0 0 0-2.12-2.14C19.5 4.6 12 4.6 12 4.6s-7.5 0-9.38.46A3.02 3.02 0 0 0 .5 7.2 31.6 31.6 0 0 0 0 12a31.6 31.6 0 0 0 .5 4.8 3.02 3.02 0 0 0 2.12 2.14C4.5 19.4 12 19.4 12 19.4s7.5 0 9.38-.46a3.02 3.02 0 0 0 2.12-2.14A31.6 31.6 0 0 0 24 12a31.6 31.6 0 0 0-.5-4.8zM9.75 15.02V8.98L15.5 12l-5.75 3.02z"/></svg>
      </a>
      <a class="foot-site" href="https://www.urano.com.br/" rel="noopener noreferrer" target="_blank">urano.com.br</a>
    </nav>
  </footer>
</body>
</html>
