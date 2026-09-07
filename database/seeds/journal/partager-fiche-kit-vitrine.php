<?php

declare(strict_types=1);

return [
    'title' => 'Partagez votre fiche : un kit pour faire connaître vos prestations',
    'slug' => 'partager-fiche-kit-vitrine',
    'category' => 'Plateforme',
    'excerpt' => 'Lien, visuels Instagram, story, LinkedIn, QR code, signature e-mail et badge : tout est prêt dans la vitrine, et les vues se lisent dans l’espace.',
    'image_path' => 'img/journal/partager-fiche-kit-vitrine.jpg',
    'image_alt' => 'Fiche prestataire ouverte à côté d’un téléphone et d’une carte portant un QR code.',
    'published' => true,
    'published_at' => '2026-09-07 10:00:00',
    'body' => <<<'HTML'
<p>Votre vitrine ne travaille que si quelqu’un l’ouvre. Pour ça, encore faut-il que le lien circule : dans une bio Instagram, une story, un post LinkedIn, un pied de mail, un site personnel, une carte de visite. Un onglet <strong>Partage</strong> est désormais disponible dans <a href="/espace/vitrine?onglet=partage">Ma vitrine</a>. Il rassemble les supports à copier ou à télécharger, tous construits autour de la même phrase : « Retrouvez mes prestations sur Acteurs du Livre ».</p>
<p>Ce n’est pas une campagne. C’est une boîte à outils. Vous choisissez ce qui vous sert, vous le placez là où vos clients vous cherchent déjà, et vous voyez l’effet dans <a href="/espace/statistiques">Statistiques</a> : les vues de la fiche et de chaque prestation, sans identifier les visiteurs.</p>

<h2 id="essentiel">L’essentiel</h2>
<ul>
  <li>L’onglet <a href="/espace/vitrine?onglet=partage">Partage</a> est dans la vitrine, réservé aux comptes qui proposent des services.</li>
  <li>Le kit reprend votre nom affiché, votre titre, votre ville et l’adresse publique de la fiche.</li>
  <li>Vous y trouvez le lien, trois visuels (Instagram, story, LinkedIn / Facebook), un QR code, une signature e-mail et un badge pour votre site.</li>
  <li>Les visuels et le QR se téléchargent en PNG ou en SVG.</li>
  <li>Les vues restent dans l’espace : elles mesurent l’ouverture de la fiche, pas « qui » est passé.</li>
</ul>

<h2>À quoi ça sert</h2>
<p>Une fiche précise se trouve dans l’<a href="/prestataires">annuaire</a>. Elle se trouve encore mieux quand un porteur de projet arrive déjà avec votre nom — parce qu’il vous a lue sur Instagram, parce qu’il a scanné une carte au salon, parce que votre signature pointe ici plutôt que vers une page d’accueil générique. Le kit évite de bricoler un visuel à la va-vite ou de recopier une URL trop longue depuis la barre d’adresse.</p>
<p>La phrase unique (« Retrouvez mes prestations sur Acteurs du Livre ») dit clairement ce qu’on trouve en cliquant : des offres, un métier, un contact, pas un réseau social de plus. Elle figure sur les visuels, la signature et le badge. Le lien, lui, est toujours celui de <em>votre</em> fiche.</p>
<p>Partager ne remplace pas une vitrine complète. Titre, métiers, présentation et au moins une <a href="/espace/prestations/creer">prestation</a> publiée restent ce qui convertit une ouverture en message. Le kit amène le visiteur ; la fiche doit ensuite répondre à la question « est-ce la bonne personne ? ».</p>

<h2>Ce que contient le kit</h2>
<h3>Le lien vers la fiche</h3>
<p>Une adresse stable, à coller dans une bio, un message, une newsletter ou un document. Les boutons de diffusion (Facebook, Instagram, LinkedIn, etc.) sont sur la même carte. Instagram ne préremplit pas un post : le lien se copie, vous l’ajoutez dans la légende ou en story.</p>

<h3>Les visuels</h3>
<p>Trois formats, au nom de la plateforme, avec vos initiales, votre nom, votre métier et le QR de la fiche :</p>
<ul>
  <li><strong>Instagram</strong> — carré 1080 × 1080, pour un post.</li>
  <li><strong>Story Instagram</strong> — vertical 1080 × 1920.</li>
  <li><strong>LinkedIn / Facebook</strong> — paysage 1200 × 627, pour un fil ou une image de partage.</li>
</ul>
<p>Téléchargez le PNG pour les réseaux. Le SVG reste utile si vous retravaillez le fichier. Dans tous les cas, ajoutez le lien dans le texte du post : l’image seule ne suffit pas toujours à rendre le QR ou l’URL cliquables.</p>

<h3>Le QR code</h3>
<p>Un scan ouvre la fiche. Il est pensé pour le papier : carte, flyer, signet, affiche de salon, dos de devis. Téléchargez-le à part si vous n’avez besoin que de lui.</p>

<h3>La signature e-mail</h3>
<p>Un bloc HTML à coller dans Gmail, Outlook ou Apple Mail. Il porte votre nom, votre titre, la phrase de la plateforme et le lien. Ce n’est pas une pièce jointe : c’est le pied de chaque message que vous envoyez déjà.</p>

<h3>Le badge pour votre site</h3>
<p>Un bouton à placer en pied de page ou sur une page « Prestations ». Deux formes : le HTML (simple à coller) et le SVG (si vous préférez un fichier image). Les deux renvoient vers votre fiche.</p>

<h2>Comment l’ouvrir</h2>
<p>Connectez-vous, allez dans <a href="/espace/vitrine">Ma vitrine</a>, puis l’onglet <strong>Partage</strong>. Le même kit est aussi annoncé depuis le tableau de bord et depuis <a href="/espace/statistiques">Statistiques</a>. Sur votre fiche publique, un raccourci « Kit de partage » apparaît lorsque c’est vous qui la consultez.</p>
<p>Si l’identité n’est pas encore enregistrée, le kit attend : sans slug, il n’y a pas d’adresse publique. Enregistrez prénom, nom et le reste de l’onglet Identité, puis revenez. Si la fiche n’est pas visible dans l’annuaire (compte incomplet, usage « je propose mes services » inactif, ou facture de commission en retard), les fichiers sont tout de même générés : ils pointeront vers la page dès qu’elle sera en ligne.</p>

<h2>Pourquoi le faire</h2>
<p>Les vues de la vitrine et de chaque prestation se lisent dans l’espace. Vos propres consultations ne sont pas comptées. Personne n’est suivi d’un jour à l’autre. C’est un compteur, pas un outil de ciblage. Il sert à voir si un titre se trouve, si une offre est ouverte, si un partage a un effet — pas à savoir qui a cliqué.</p>
<blockquote>
  <p>Partagez votre profil : vos statistiques de vues sont disponibles dans votre espace.</p>
</blockquote>
<p>Un post sans lien, ou un QR vers une page d’accueil trop large, se perd. Ici, chaque support mène à la même fiche. Si les vues montent sans message, c’est souvent le contenu de la vitrine qu’il faut ajuster — pas le kit.</p>

<h2>Ce que nous n’attendons pas</h2>
<p>Pas d’obligation de publier partout. Pas de classement acheté. Le kit ne change rien au fonctionnement de l’annuaire, aux avis, ni à la commission. Il ne remplace pas non plus un site personnel : le badge le complète. Les visuels portent la marque Acteurs du Livre ; ils ne sont pas des créations à revendre ni à faire passer pour une commande d’illustration.</p>

<h2>Pour commencer</h2>
<p>Ouvrez <a href="/espace/vitrine?onglet=partage">l’onglet Partage</a>, copiez le lien, téléchargez le format qui correspond au canal que vous tenez déjà. Placez la signature dans votre messagerie. Si vous avez un site, ajoutez le badge. Puis regardez <a href="/espace/statistiques">les vues</a> sur quelques jours : c’est le seul indicateur que nous affichons, et il suffit pour savoir si la fiche circule.</p>
HTML,
];
