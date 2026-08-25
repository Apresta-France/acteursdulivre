<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title><?= e($subject ?? $appName ?? 'Acteurs du Livre') ?></title>
</head>
<body style="margin:0;background:#E8E3DA;font-family:Helvetica,Arial,sans-serif;color:#14202C;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#E8E3DA;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:14px;overflow:hidden;">
          <tr>
            <td style="background:#022746;padding:22px 28px;color:#E4EDF5;font-family:'Space Grotesk',Helvetica,sans-serif;font-size:18px;">
              <?= e($appName ?? 'Acteurs du Livre') ?>
            </td>
          </tr>
          <tr>
            <td style="padding:28px;font-size:15px;line-height:1.65;">
              <?= $content ?? '' ?>
            </td>
          </tr>
          <tr>
            <td style="padding:18px 28px 28px;font-size:12px;color:#66768A;">
              <a href="<?= e($appUrl ?? 'https://acteursdulivre.fr') ?>" style="color:#D85D3F;">acteursdulivre.fr</a>
              — 12 rue du Calvaire, Nantes
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
