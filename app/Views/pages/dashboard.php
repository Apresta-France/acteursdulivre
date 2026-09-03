<?php
$first = $userFirst ?? 'vous';
$seeks = !empty($seeksServices);
$offers = !empty($offersServices);
$missionCount = (int) ($missionCount ?? 0);
$openMissionCount = (int) ($openMissionCount ?? 0);
$profileCompletion = (int) ($profileCompletion ?? 0);
$unreadMessages = (int) ($unreadMessages ?? 0);
$unreadAlerts = (int) ($unreadAlerts ?? 0);
$availabilityBusy = ($availabilityStatus ?? 'available') === 'busy';
$availabilityNote = trim((string) ($availabilityNote ?? ''));

if ($seeks && $offers) {
    $subtitle = 'Vous pouvez chercher des prestataires et proposer vos services.';
} elseif ($seeks) {
    $subtitle = 'Vous cherchez des prestataires pour vos projets.';
} elseif ($offers) {
    $subtitle = 'Vous proposez vos services aux porteurs de projet.';
} else {
    $subtitle = 'Activez un usage dans vos paramètres pour afficher vos actions.';
}

$todos = [];
foreach ($jalonTodos ?? [] as $jalon) {
    $todos[] = [
        'icon' => (string) ($jalon['icon'] ?? 'clipboard'),
        'title' => (string) ($jalon['title'] ?? 'Jalon en cours'),
        'body' => (string) ($jalon['body'] ?? ''),
        'href' => (string) ($jalon['href'] ?? '/espace/suivi'),
        'cta' => (string) ($jalon['cta'] ?? 'Ouvrir'),
    ];
}
if ($seeks && $missionCount === 0) {
    $todos[] = [
        'icon' => 'file-plus',
        'title' => 'Publier votre première recherche',
        'body' => 'Décrivez le besoin et le budget : les prestataires du métier choisi pourront y répondre.',
        'href' => '/espace/publier',
        'cta' => 'Rédiger l\'annonce',
    ];
}
if ($offers && $profileCompletion < 80) {
    $todos[] = [
        'icon' => 'id',
        'title' => 'Compléter votre vitrine',
        'body' => 'Profil complété à ' . $profileCompletion . ' %. Les vitrines précises reçoivent nettement plus de demandes.',
        'href' => '/espace/vitrine',
        'cta' => 'Continuer',
    ];
}
?>
<div class="espace-page dash-page">
  <div class="espace-page-head">
    <div>
      <h1>Bonjour <?= e($first) ?></h1>
      <p><?= e($subtitle) ?><?php if (!empty($isPlatformCofounder)): ?> <span class="profile-badge profile-badge-cofounder">Co-fondateur de la plateforme</span><?php endif; ?><?php if (!empty($isFounder)): ?> <span class="profile-badge profile-badge-founder">Membre fondateur</span><?php endif; ?></p>
    </div>
    <div class="dash-hero-actions">
      <?php if ($seeks): ?>
        <a class="btn-orange" href="<?= e(url('/espace/publier')) ?>"><?= icon('file-plus', 16) ?> Publier une recherche</a>
      <?php endif; ?>
      <?php if ($offers): ?>
        <a class="btn-navy" href="<?= e(url('/espace/prestations/creer')) ?>"><?= icon('plus-box', 16) ?> Proposer une prestation</a>
      <?php endif; ?>
      <a class="btn-ghost" href="<?= e(url('/espace/parametres')) ?>"><?= icon('sliders', 16) ?> Mes usages</a>
    </div>
  </div>

  <?php if (!empty($onboardingPending)): ?>
    <div class="dash-onboard">
      <div>
        <strong>Votre compte n’est pas encore installé</strong>
        <em><?php
          $top = $onboardingPriorities[0] ?? null;
          echo e($top
            ? (string) $top['title'] . ' — ' . (string) $top['body']
            : 'Quelques minutes suffisent pour que votre fiche existe vraiment.');
        ?></em>
      </div>
      <a class="btn-orange" href="<?= e(url('/espace/bienvenue')) ?>">Reprendre l’accueil</a>
    </div>
  <?php endif; ?>

  <?php if (!empty($saved)): ?>
    <div class="flash flash-ok"><?= e(is_string($saved) ? $saved : 'Enregistré.') ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="flash flash-error"><?= e((string) $error) ?></div>
  <?php endif; ?>
  <?php require ADL_ROOT . '/app/Views/partials/billing-banner.php'; ?>

  <?php if ($offers): ?>
    <section class="avail-banner<?= $availabilityBusy ? ' is-busy' : ' is-available' ?>">
      <span class="dash-ico<?= $availabilityBusy ? '' : ' dash-ico-accent' ?>"><?= icon('clock', 18) ?></span>
      <div>
        <strong>Vous êtes <?= $availabilityBusy ? 'Occupé' : 'Disponible' ?></strong>
        <em><?php if ($availabilityBusy): ?>
          Votre vitrine indique que vous n'acceptez pas de nouveaux appels d'offres<?= $availabilityNote !== '' ? ' · ' . e($availabilityNote) : '' ?>. Les porteurs de projet peuvent toujours vous écrire.
        <?php else: ?>
          Les porteurs de projet voient que vous acceptez de nouveaux appels d'offres<?= $availabilityNote !== '' ? ' · ' . e($availabilityNote) : '' ?>. Passez en Occupé dès que votre planning est saturé.
        <?php endif; ?></em>
      </div>
      <form method="post" action="<?= e(url('/espace/disponibilite')) ?>" class="mode-switch">
        <?= csrf_field() ?>
        <button type="submit" name="availability_status" value="available" class="mode-option<?= !$availabilityBusy ? ' is-on is-available' : '' ?>">Disponible</button>
        <button type="submit" name="availability_status" value="busy" class="mode-option<?= $availabilityBusy ? ' is-on is-busy' : '' ?>">Occupé</button>
      </form>
    </section>
  <?php endif; ?>

  <div class="dash-stats">
    <a class="dash-stat" href="<?= e(url('/espace/messages')) ?>">
      <span class="dash-ico"><?= icon('mail', 18) ?></span>
      <span>
        <strong>Messages</strong>
        <em><?= $unreadMessages ?> non lu<?= $unreadMessages > 1 ? 's' : '' ?></em>
      </span>
    </a>
    <a class="dash-stat" href="<?= e(url('/espace/notifications')) ?>">
      <span class="dash-ico"><?= icon('bell', 18) ?></span>
      <span>
        <strong>Alertes</strong>
        <em><?= $unreadAlerts ?> nouvelle<?= $unreadAlerts > 1 ? 's' : '' ?></em>
      </span>
    </a>
    <?php if ($seeks): ?>
      <a class="dash-stat" href="<?= e(url('/espace/missions')) ?>">
        <span class="dash-ico"><?= icon('clipboard', 18) ?></span>
        <span>
          <strong>Recherches</strong>
          <em><?= $openMissionCount ?> ouverte<?= $openMissionCount > 1 ? 's' : '' ?> · <?= $missionCount ?> au total</em>
        </span>
      </a>
    <?php endif; ?>
    <?php if ($offers): ?>
      <a class="dash-stat" href="<?= e(url('/espace/vitrine')) ?>">
        <span class="dash-ico"><?= icon('id', 18) ?></span>
        <span>
          <strong>Vitrine</strong>
          <em>Complétée à <?= $profileCompletion ?> %</em>
        </span>
      </a>
      <?php if (!empty($commissionRate)): ?>
        <a class="dash-stat" href="<?= e(url('/espace/facturation')) ?>">
          <span class="dash-ico dash-ico-accent"><?= icon('invoice', 18) ?></span>
          <span>
            <strong>Commission <?= e((string) ($commissionRate['label'] ?? '')) ?></strong>
            <em><?= e((string) (($commissionRate['progress'] ?? '') !== '' ? $commissionRate['progress'] : ($commissionRate['first_free'] ? 'Première mission offerte' : 'sur le hors-taxe'))) ?></em>
          </span>
        </a>
      <?php endif; ?>
      <?php
        $views = $viewSummary ?? null;
        $viewTotal = is_array($views) ? (int) ($views['total'] ?? 0) : 0;
        $viewDays = is_array($views) ? (int) ($views['days'] ?? 7) : 7;
        $viewProfile = is_array($views) ? (int) ($views['profile_views'] ?? 0) : 0;
        $viewServices = is_array($views) ? (int) ($views['service_views'] ?? 0) : 0;
      ?>
      <a class="dash-stat" href="<?= e(url('/espace/statistiques')) ?>">
        <span class="dash-ico"><?= icon('chart', 18) ?></span>
        <span>
          <strong>Vues · <?= $viewDays ?> j.</strong>
          <em><?php
            if ($viewTotal === 0) {
                echo 'Aucune vue pour l’instant';
            } else {
                echo $viewProfile . ' vitrine · ' . $viewServices . ' prestation' . ($viewServices > 1 ? 's' : '');
            }
          ?></em>
        </span>
      </a>
    <?php endif; ?>
  </div>

  <?php
    $platformNews = $platformNews ?? [];
    if ($platformNews !== []):
  ?>
    <section class="dash-section dash-news" id="actualite">
      <div class="dash-section-head">
        <h2>Actualité de la plateforme</h2>
        <a href="<?= e(url('/journal')) ?>">Le journal →</a>
      </div>
      <div class="dash-news-list">
        <?php foreach ($platformNews as $news): ?>
          <?php
            $publishedTs = strtotime((string) ($news['published_at'] ?? ''));
            $isNew = $publishedTs !== false && $publishedTs >= strtotime('-3 days');
          ?>
          <a class="dash-news-item" href="<?= e(url((string) ($news['href'] ?? '/journal'))) ?>">
            <?php if (!empty($news['when'])): ?>
              <span class="dash-news-meta"><?= e((string) $news['when']) ?></span>
            <?php endif; ?>
            <strong><?= e((string) ($news['title'] ?? '')) ?></strong><?php if ($isNew): ?> <span class="dash-news-new">Nouveau</span><?php endif; ?>
            <?php if (!empty($news['chapo'])): ?>
              <em><?= e((string) $news['chapo']) ?></em>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($todos !== []): ?>
    <section class="dash-section">
      <h2>À faire en priorité</h2>
      <div class="dash-todos">
        <?php foreach ($todos as $todo): ?>
          <a class="dash-todo" href="<?= e(url($todo['href'])) ?>">
            <span class="dash-ico dash-ico-accent"><?= icon($todo['icon'], 18) ?></span>
            <span>
              <strong><?= e($todo['title']) ?></strong>
              <em><?= e($todo['body']) ?></em>
            </span>
            <span class="dash-card-cta"><?= e($todo['cta']) ?> <?= icon('arrow', 14) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($seeks): ?>
    <section class="dash-section">
      <h2>Chercher des prestataires</h2>
      <div class="dash-cards">
        <a class="dash-card" href="<?= e(url('/recherche')) ?>">
          <span class="dash-ico"><?= icon('search', 20) ?></span>
          <strong>Parcourir l'annuaire</strong>
          <span>Filtrez par métier, spécialité, ville et tarif, puis engagez la discussion.</span>
          <span class="dash-card-cta">Ouvrir l'annuaire <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card dash-card-accent" href="<?= e(url('/espace/publier')) ?>">
          <span class="dash-ico"><?= icon('file-plus', 20) ?></span>
          <strong>Publier une recherche</strong>
          <span>Décrivez le besoin et le budget : les prestataires qualifiés vous envoient leur devis.</span>
          <span class="dash-card-cta">Rédiger l'annonce <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card" href="<?= e(url('/espace/missions')) ?>">
          <span class="dash-ico"><?= icon('clipboard', 20) ?></span>
          <strong>Mes recherches</strong>
          <span>Suivez les appels d'offres que vous avez publiés et les devis reçus.</span>
          <span class="dash-card-cta">Voir le suivi <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card" href="<?= e(url('/espace/commandes')) ?>">
          <span class="dash-ico"><?= icon('bag', 20) ?></span>
          <strong>Mes commandes</strong>
          <span>Suivi à jalons : devis, règlements hors plateforme, livraison, validation.</span>
          <span class="dash-card-cta">Ouvrir les commandes <?= icon('arrow', 14) ?></span>
        </a>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($offers): ?>
    <section class="dash-section">
      <h2>Proposer mes services</h2>
      <div class="dash-cards">
        <a class="dash-card" href="<?= e(url('/espace/vitrine')) ?>">
          <span class="dash-ico"><?= icon('id', 20) ?></span>
          <strong>Compléter ma vitrine</strong>
          <span>Métiers, spécialités, présentation : c'est ce que les porteurs de projet voient en premier.</span>
          <span class="dash-card-cta">Éditer la vitrine <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card dash-card-accent" href="<?= e(url('/espace/prestations/creer')) ?>">
          <span class="dash-ico"><?= icon('plus-box', 20) ?></span>
          <strong>Proposer une prestation</strong>
          <span>Une offre packagée à prix, délai et périmètre affichés.</span>
          <span class="dash-card-cta">Composer l'offre <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card" href="<?= e(url('/missions')) ?>">
          <span class="dash-ico"><?= icon('megaphone', 20) ?></span>
          <strong>Voir les appels d'offres</strong>
          <span>Candidatez gratuitement aux recherches publiées par les porteurs de projet.</span>
          <span class="dash-card-cta">Parcourir les appels d'offres <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card" href="<?= e(url('/espace/candidatures')) ?>">
          <span class="dash-ico"><?= icon('send', 20) ?></span>
          <strong>Mes candidatures</strong>
          <span>Le suivi des devis que vous avez envoyés.</span>
          <span class="dash-card-cta">Voir les devis <?= icon('arrow', 14) ?></span>
        </a>
        <a class="dash-card" href="<?= e(url('/espace/statistiques')) ?>">
          <span class="dash-ico"><?= icon('chart', 20) ?></span>
          <strong>Statistiques de vues</strong>
          <span>Combien de fois votre vitrine et chaque prestation ont été ouvertes, pour ajuster le contenu.</span>
          <span class="dash-card-cta">Voir les vues <?= icon('arrow', 14) ?></span>
        </a>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!$seeks && !$offers): ?>
    <div class="search-empty">
      <strong>Aucun usage n'est encore activé.</strong>
      <span>Choisissez si vous cherchez des prestataires, si vous proposez vos services, ou les deux.</span>
      <a class="btn-orange" href="<?= e(url('/espace/parametres')) ?>"><?= icon('sliders', 16) ?> Modifier mes usages</a>
    </div>
  <?php endif; ?>

  <section class="dash-section dash-forum" id="forum">
    <div class="dash-section-head">
      <h2>Forum</h2>
      <a href="<?= e(url('/espace/forum')) ?>">Gérer mon forum →</a>
    </div>
    <?php
      $forumStats = $forumStats ?? ['mine' => 0, 'followed' => 0, 'posts' => 0];
      $forumFollowed = $forumFollowed ?? [];
      $forumMine = $forumMine ?? [];
    ?>
    <div class="dash-forum-grid">
      <div class="dash-forum-main">
        <div class="dash-forum-stats">
          <a class="dash-stat" href="<?= e(url('/espace/forum?onglet=suivis')) ?>">
            <span class="dash-ico"><?= icon('bell', 18) ?></span>
            <span>
              <strong>Discussions suivies</strong>
              <em><?= (int) ($forumStats['followed'] ?? 0) ?></em>
            </span>
          </a>
          <a class="dash-stat" href="<?= e(url('/espace/forum?onglet=mine')) ?>">
            <span class="dash-ico"><?= icon('chat', 18) ?></span>
            <span>
              <strong>Mes discussions</strong>
              <em><?= (int) ($forumStats['mine'] ?? 0) ?></em>
            </span>
          </a>
          <div class="dash-stat is-static">
            <span class="dash-ico"><?= icon('megaphone', 18) ?></span>
            <span>
              <strong>Mes messages</strong>
              <em><?= (int) ($forumStats['posts'] ?? 0) ?></em>
            </span>
          </div>
        </div>

        <form method="post" action="<?= e(url('/espace/forum')) ?>" class="dash-forum-prefs">
          <?= csrf_field() ?>
          <input type="hidden" name="back" value="/espace#forum">
          <h3 class="espace-group-title">Notifications du forum</h3>
          <p class="espace-section-lead">Ces e-mails concernent uniquement le forum. Les messages privés restent dans les réglages généraux.</p>
          <label class="check-row">
            <input type="checkbox" name="notify_forum_followed" value="1"<?= !empty($notifyForumFollowed) ? ' checked' : '' ?>>
            M’écrire quand une discussion que je suis reçoit une nouvelle réponse
          </label>
          <label class="check-row">
            <input type="checkbox" name="notify_forum_mine" value="1"<?= !empty($notifyForumMine) ? ' checked' : '' ?>>
            M’écrire quand quelqu’un répond à une discussion que j’ai ouverte
          </label>
          <div class="dash-forum-actions">
            <button type="submit" class="btn-navy">Enregistrer</button>
            <a class="btn-orange" href="<?= e(url('/forum/nouveau')) ?>"><?= icon('plus-box', 16) ?> Ouvrir une discussion</a>
            <a class="btn-ghost" href="<?= e(url('/forum')) ?>">Voir le forum</a>
          </div>
        </form>
      </div>

      <aside class="dash-forum-side">
        <div class="dash-forum-panel">
          <div class="dash-forum-panel-title">Discussions suivies</div>
          <?php if ($forumFollowed === []): ?>
            <p class="forum-muted">Aucune discussion suivie pour l’instant. Suivez un fil pour être prévenu des réponses.</p>
          <?php else: ?>
            <div class="dash-forum-list">
              <?php foreach ($forumFollowed as $t): ?>
                <div class="dash-forum-item">
                  <a href="<?= e(url((string) ($t['href'] ?? '/forum'))) ?>">
                    <strong><?= e((string) ($t['title'] ?? '')) ?></strong>
                    <em><?= e((string) (($t['category_short'] ?? '') . ' · ' . ($t['last_when'] ?? ''))) ?></em>
                  </a>
                  <form method="post" action="<?= e(url('/espace/forum/unfollow/' . (int) ($t['id'] ?? 0))) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="back" value="/espace#forum">
                    <button type="submit" class="forum-text-btn" title="Ne plus suivre">Retirer</button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="dash-forum-panel">
          <div class="dash-forum-panel-title">Mes discussions</div>
          <?php if ($forumMine === []): ?>
            <p class="forum-muted">Vous n’avez pas encore ouvert de discussion.</p>
          <?php else: ?>
            <div class="dash-forum-list">
              <?php foreach ($forumMine as $t): ?>
                <a class="dash-forum-item is-link" href="<?= e(url((string) ($t['href'] ?? '/forum'))) ?>">
                  <strong><?= e((string) ($t['title'] ?? '')) ?></strong>
                  <em><?= e(format_int((int) ($t['reply_count'] ?? 0)) . ' rép. · ' . (string) ($t['last_when'] ?? '')) ?></em>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </section>

  <section class="dash-section">
    <h2>Raccourcis</h2>
    <div class="dash-quick">
      <a class="dash-chip" href="<?= e(url('/espace/messages')) ?>"><?= icon('mail', 16) ?> Messages</a>
      <a class="dash-chip" href="<?= e(url('/espace/notifications')) ?>"><?= icon('bell', 16) ?> Alertes</a>
      <?php if ($seeks): ?>
        <a class="dash-chip" href="<?= e(url('/espace/favoris')) ?>"><?= icon('heart', 16) ?> Favoris</a>
        <a class="dash-chip" href="<?= e(url('/recherche')) ?>"><?= icon('search', 16) ?> Annuaire</a>
      <?php endif; ?>
      <?php if ($offers): ?>
        <a class="dash-chip" href="<?= e(url('/espace/prestations')) ?>"><?= icon('grid', 16) ?> Mes prestations</a>
        <a class="dash-chip" href="<?= e(url('/espace/statistiques')) ?>"><?= icon('chart', 16) ?> Statistiques</a>
        <a class="dash-chip" href="<?= e(url('/espace/facturation')) ?>"><?= icon('invoice', 16) ?> Facturation</a>
        <?php if (($profileHref ?? '') !== '' && $profileHref !== '/recherche'): ?>
          <a class="dash-chip" href="<?= e(url($profileHref)) ?>"><?= icon('store', 16) ?> Voir en public</a>
        <?php endif; ?>
      <?php endif; ?>
      <a class="dash-chip" href="<?= e(url('/espace/auteur')) ?>"><?= icon('book', 16) ?> Fiche auteur</a>
      <a class="dash-chip" href="<?= e(url('/espace/forum')) ?>"><?= icon('chat', 16) ?> Forum</a>
      <a class="dash-chip" href="<?= e(url('/espace/parametres')) ?>"><?= icon('gear', 16) ?> Paramètres</a>
      <a class="dash-chip" href="<?= e(url('/aide')) ?>"><?= icon('book', 16) ?> Centre d'aide</a>
    </div>
  </section>
</div>
