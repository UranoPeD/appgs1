<?php

declare(strict_types=1);

/**
 * @var string $appName
 * @var string $logoUrl
 * @var string $title
 * @var string $csrf
 * @var string $inner
 */

if (!isset($title) || !is_string($title)) {
    $title = 'Admin';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title . ' · ' . $appName, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/css/app.css?v=2">
</head>
<body>
  <header class="top">
    <?php if ($logoUrl !== ''): ?>
      <img class="logo" src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="">
    <?php endif; ?>
    <p class="brand"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></p>
    <nav class="nav">
      <a href="/">Site</a>
      <a href="/admin">Cadastro</a>
    </nav>
  </header>
  <main class="page page-wide">
    <?php require $inner; ?>
  </main>
</body>
</html>
