<h1>Administration</h1>
<p style="color:#66768A;">Paramétrez la plateforme, le SMTP et les modèles d'e-mails.</p>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:24px;">
  <div style="background:#fff;border-radius:14px;padding:22px;">
    <div style="font-family:'Space Grotesk',sans-serif;color:#022746;margin-bottom:12px;">Utilisateurs</div>
    <div style="font-size:32px;font-family:'Space Grotesk',sans-serif;color:#022746;"><?= count($users ?? []) ?></div>
  </div>
  <div style="background:#fff;border-radius:14px;padding:22px;">
    <div style="font-family:'Space Grotesk',sans-serif;color:#022746;margin-bottom:12px;">Modèles d'e-mails</div>
    <div style="font-size:32px;font-family:'Space Grotesk',sans-serif;color:#022746;"><?= count($templates ?? []) ?></div>
  </div>
</div>
<table class="table" style="margin-top:28px;">
  <thead><tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Statut</th></tr></thead>
  <tbody>
    <?php foreach ($users ?? [] as $u): ?>
      <tr>
        <td><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= e($u['role']) ?></td>
        <td><?= e($u['status']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
