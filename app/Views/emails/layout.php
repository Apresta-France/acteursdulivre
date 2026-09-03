<?php
$appName = (string) ($appName ?? 'Acteurs du Livre');
$appUrl = (string) ($appUrl ?? url('/'));
$homeUrl = $appUrl !== '' ? rtrim($appUrl, '/') : url('/');
$helpUrl = url('/aide');
$privacyUrl = url('/confidentialite');
$contactUrl = url('/contact');
$isNewsletter = (($kind ?? '') === 'newsletter') || !empty($unsubscribeUrl);
$preheader = trim((string) ($preheader ?? $subject ?? ''));
$link = 'color:#6B7280;text-decoration:underline;';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($subject ?? $appName) ?></title>
  <!--[if !mso]><!-->
  <style>
    a { color: #022746; }
  </style>
  <!--<![endif]-->
</head>
<body style="margin:0;padding:0;background:#F4F4F5;font-family:Helvetica,Arial,sans-serif;color:#1F2933;">
  <?php if ($preheader !== ''): ?>
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;"><?= e($preheader) ?>&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>
  <?php endif; ?>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#F4F4F5;">
    <tr>
      <td style="padding:32px 16px 40px;" align="center">
        <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="width:100%;max-width:560px;background:#ffffff;">
          <tr>
            <td style="padding:28px 32px 0;font-family:Helvetica,Arial,sans-serif;font-size:15px;font-weight:600;color:#022746;">
              <a href="<?= e($homeUrl) ?>" style="color:#022746;text-decoration:none;"><?= e($appName) ?></a>
            </td>
          </tr>
          <tr>
            <td style="padding:24px 32px 8px;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:1.6;color:#1F2933;">
              <?= $content ?? '' ?>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 32px 32px;font-family:Helvetica,Arial,sans-serif;font-size:12px;line-height:1.55;color:#6B7280;">
              <p style="margin:0 0 14px;padding-top:20px;border-top:1px solid #E8E8EA;">
                <?php if ($isNewsletter): ?>
                  Vous recevez cet e-mail parce que vous êtes inscrit·e à la lettre d’Acteurs du Livre.
                <?php else: ?>
                  Ceci est un e-mail de service envoyé par Acteurs du Livre.
                <?php endif; ?>
              </p>
              <p style="margin:0 0 16px;">
                <a href="<?= e($helpUrl) ?>" style="<?= $link ?>">Aide</a>
                &nbsp;·&nbsp;
                <a href="<?= e($privacyUrl) ?>" style="<?= $link ?>">Confidentialité</a>
                &nbsp;·&nbsp;
                <a href="<?= e($contactUrl) ?>" style="<?= $link ?>">Contact</a>
                <?php if (!empty($unsubscribeUrl)): ?>
                  &nbsp;·&nbsp;
                  <a href="<?= e((string) $unsubscribeUrl) ?>" style="<?= $link ?>">Se désinscrire</a>
                <?php endif; ?>
              </p>
              <p style="margin:0;">
                EDITIONS TESSERACT<br>
                486 rue Sadi Carnot<br>
                59184 Sainghin-en-Weppes<br>
                France
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
