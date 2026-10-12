<?php

declare(strict_types=1);

return [
    'title' => 'Combien de pages fera ce manuscrit',
    'slug' => 'combien-de-pages-fera-ce-manuscrit',
    'category' => 'Fabrication',
    'excerpt' => 'Signes, format, corps : l’outil estime les pages de texte et l’arrondi au cahier. Le maquettiste confirme sur la police réelle.',
    'image_path' => 'img/journal/maquette-interieure-livre.jpg',
    'image_alt' => 'Gabarit de mise en pages, réglet et cahiers imprimés sur une table lumineuse.',
    'published' => true,
    'published_at' => '2026-10-12 09:30:00',
    'body' => <<<'HTML'
<p>Un manuscrit de <strong>520&nbsp;000 signes</strong> espaces compris, en roman <strong>14&nbsp;×&nbsp;21</strong>, corps 11 sur 13,5, fait environ <strong>257 pages de texte</strong>. Avec 8 pages hors texte, on est à 265. Au cahier de 16, l’offset part plutôt à <strong>272</strong>. C’est le calcul que fait <a href="/outils/pages">l’estimateur de pagination</a>, ouvert dans les <a href="/outils">outils du livre</a>.</p>
<p>Ces chiffres sont un ordre de grandeur, pas un devis et pas une maquette. La chasse réelle dépend de la police. Le <a href="/metiers/maquette">maquettiste</a> cale sur le fichier. L’<a href="/metiers/impression">imprimeur</a> dit ensuite quel multiple de pages il tient.</p>

<h2 id="essentiel">L’essentiel</h2>
<ul>
  <li>L’outil part d’un nombre de signes, de feuillets ou de mots. Rien n’est envoyé, rien n’est enregistré.</li>
  <li>Trois gabarits&nbsp;: roman courant (11&nbsp;/&nbsp;13,5), poche serré (9&nbsp;/&nbsp;11), essai aéré (11&nbsp;/&nbsp;16). Un corps libre reste possible.</li>
  <li>520&nbsp;000 signes en 14&nbsp;×&nbsp;21, roman courant&nbsp;: <strong>2&nbsp;030 signes par page</strong>, 257 pages de texte, 272 au cahier de 16.</li>
  <li>Le même volume en poche serré tombe à 223 pages de texte. En essai aéré, à 344.</li>
  <li>Le hors-texte part à 8 pages (faux-titre, titre, copyright, un blanc). On le met à 0 si l’on ne veut que le manuscrit.</li>
  <li>Le lien ouvre le <a href="/outils/dos">calculateur de dos</a> sur l’arrondi au cahier de 16.</li>
</ul>

<h2 id="outil">Ce que l’outil demande, et ce qu’il rend</h2>
<p>On indique un volume, un format fini et un gabarit. Les formats sont ceux du calculateur de dos&nbsp;: poche 11&nbsp;×&nbsp;18, semi-poche, roman 14&nbsp;×&nbsp;21, A5, 15&nbsp;×&nbsp;21, 15&nbsp;×&nbsp;23, 16&nbsp;×&nbsp;24, A4, ou un format libre. Le résultat affiche les signes par ligne, les lignes par page, les signes par page, les pages de texte, le total avec le hors-texte, puis l’arrondi aux cahiers de 16, de 8 et de 4.</p>
<p>Un schéma montre le bloc de texte dans le format&nbsp;: pour le roman courant, <strong>102&nbsp;×&nbsp;170&nbsp;mm</strong> dans un 140&nbsp;×&nbsp;210. C’est cette surface qui compte, pas la page Word.</p>
<p>Si vous avez le texte et pas encore le nombre, le <a href="/outils/volume">compteur de signes</a> le décompte dans le navigateur. De là, le lien «&nbsp;Estimer les pages&nbsp;» reprend le volume, sans coller le manuscrit dans l’adresse.</p>

<h2 id="chasse">Comment on passe des signes aux pages</h2>
<p>Le feuillet français fait <strong>1&nbsp;500 signes espaces compris</strong>. 520&nbsp;000 signes, c’est un peu moins de 350 feuillets. Ce feuillet n’est pas une page de livre&nbsp;: c’est l’unité des devis de <a href="/metiers/correction">correction</a> et de préparation de copie. La page, elle, dépend du format et du corps.</p>
<p>L’outil suppose un romain de labeur — Garamond, Minion, Caslon — dont la chasse moyenne, espaces compris, vaut <strong>0,45 cadratin</strong>. Signes par ligne&nbsp;= largeur du bloc ÷ chasse. Lignes par page&nbsp;= hauteur du bloc ÷ interligne, vers le bas. Pages de texte&nbsp;= signes du manuscrit ÷ signes par page, arrondi au-dessus.</p>
<p>Les marges du roman courant sont un petit fond de 16&nbsp;mm, un grand fond de 22&nbsp;mm, une tête de 16&nbsp;mm et un pied de 24&nbsp;mm. Le poche est plus juste. L’essai élargit les fonds et ouvre l’interligne. En corps libre, les marges restent celles du roman&nbsp;: on ne change que le corps et l’interligne.</p>
<blockquote>
  <p>«&nbsp;Une page de livre n’est pas une page dactylographiée. Le feuillet dit le volume du texte. Le format dit combien de ce texte tient sur une page.&nbsp;»</p>
</blockquote>

<h2 id="trois-gabarits">Le même manuscrit, trois livres</h2>
<p>Pour 520&nbsp;000 signes et 8 pages hors texte, sans compter les ouvertures de chapitre&nbsp;:</p>
<table>
  <caption>520&nbsp;000 signes — pages estimées selon le gabarit (ordre de grandeur)</caption>
  <thead>
    <tr>
      <th scope="col">Gabarit</th>
      <th scope="col">Signes par page</th>
      <th scope="col">Pages de texte</th>
      <th scope="col">Cahier de 16</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Roman 14&nbsp;×&nbsp;21, 11&nbsp;/&nbsp;13,5</td>
      <td>2&nbsp;030</td>
      <td>257</td>
      <td>272</td>
    </tr>
    <tr>
      <td>Poche 11&nbsp;×&nbsp;18, 9&nbsp;/&nbsp;11</td>
      <td>2&nbsp;340</td>
      <td>223</td>
      <td>240</td>
    </tr>
    <tr>
      <td>Essai 14&nbsp;×&nbsp;21, 11&nbsp;/&nbsp;16</td>
      <td>1&nbsp;512</td>
      <td>344</td>
      <td>352</td>
    </tr>
  </tbody>
</table>
<p>Le poche serré tient <em>plus</em> de signes par page que le roman courant&nbsp;: le même texte y fait moins de pages, pas plus. L’essai aéré, lui, allonge franchement. En 16&nbsp;×&nbsp;24 avec le gabarit roman, on tombe vers <strong>182 pages de texte</strong>, 192 au cahier de 16&nbsp;: le format change le livre autant que le nombre de signes.</p>
<p>Une ligne au-delà de 70 signes fatigue la lecture suivie. En dessous de 45, elle est courte. L’outil le signale. Ce n’est pas une faute&nbsp;: c’est un motif pour élargir les marges, baisser le corps, ou changer de format avant de dessiner la couverture.</p>

<h2 id="hors-texte">Hors-texte et chapitres</h2>
<p>Le manuscrit ne fait pas tout le livre. Faux-titre, titre, copyright, dédicace, parfois une table ou une préface&nbsp;: comptez souvent <strong>8 à 16 pages</strong> en plus. L’outil part à 8. Mettez 0 pour isoler le texte, 16 si les liminaires sont déjà épaisses.</p>
<p>Chaque ouverture de chapitre laisse souvent une demi-page blanche, surtout si le chapitre commence en belle page. Vingt chapitres ajoutent ainsi une dizaine de pages au flux du texte. Ce n’est pas une page pleine à chaque fois&nbsp;: c’est la moyenne entre un chapitre qui tombe en bas de page et un chapitre qui en ouvre une nouvelle.</p>

<h2 id="cahier">Pourquoi 265 pages deviennent 272</h2>
<p>En offset, les pages s’imposent par cahiers de 4, de 8 ou de 16. Un total de 265 pages avec le hors-texte n’est pas imprimable tel quel en cahiers de 16&nbsp;: on monte à 272, soit sept pages blanches. Le cahier de 8 tombe au même 272. Le cahier de 4 s’arrête à 268. Le numérique est plus souple et peut garder 265.</p>
<p>Le poche de l’exemple, à 231 pages avec le hors-texte, demande neuf pages blanches pour atteindre 240. L’outil le dit&nbsp;: un cahier de 8 (232 pages) colle mieux. Mieux vaut le décider avant le BAT de couverture. Le <a href="/journal/impression-pod-numerique-offset">choix entre POD, numérique et offset</a> se joue aussi là.</p>
<p>Le bouton de l’outil ouvre le dos sur cet arrondi de 16, avec le même format. Le dos, lui, dépend encore du papier&nbsp;: un bouffant 80&nbsp;g n’a pas l’épaisseur d’un offset 80&nbsp;g. Voir <a href="/journal/choisir-papier-bouffant-offset-recycle">bouffant, offset, recyclé</a>.</p>

<h2 id="word">Pourquoi Word ne donne pas le même nombre</h2>
<p>Un traitement de texte compte des pages A4, souvent en Times 12, avec ses propres marges. Ce chiffre sert à relire. Il ne dit pas combien de pages fera un roman 14&nbsp;×&nbsp;21 en 11&nbsp;/&nbsp;13,5. La surface n’est pas la même, la chasse non plus.</p>
<p>Un gabarit Word peut produire un intérieur lisible pour un cercle restreint. Dès qu’il y a un imprimeur, un dos et une librairie, les limites apparaissent&nbsp;: césures, veuves, folio, page de titre, fonds de couverture. L’article sur la <a href="/journal/maquette-interieure-livre">maquette intérieure</a> dit ce que l’on achète à ce moment-là&nbsp;: une grille, pas un export.</p>

<h2 id="limites">Ce que l’estimation ne remplace pas</h2>
<p>La police déplace le résultat. Deux romains de labeur ne chassent pas de la même façon, et une titraille très présente non plus. Les notes, les citations en retrait, les vers, les tableaux vident la page&nbsp;: un essai annoté sort du gabarit «&nbsp;essai aéré&nbsp;» dès que l’apparat est lourd. Les images se comptent à part.</p>
<p>L’ordre utile reste le même. On compte les signes, on fait <a href="/journal/preparation-copie-ou-correction">corriger</a>, puis on maquette. Corriger après la mise en pages recale des chapitres entiers. L’estimation sert avant le devis de maquette et avant le brief imprimeur&nbsp;: elle évite d’annoncer «&nbsp;300 pages&nbsp;» quand le format choisi en fera 180, ou 350.</p>
<p>Sur <a href="/">acteursdulivre.fr</a>, l’outil est gratuit, sans compte. La maquette, elle, est un métier&nbsp;: <a href="/besoin/maquette-de-livre">trouver un maquettiste</a>. Aucun livrable de mission n’est généré par une IA. Le détail est dans les <a href="/regles-ia">règles IA</a>.</p>

<h2 id="questions">Questions fréquentes</h2>
<h3>Le manuscrit est-il envoyé quelque part&nbsp;?</h3>
<p>Non. L’estimateur reçoit un nombre. Le compteur de signes, si vous collez un texte, le décompte dans votre navigateur. L’adresse de la page peut contenir ce nombre pour partager le résultat. Elle ne contient pas le texte.</p>
<h3>Faut-il garder les 8 pages hors texte&nbsp;?</h3>
<p>Non. C’est un point de départ&nbsp;: faux-titre, titre, copyright, un blanc. Mettez 0 pour ne voir que le manuscrit, ou 16 si vous avez une table, une préface, des remerciements.</p>
<h3>Un chapitre ajoute-t-il une page entière&nbsp;?</h3>
<p>En moyenne, une demi-page. Le chapitre qui commence en belle page en laisse parfois une blanche&nbsp;; celui qui suit de près n’en laisse presque pas. Vingt chapitres, c’est environ dix pages de plus dans le flux du texte.</p>
<h3>Faut-il toujours un multiple de 16&nbsp;?</h3>
<p>En offset, souvent, selon le cahier que tient l’imprimeur&nbsp;: 4, 8 ou 16. Le numérique peut rester sur le total. Le lien vers le dos prend le cahier de 16, le plus courant pour un roman. Ce n’est pas une obligation universelle.</p>
<h3>L’outil peut-il servir de maquette&nbsp;?</h3>
<p>Non. Il ne choisit pas la police, ne règle pas les césures, ne place pas les folios. Il dit combien de pages prévoir pour que le devis et le dos partent d’un chiffre tenable. La maquette confirme, ou déplace ce chiffre.</p>

<h2 id="aller-plus-loin">Pour aller plus loin</h2>
<p>Outils&nbsp;: <a href="/outils/pages">estimer les pages</a>, <a href="/outils/volume">compter les signes</a>, <a href="/outils/dos">calculer le dos</a>. Métier&nbsp;: <a href="/metiers/maquette">maquette</a>, <a href="/metiers/impression">impression</a>, <a href="/metiers/correction">correction</a>. Journal&nbsp;: <a href="/journal/maquette-interieure-livre">maquette intérieure</a>, <a href="/journal/choisir-papier-bouffant-offset-recycle">choisir son papier</a>, <a href="/journal/impression-pod-numerique-offset">POD, numérique, offset</a>.</p>
HTML,
];
