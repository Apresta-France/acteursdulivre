<?php
$headerUnreadForum = (int) ($unreadForum ?? 0);
$headerCommunauteOn = !empty($isCommunaute) || !empty($isForum) || !empty($isEspaceForum) || !empty($isMaisons) || !empty($isMaison) || !empty($isSalons) || !empty($isSalon);
$headerForumHref = !empty($logged) && $headerUnreadForum > 0 ? '/espace/forum?onglet=suivis' : '/forum';
?>
<div class="header-drop<?= $headerCommunauteOn ? ' is-current' : '' ?>" data-header-drop>
  <button type="button" class="header-drop-btn" data-header-drop-toggle aria-expanded="false" aria-controls="header-communaute-panel" aria-haspopup="true" aria-label="<?= $headerUnreadForum > 0 ? 'Communauté (' . $headerUnreadForum . ' réponses non lues)' : 'Menu communauté' ?>">
    Communauté<?php if ($headerUnreadForum > 0): ?><span class="badge-orange"><?= $headerUnreadForum > 99 ? '99+' : $headerUnreadForum ?></span><?php endif; ?>
  </button>
  <div class="header-drop-flyout" id="header-communaute-panel">
    <div class="header-drop-panel">
      <a href="<?= e(url($headerForumHref)) ?>"<?= !empty($isForum) || !empty($isEspaceForum) ? ' class="is-active"' : '' ?>>
        <strong>Forum<?php if ($headerUnreadForum > 0): ?><span class="badge-orange"><?= $headerUnreadForum > 99 ? '99+' : $headerUnreadForum ?></span><?php endif; ?></strong>
        <span>Parler métier</span>
      </a>
      <a href="<?= e(url('/maisons-edition')) ?>"<?= !empty($isMaisons) || !empty($isMaison) ? ' class="is-active"' : '' ?>>
        <strong>Annuaire</strong>
        <span>Maisons d’édition</span>
      </a>
      <a href="<?= e(url('/salons')) ?>"<?= !empty($isSalons) || !empty($isSalon) ? ' class="is-active"' : '' ?>>
        <strong>Agenda</strong>
        <span>Salons du livre</span>
      </a>
    </div>
  </div>
</div>
