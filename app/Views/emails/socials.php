<?php
/** Trois boutons de suivi, sur une seule rangée (tableau : les clients mail ignorent souvent flex). */
$names = [
    'facebook' => 'Facebook',
    'instagram' => 'Instagram',
    'linkedin' => 'LinkedIn',
];
?>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 0 16px;">
  <tr>
    <?php foreach (\Adl\Data\Socials::profiles() as $s): ?>
      <?php
        $id = (string) ($s['id'] ?? '');
        $name = $names[$id] ?? (string) ($s['short'] ?? '');
      ?>
      <td style="padding:0 8px 0 0;vertical-align:middle;">
        <a href="<?= e((string) ($s['href'] ?? '#')) ?>"
           title="<?= e((string) ($s['label'] ?? $name)) ?>"
           style="display:inline-block;border:1px solid #D1D5DB;border-radius:8px;text-decoration:none;padding:7px 7px;"><?= \Adl\Data\Socials::iconLabel($id, $name, false, 12) ?></a>
      </td>
    <?php endforeach; ?>
  </tr>
</table>
