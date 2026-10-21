<?php

declare(strict_types=1);

return [
    'title' => 'Les souscriptions : un livre déjà en cours, montré ici',
    'slug' => 'souscriptions-livre-deja-en-cours',
    'category' => 'Plateforme',
    'excerpt' => 'La page Souscriptions montre des livres en création, en vente ou sponsorisés, dont la campagne est déjà ouverte ailleurs. On lit la fiche ici ; on soutient ou on achète sur le site qui l’héberge.',
    'image_path' => '',
    'image_alt' => '',
    'published' => true,
    'published_at' => '2026-10-21 09:30:00',
    'body' => <<<'HTML'
<p>La page <a href="/souscriptions"><strong>Souscriptions</strong></a> rassemble des livres dont la campagne est déjà lancée quelque part : une souscription le temps de fabriquer l’ouvrage, une prévente, une vente directe, ou une mise en avant sponsorisée. Acteurs du Livre ne collecte pas l’argent. La fiche décrit le livre, dit qui le porte, et renvoie vers le site qui héberge la campagne — Ulule, la boutique de l’autrice, l’atelier, la maison.</p>
<p>On y accède depuis le menu Communauté, entre le forum, l’annuaire des maisons et l’agenda des salons. Tant qu’une annonce est ouverte, elle apparaît dans la liste. Quand la date est passée, elle reste consultable parmi les annonces terminées, sans bouton vers le paiement.</p>

<h2 id="essentiel">L’essentiel</h2>
<ul>
  <li>Trois annonces : en création, en vente (prévente ou vente directe), sponsorisé.</li>
  <li>La campagne existe déjà ailleurs. On ne lance pas une collecte depuis cette page.</li>
  <li>Le bouton de la fiche ouvre le site hôte. Le paiement ne passe pas par Acteurs du Livre.</li>
  <li>Une annonce sponsorisée le dit sur la fiche. Le lien quitte le site.</li>
  <li>Proposer un livre ne demande pas de compte. L’équipe vérifie avant de publier.</li>
  <li>Un même lien ne peut pas être proposé deux fois.</li>
</ul>

<h2 id="a-quoi-ca-sert">À quoi sert cette section</h2>
<p>Une souscription ou une prévente vit d’habitude sur la page qui encaisse. Elle est visible pour qui la cherche déjà, beaucoup moins pour le reste des métiers du livre. La section sert de vitrine commune : un correcteur, un libraire, un illustrateur, un lecteur de la communauté voit qu’un livre est en train de se faire, qui le porte, et jusqu’à quand.</p>
<p>Pour la personne qui porte le livre, c’est un résumé de la campagne, à côté du <a href="/forum">forum</a>, de l’<a href="/salons">agenda des salons</a> et de l’<a href="/maisons-edition">annuaire des maisons</a>. Ce n’est pas une prestation, ni un appel d’offres. On n’y commande pas une correction. On y montre un ouvrage déjà en route.</p>
<p>Pour qui lit, c’est un moyen de soutenir ou d’acheter sans quitter le contexte du métier. Le chapô dit où en est le livre. La fiche nomme les intervenants lorsqu’ils sont indiqués. Le compte à rebours dit s’il reste du temps.</p>

<h2 id="trois-annonces">Trois façons d’être annoncé</h2>
<p>La liste se filtre. <strong>Ouvertes</strong> montre tout ce qui est encore en cours. Les trois autres filtres séparent le type d’annonce. Les compteurs en haut de page indiquent combien de livres sont en création, en vente, ou sponsorisés.</p>
<p><strong>En création.</strong> Une souscription déjà ouverte ailleurs, le temps de fabriquer le livre. L’argent sert à rendre l’ouvrage possible : impression, illustration, tirage. Le bouton publié dit « Soutenir la souscription ». On choisit ce type tant que la campagne n’est pas encore la vente du livre fini.</p>
<p><strong>En vente.</strong> Une prévente ou une vente directe, chez l’auteur, l’atelier ou la maison. Dans le formulaire, on distingue la prévente, dont le bouton dira « Réserver », de la vente, dont le bouton dira « Voir la vente ». Sur la liste publique, les deux se retrouvent sous le même filtre.</p>
<p><strong>Sponsorisé.</strong> Une mise en avant assumée. La fiche publiée le dit, et la mention « Annonce sponsorisée » accompagne le lien. La date de fin n’est pas obligatoire. Le bouton dit « Voir le livre ». Sans date, la carte indique « En cours » plutôt qu’un compte à rebours.</p>
<p>Le filtre <strong>Terminées</strong> garde les annonces closes. Elles ne proposent plus de quitter le site pour payer. Une mention de clôture remplace le bouton.</p>

<h2 id="lire-une-fiche">Ce qu’on peut faire en lisant</h2>
<p>Aucune inscription n’est demandée pour parcourir <a href="/souscriptions">la liste</a>. Chaque carte indique le type, le temps restant — « Plus que 12 jours », « Plus qu’un jour », « Dernier jour » ou « Clôturée » —, le titre, qui porte le livre, le genre s’il est renseigné, et où la campagne est ouverte.</p>
<p>Une annonce peut être mise en avant en tête de liste. Les autres s’affichent en grille. Quand des livres sont ouverts, jusqu’à trois d’entre eux apparaissent aussi sur la page <a href="/communaute">Communauté</a>, avec le même rappel : la campagne reste ailleurs.</p>
<p>Ouvrir une fiche donne le chapô, le texte de présentation, et, lorsqu’ils ont été ajoutés, les métiers qui font le livre — relieur, illustratrice, imprimeur — ainsi que quelques renseignements pratiques. Le bloc « Ailleurs » nomme le site hôte, la date de clôture, et le bouton qui ouvre la campagne dans un nouvel onglet. Si le lien n’est pas encore renseigné, le bouton reste inactif : « Lien à venir ». En bas de fiche, d’autres annonces encore ouvertes sont proposées.</p>
<p>Soutenir, réserver ou acheter se fait sur le site indiqué. Acteurs du Livre n’émet pas de reçu, ne gère pas les contreparties et ne suit pas l’avancement de la collecte.</p>

<h2 id="proposer">Proposer un livre</h2>
<p>Le bouton <a href="/souscriptions/proposer">Proposer un livre</a> est ouvert à tout le monde. Un compte connecté préremplit le nom et l’e-mail. Ce n’est pas une condition.</p>
<p>Le formulaire demande ce qui est déjà vrai ailleurs :</p>
<ul>
  <li>le titre ;</li>
  <li>ce qui est en cours : création, prévente, vente ou sponsoring ;</li>
  <li>le genre, si vous voulez le préciser (roman, essai, album…) ;</li>
  <li>qui porte le livre — personne, atelier ou maison — et son rôle, s’il aide à comprendre (autrice, relieur, maison) ;</li>
  <li>un chapô de quelques phrases, 40 caractères au minimum : de quoi il s’agit, et où en est le livre ;</li>
  <li>où c’est ouvert, et le lien vers la page de la campagne ;</li>
  <li>la date de clôture, obligatoire sauf pour une annonce sponsorisée sans date de fin ;</li>
  <li>votre nom, votre e-mail, et un message pour l’équipe si vous avez quelque chose à préciser. Ce message n’est pas publié.</li>
</ul>
<p>L’envoi ne publie rien. La confirmation le dit : la proposition est envoyée, le livre n’est pas encore visible, l’équipe écrit après vérification. Elle contrôle que le lien existe, que l’annonce est encore ouverte, et que le sponsoring est assumé lorsqu’il y en a un. Une campagne déjà close est refusée à l’envoi. Un lien déjà dans la liste, ou déjà en cours d’examen, est refusé à l’envoi. Mieux vaut donc regarder la liste avant de remplir le formulaire.</p>
<p>Une fois la fiche ouverte, elle reste visible le temps de la campagne. Le bouton ramène sur le site qui l’héberge. Passée la date, l’annonce quitte les filtres ouverts et rejoint les terminées.</p>

<h2 id="ce-que-ce-n-est-pas">Ce que cette section n’est pas</h2>
<p>Ce n’est pas une cagnotte intégrée au site, ni un moyen de vendre un livre qui n’a pas encore de page ailleurs. Si la campagne n’existe pas encore, il n’y a rien à proposer.</p>
<p>Ce n’est pas non plus la place de marché des métiers. Pour trouver un correcteur, un illustrateur ou un imprimeur, on passe par l’<a href="/prestataires">annuaire</a>, les <a href="/prestations">prestations</a> ou les <a href="/missions">appels d’offres</a>. La fiche d’une souscription peut nommer les personnes qui travaillent sur le livre. Elle ne les met pas en mission, et elle n’ouvre pas de devis.</p>
<p>Une annonce sponsorisée n’est pas présentée comme une souscription. Le type est choisi à l’envoi, vérifié, puis affiché tel quel.</p>

<h2 id="pour-commencer">Pour commencer</h2>
<p>La liste est sur <a href="/souscriptions">Souscriptions</a>. S’il n’y a encore aucune annonce, la page le dit et renvoie vers le formulaire. Vous portez un livre dont la collecte, la prévente ou la vente est déjà ouverte : <a href="/souscriptions/proposer">décrivez-le</a>. Vous voulez seulement voir ce qui se fabrique en ce moment : filtrez, ouvrez une fiche, et suivez le lien si le livre vous concerne.</p>
HTML,
];
