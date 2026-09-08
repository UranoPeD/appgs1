<?php
/** @var string|null $error */
?>
<section class="card">
  <h1>Acesso ao cadastro</h1>
  <p class="muted">Use o token definido em <code>APP_ADMIN_TOKEN</code>.</p>
  <?php if (!empty($error)): ?>
    <p class="badge badge-alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <form method="post" action="/admin/login" class="form">
    <label>
      Token
      <input type="password" name="token" autocomplete="current-password" required>
    </label>
    <button type="submit">Entrar</button>
  </form>
</section>
