<?php
/** @var list<array<string, mixed>> $products */
/** @var string|null $dbError */
/** @var string|null $notice */
/** @var string $csrf */
?>
<section class="card">
  <div class="row-between">
    <div>
      <h1>Produtos</h1>
      <p class="muted">Fichas associadas ao GTIN do Digital Link.</p>
    </div>
    <a class="btn" href="/admin/new">Novo produto</a>
  </div>
  <?php if (!empty($notice)): ?>
    <p class="badge badge-ok"><?= htmlspecialchars((string) $notice, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($dbError)): ?>
    <p class="badge badge-alert">Banco indisponível. Confira <code>DATABASE_URL</code> e o schema no Supabase.</p>
  <?php endif; ?>
</section>

<section class="card">
  <?php if ($products === []): ?>
    <p>Nenhum produto cadastrado ainda.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>GTIN</th>
            <th>Nome</th>
            <th>Marca</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $item): ?>
            <tr>
              <td class="mono"><?= htmlspecialchars((string) $item['gtin'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) ($item['brand'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="actions">
                <a href="/admin/edit/<?= htmlspecialchars((string) $item['gtin'], ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                <form method="post" action="/admin/delete" onsubmit="return confirm('Excluir este produto?');">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="gtin" value="<?= htmlspecialchars((string) $item['gtin'], ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" class="link-btn">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  <form method="post" action="/admin/logout" class="logout">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="link-btn">Sair</button>
  </form>
</section>
