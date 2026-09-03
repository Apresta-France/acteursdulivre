<?php

declare(strict_types=1);

return [
    'title' => 'La section auteur : une fiche publique pour vos livres',
    'slug' => 'section-auteur-fiche-publique',
    'category' => 'Plateforme',
    'excerpt' => 'Bibliographie, biographie, presse et actualités : une page dédiée, distincte de la vitrine prestataire.',
    'image_path' => null,
    'image_alt' => 'Rayonnage de livres et fiche auteur ouverte sur un écran.',
    'published' => true,
    'body' => <<<'HTML'
<p>Vous pouvez désormais créer une <strong>fiche auteur</strong> sur Acteurs du Livre. Elle présente qui vous êtes comme auteur·ice — nom de plume, genres, biographie, œuvres, presse, liens — et s’affiche dans un <a href="/auteurs">annuaire des auteurs</a>, à part de la vitrine prestataire.</p>
<p>La fiche se gère dans l’espace, à <a href="/espace/auteur">Ma fiche auteur</a>. Elle reste en brouillon tant que vous ne la publiez pas.</p>

<h2 id="essentiel">L’essentiel</h2>
<ul>
  <li>Fiche auteur distincte de la vitrine « je propose mes services ».</li>
  <li>Biographie courte et longue, genres, nom de plume, disponibilités (salons, écoles, presse…).</li>
  <li>Catalogue d’œuvres : titre, ISBN, résumé, formats, liens d’achat, images de couverture.</li>
  <li>Presse, prix, événements, liens (site, Babelio, éditeur, réseaux…).</li>
  <li>Publication volontaire : brouillon privé, puis mise en ligne sur <code>/auteurs/votre-slug</code>.</li>
</ul>

<h2>À quoi ça sert</h2>
<p>Beaucoup de membres sont à la fois porteurs de projet et auteurs — parfois aussi prestataires. La vitrine parle de tarifs et de métiers. La fiche auteur parle de livres : une page à partager avec un libraire, un journaliste, un lecteur, ou à laisser indexer par les moteurs. Elle ne remplace pas un site personnel ; elle donne une adresse stable sur la plateforme, avec bibliographie et actualités.</p>
<p>L’annuaire <a href="/auteurs">/auteurs</a> liste les fiches publiées. Chaque fiche publique a sa propre URL. Vous pouvez la prévisualiser avant publication.</p>

<h2>Comment ça fonctionne</h2>
<p>Dans l’espace, ouvrez <strong>Ma fiche auteur</strong>. Complétez l’identité (nom de plume optionnel, accroche, genres), la biographie, puis les blocs presse, liens, distinctions et événements. L’onglet <strong>Mes œuvres</strong> permet d’ajouter chaque titre : type (roman, essai, jeunesse…), rôle, éditeur, année, ISBN, résumé, extrait, formats, prix, liens d’achat, images. Un indicateur de complétion guide ce qui manque encore.</p>
<ul>
  <li>Enregistrez autant que besoin : le brouillon n’est pas visible publiquement.</li>
  <li>Quand la fiche est prête, cliquez sur <strong>Publier ma fiche</strong>. Vous pouvez la repasser en brouillon à tout moment.</li>
  <li>Le slug d’URL suit en général le nom affiché ; il se fige dès la première publication utile.</li>
</ul>
<p>Vous n’avez pas besoin d’être « prestataire » pour avoir une fiche auteur : tout compte peut en créer une. Si vous avez déjà une vitrine, les deux coexistent — l’une pour vendre un savoir-faire, l’autre pour présenter une œuvre.</p>

<h2>Ce que nous attendons</h2>
<p>Des informations exactes, des liens qui fonctionnent, des couvertures dont vous avez le droit d’usage. Pas de catalogue fantôme ni de bio générée pour remplir. La fiche sert les lecteurs et les professionnels qui cherchent un auteur, pas un remplissage SEO. Les règles de la plateforme (dont l’absence d’IA générative sur les livrables) s’appliquent aussi ici pour les contenus que vous publiez.</p>
<blockquote>
  <p>La fiche auteur n’est pas un appel d’offres. Pour chercher un correcteur ou une couverture, utilisez l’<a href="/recherche">annuaire</a> ou <a href="/espace/publier">publiez une recherche</a>.</p>
</blockquote>

<h2>Pour commencer</h2>
<p>Rendez-vous dans <a href="/espace/auteur">l’espace auteur</a>, ajoutez au moins une œuvre et une bio courte, puis publiez. Parcourez ensuite l’<a href="/auteurs">annuaire</a> pour voir comment les fiches apparaissent. Le <a href="/journal">journal</a> et le <a href="/forum">forum</a> restent disponibles pour le reste de la conversation métier.</p>
HTML,
];