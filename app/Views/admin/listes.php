<?php
$trades = $trades ?? [];
$specialties = $specialties ?? [];
?>
<div class="admin-page">
  <h1>Métiers &amp; spécialités</h1>
  <p class="admin-lead">Ces listes alimentent les menus déroulants Métier et Spécialité — création de prestation, vitrine et annuaire. Les spécialités proposées dépendent du métier : genres de textes pour l’écrit, types de prestation pour les autres métiers. Le terme Global reste transversal.</p>

  <?php if (!empty($saved)): ?><div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="flash flash-error"><?= e((string) $error) ?></div><?php endif; ?>

  <div class="admin-tax-grid">
    <?php
    $sections = [
        ['kind' => 'trade', 'title' => 'Métiers', 'help' => 'Correction, illustration, traduction…', 'items' => $trades, 'global' => false],
        ['kind' => 'specialty', 'title' => 'Spécialités', 'help' => 'Genres littéraires et types de prestation selon les métiers, plus le terme Global.', 'items' => $specialties, 'global' => true],
    ];
    foreach ($sections as $section):
    ?>
      <section class="admin-tax-card">
        <h2><?= e($section['title']) ?></h2>
        <p><?= e($section['help']) ?></p>

        <form class="admin-tax-add" method="post" action="<?= e(url('/admin/listes')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="kind" value="<?= e($section['kind']) ?>">
          <input class="input" name="name" required maxlength="120" placeholder="Nouveau terme">
          <?php if ($section['global']): ?>
            <label class="admin-tax-check">
              <input type="checkbox" name="is_global" value="1">
              Terme global
            </label>
          <?php endif; ?>
          <button class="btn-navy" type="submit" name="action" value="create">Ajouter</button>
        </form>

        <div class="admin-tax-list">
          <?php if ($section['items'] === []): ?>
            <p class="admin-tax-empty">Aucun terme pour le moment.</p>
          <?php endif; ?>
          <?php foreach ($section['items'] as $term): ?>
            <form class="admin-tax-row<?= empty($term['enabled']) ? ' is-off' : '' ?>" method="post" action="<?= e(url('/admin/listes')) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $term['id'] ?>">
              <input class="input" name="name" required maxlength="120" value="<?= e((string) $term['name']) ?>">
              <label class="admin-tax-check" title="Visible dans les menus">
                <input type="checkbox" name="enabled" value="1"<?= !empty($term['enabled']) ? ' checked' : '' ?>>
                Visible
              </label>
              <?php if ($section['global']): ?>
                <label class="admin-tax-check" title="Tous types de textes">
                  <input type="checkbox" name="is_global" value="1"<?= !empty($term['is_global']) ? ' checked' : '' ?>>
                  Global
                </label>
              <?php endif; ?>
              <span class="admin-tax-usage"><?= (int) ($term['usage'] ?? 0) > 0 ? (int) $term['usage'] . ' usage' . ((int) $term['usage'] > 1 ? 's' : '') : '' ?></span>
              <div class="admin-tax-actions">
                <button class="admin-ghost" type="submit" name="action" value="up" title="Monter">↑</button>
                <button class="admin-ghost" type="submit" name="action" value="down" title="Descendre">↓</button>
                <button class="btn-navy" type="submit" name="action" value="save">OK</button>
                <button class="admin-ghost" type="submit" name="action" value="delete" title="Supprimer" onclick="return confirm('Supprimer ce terme ?');">✕</button>
              </div>
            </form>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </div>
</div>
