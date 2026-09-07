<?php
$rows = $landings ?? [];
$hubUrl = (string) ($hubUrl ?? '');
$clients = array_values(array_filter($rows, static fn (array $r): bool => ($r['audience'] ?? '') !== 'prestataire'));
$offerers = array_values(array_filter($rows, static fn (array $r): bool => ($r['audience'] ?? '') === 'prestataire'));
$groups = [
    ['title' => 'Porteurs de projet', 'rows' => $clients],
    ['title' => 'Prestataires', 'rows' => $offerers],
];
?>
<div class="admin-page landings-admin">
  <div class="admin-page-head">
    <div>
      <h1>Landings pub</h1>
      <p class="admin-lead">Une URL par besoin, à coller dans Google Ads, Meta ou LinkedIn. Copiez le lien nu ou le lien avec UTM. Les visites et inscriptions des 7 derniers jours viennent des statistiques internes.</p>
    </div>
    <?php if ($hubUrl !== ''): ?>
      <a class="admin-ghost" href="<?= e($hubUrl) ?>" target="_blank" rel="noopener">Sommaire public</a>
    <?php endif; ?>
  </div>

  <form class="admin-card landing-utm" data-landing-utm>
    <h2>Paramètres UTM</h2>
    <p class="admin-muted">Ils s’ajoutent aux boutons « URL campagne ». Laissez la campagne vide pour utiliser le slug de la page.</p>
    <div class="landing-utm-grid">
      <div>
        <label class="field" for="utm_source">utm_source</label>
        <input class="input" id="utm_source" name="utm_source" value="google" autocomplete="off" spellcheck="false">
      </div>
      <div>
        <label class="field" for="utm_medium">utm_medium</label>
        <input class="input" id="utm_medium" name="utm_medium" value="cpc" autocomplete="off" spellcheck="false">
      </div>
      <div>
        <label class="field" for="utm_campaign">utm_campaign</label>
        <input class="input" id="utm_campaign" name="utm_campaign" value="" placeholder="slug de la page" autocomplete="off" spellcheck="false">
      </div>
      <div>
        <label class="field" for="utm_content">utm_content</label>
        <input class="input" id="utm_content" name="utm_content" value="" placeholder="optionnel" autocomplete="off" spellcheck="false">
      </div>
    </div>
  </form>

  <?php foreach ($groups as $group): ?>
    <?php if ($group['rows'] === []) continue; ?>
    <h2 class="admin-h2"><?= e((string) $group['title']) ?></h2>
    <div class="r-scroll">
      <table class="table">
        <thead>
          <tr>
            <th>Besoin</th>
            <th>URL</th>
            <th>7 j.</th>
            <th>Inscr.</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($group['rows'] as $row): ?>
            <tr>
              <td>
                <a href="<?= e((string) $row['url']) ?>" target="_blank" rel="noopener"><?= e((string) $row['need']) ?></a>
                <div class="admin-muted"><?= e((string) $row['kicker']) ?><?= ($row['trade'] ?? '') !== '' ? ' · ' . e((string) $row['trade']) : '' ?></div>
              </td>
              <td>
                <code class="landing-path"><?= e((string) $row['path']) ?></code>
              </td>
              <td><?= format_int((int) $row['views7']) ?></td>
              <td><?= format_int((int) $row['signups7']) ?></td>
              <td class="admin-actions">
                <button type="button" class="admin-ghost" data-copy="<?= e((string) $row['url']) ?>">Copier l’URL</button>
                <button
                  type="button"
                  class="admin-ghost"
                  data-copy-landing
                  data-url="<?= e((string) $row['url']) ?>"
                  data-campaign="<?= e((string) $row['campaign']) ?>"
                >URL campagne</button>
                <details class="landing-ads">
                  <summary>Textes d’annonces</summary>
                  <div class="landing-ads-body">
                    <p class="admin-muted"><?= e((string) $row['h1']) ?></p>
                    <?php if (!empty($row['ad_headlines'])): ?>
                      <p><strong>Titres</strong> (30 car.)</p>
                      <ul>
                        <?php foreach ($row['ad_headlines'] as $h): ?>
                          <li>
                            <button type="button" class="admin-ghost" data-copy="<?= e((string) $h) ?>"><?= e((string) $h) ?></button>
                            <span class="admin-muted"><?= mb_strlen((string) $h) ?></span>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                    <?php if (!empty($row['ad_descriptions'])): ?>
                      <p><strong>Descriptions</strong> (90 car.)</p>
                      <ul>
                        <?php foreach ($row['ad_descriptions'] as $d): ?>
                          <li>
                            <button type="button" class="admin-ghost" data-copy="<?= e((string) $d) ?>"><?= e((string) $d) ?></button>
                            <span class="admin-muted"><?= mb_strlen((string) $d) ?></span>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                    <?php if (!empty($row['ad_keywords'])): ?>
                      <p><strong>Mots-clés</strong></p>
                      <p>
                        <button type="button" class="admin-ghost" data-copy="<?= e(implode("\n", $row['ad_keywords'])) ?>">Copier la liste</button>
                      </p>
                      <p class="admin-muted"><?= e(implode(' · ', $row['ad_keywords'])) ?></p>
                    <?php endif; ?>
                  </div>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
</div>
