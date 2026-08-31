<div class="legal-page" style="max-width: 640px; padding: 48px 24px 72px;">
  <h1 style="font-family: 'Space Grotesk', sans-serif; font-size: 32px; font-weight: 700; color: #022746; margin: 0 0 12px;">Inscription confirmée</h1>
  <?php if (!empty($ok)): ?>
    <p style="font-size: 16px; color: #4A5A6B; line-height: 1.65;">Votre adresse recevra la lettre d’information, en principe chaque semaine. Vous pourrez vous désinscrire en un clic depuis n’importe quel envoi.</p>
  <?php else: ?>
    <p style="font-size: 16px; color: #4A5A6B; line-height: 1.65;">Ce lien de confirmation n’est plus valable. S’il a déjà été utilisé, vous êtes inscrit. Sinon, renvoyez le formulaire du pied de page.</p>
  <?php endif; ?>
  <p style="margin-top: 28px;"><a class="btn-navy" href="<?= e(url('/journal')) ?>">Lire le journal</a></p>
</div>
