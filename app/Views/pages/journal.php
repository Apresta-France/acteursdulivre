<?php
$articles = $articles ?? [];
$hero = $articles[0] ?? null;
$rest = $hero ? array_slice($articles, 1) : [];
?>
<div class="journal-page">
  <h1>Le journal</h1>
  <p class="journal-lead">Prix, méthodes, contrats, retours d'expérience : ce qu'on apprend en regardant les métiers du livre travailler.</p>

  <?php if ($articles === []): ?>
    <div class="search-empty">
      <strong>Aucun article publié pour le moment.</strong>
      <span>Les textes du journal apparaîtront ici dès leur mise en ligne.</span>
    </div>
  <?php else: ?>
    <?php if ($hero): ?>
      <a class="journal-hero" href="<?= e(url((string) $hero['href'])) ?>">
        <div class="journal-hero-media" style="background-image:url('<?= e((string) $hero['img']) ?>')"></div>
        <div class="journal-hero-body">
          <div class="journal-kicker"><?= e((string) $hero['cat']) ?> · <?= e((string) $hero['read']) ?> de lecture</div>
          <h2><?= e((string) $hero['title']) ?></h2>
          <p><?= e((string) $hero['chapo']) ?></p>
        </div>
      </a>
    <?php endif; ?>

    <?php if ($rest !== []): ?>
      <div class="journal-grid">
        <?php foreach ($rest as $a): ?>
          <a class="journal-card" href="<?= e(url((string) $a['href'])) ?>">
            <div class="journal-card-media" style="background-image:url('<?= e((string) $a['img']) ?>')"></div>
            <div class="journal-kicker"><?= e((string) $a['cat']) ?> · <?= e((string) $a['read']) ?></div>
            <strong><?= e((string) $a['title']) ?></strong>
            <span><?= e((string) $a['chapo']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
