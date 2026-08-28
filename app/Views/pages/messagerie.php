<?php
$threads = $threads ?? [];
$thread = $thread ?? null;
$messages = $messages ?? [];
?>
<div class="espace-page">
  <div class="espace-page-head">
    <div>
      <h1>Messagerie</h1>
      <p>Les échanges autour des recherches, des devis et des commandes.</p>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>
  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Envoyé.') ?></div>
  <?php endif; ?>

  <?php if ($threads === [] && !$thread): ?>
    <div class="search-empty">
      <strong>Aucun message pour le moment.</strong>
      <span>Demandez un devis depuis une vitrine, ou écrivez au porteur d'une recherche.</span>
    </div>
  <?php else: ?>
    <div class="inbox">
      <aside class="inbox-list">
        <?php foreach ($threads as $item): ?>
          <a class="inbox-item<?= !empty($thread) && (int) $item['id'] === (int) $thread['id'] ? ' is-on' : '' ?><?= !empty($item['unread']) ? ' is-unread' : '' ?>" href="<?= e(url((string) $item['href'])) ?>">
            <?= avatar_html($item['other'] ?? [], 36) ?>
            <span>
              <strong><?= e((string) ($item['other']['name'] ?? $item['subject'])) ?></strong>
              <em><?= e(mb_strimwidth((string) ($item['preview'] ?? ''), 0, 70, '…')) ?></em>
            </span>
            <small><?= e((string) ($item['when'] ?? '')) ?></small>
          </a>
        <?php endforeach; ?>
      </aside>
      <div class="inbox-thread">
        <?php if (!$thread): ?>
          <div class="search-empty">
            <strong>Choisissez une conversation.</strong>
          </div>
        <?php else: ?>
          <div class="inbox-thread-head">
            <?= avatar_html($thread['other'] ?? [], 40) ?>
            <div>
              <strong><?= e((string) ($thread['other']['name'] ?? 'Conversation')) ?></strong>
              <em><?= e((string) ($thread['subject'] ?? '')) ?></em>
            </div>
          </div>
          <div class="inbox-messages">
            <?php foreach ($messages as $msg): ?>
              <article class="msg<?= (int) ($msg['user_id'] ?? 0) === (int) (\Adl\Core\Auth::id() ?? 0) ? ' is-mine' : '' ?>">
                <div class="msg-meta"><?= e((string) $msg['who']) ?> · <?= e((string) $msg['when']) ?></div>
                <p><?= nl2br(e((string) $msg['body'])) ?></p>
              </article>
            <?php endforeach; ?>
          </div>
          <form class="inbox-compose" method="post" action="<?= e(url('/espace/messages/' . (int) $thread['id'])) ?>">
            <?= csrf_field() ?>
            <textarea class="textarea" name="body" rows="3" required placeholder="Votre message…"></textarea>
            <button class="btn-orange" type="submit">Envoyer</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
