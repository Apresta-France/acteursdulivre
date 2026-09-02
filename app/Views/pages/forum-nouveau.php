<?php
$categories = $forumCategories ?? [];
$preselect = (string) ($forumCategorySlug ?? '');
$old = is_array($old ?? null) ? $old : [];
$oldCat = (int) ($old['category_id'] ?? 0);
if ($oldCat <= 0 && $preselect !== '') {
    foreach ($categories as $c) {
        if (($c['slug'] ?? '') === $preselect) {
            $oldCat = (int) ($c['id'] ?? 0);
            break;
        }
    }
}
?>
<div class="forum-page">
  <section class="forum-hero forum-hero-compact">
    <div class="forum-crumb">
      <a href="<?= e(url('/forum')) ?>">Forum</a>
      <span>/</span>
      <span>Nouvelle discussion</span>
    </div>
    <h1>Ouvrir une discussion</h1>
    <p class="forum-lead">Posez une question concrète : chiffres, clauses, délais, cas réels. Les réponses vagues n'aident personne.</p>
  </section>

  <div class="forum-body">
    <form class="forum-compose forum-compose-new" method="post" action="<?= e(url('/forum/nouveau')) ?>" data-forum-compose>
      <?= csrf_field() ?>
      <div class="forum-compose-head">
        <div class="forum-compose-who">
          <div class="forum-post-name">Votre discussion</div>
          <div class="forum-aside-meta">Visible par toute la communauté · sans IA générative</div>
        </div>
        <span class="forum-pin">Sans IA</span>
      </div>
      <div class="forum-compose-body">
        <label class="forum-field">
          <span>Rubrique</span>
          <select name="category_id" required>
            <option value="">Choisir une rubrique…</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int) ($c['id'] ?? 0) ?>"<?= $oldCat === (int) ($c['id'] ?? 0) ? ' selected' : '' ?>><?= e((string) ($c['name'] ?? '')) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="forum-field">
          <span>Titre</span>
          <input type="text" name="title" required minlength="8" maxlength="180" value="<?= e((string) ($old['title'] ?? '')) ?>" placeholder="Ex. Quel tarif pour une correction de 90 000 signes en 2026 ?">
        </label>

        <label class="forum-field">
          <span>Message</span>
          <textarea name="body" rows="10" minlength="80" required placeholder="Expliquez le contexte, ce que vous avez déjà tenté, et la question précise."><?= e((string) ($old['body'] ?? '')) ?></textarea>
        </label>

        <label class="forum-field">
          <span>Étiquettes <em>(optionnel, séparées par des virgules)</em></span>
          <input type="text" name="tags" value="<?= e((string) ($old['tags'] ?? '')) ?>" placeholder="correction, devis, roman">
        </label>

        <label class="forum-engage">
          <input type="checkbox" name="no_ai" value="1" required>
          <span>Je confirme que ce message est de ma main et qu'aucune IA générative n'a été utilisée pour le produire.</span>
        </label>

        <div class="forum-compose-actions">
          <button type="submit" class="btn-orange">Publier la discussion</button>
          <a class="btn-ghost" href="<?= e(url('/forum')) ?>">Annuler</a>
          <span class="forum-muted" data-draft-count>Minimum 80 caractères</span>
        </div>
      </div>
    </form>
  </div>
</div>
