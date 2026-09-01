<div class="legal-page" style="max-width: 640px; padding: 48px 24px 72px;">
  <h1 style="font-family: 'Space Grotesk', sans-serif; font-size: 32px; font-weight: 700; color: #022746; margin: 0 0 12px;">Désinscription</h1>
  <?php if (!empty($ok)): ?>
    <p style="font-size: 16px; color: #4A5A6B; line-height: 1.65;">Vous ne recevrez plus la lettre d’information. Vous pourrez vous réinscrire plus tard depuis le pied de page.</p>
  <?php else: ?>
    <p style="font-size: 16px; color: #4A5A6B; line-height: 1.65;">Ce lien de désinscription n’est pas reconnu. Écrivez-nous à <a href="mailto:guillaume@editions-tesseract.fr">guillaume@editions-tesseract.fr</a> si le problème continue.</p>
  <?php endif; ?>
  <p style="margin-top: 28px;"><a class="btn-navy" href="<?= e(url('/')) ?>">Retour à l’accueil</a></p>
</div>
