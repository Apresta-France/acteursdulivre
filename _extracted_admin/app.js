
const NAVY = "#022746", ORANGE = "#D85D3F";

const NAV = [
  ["dash", "Tableau de bord", "", "Pilotage"],
  ["verif", "Vérifications", "24", ""],
  ["moderation", "Modération", "18", ""],
  ["litiges", "Litiges", "4", ""],
  ["avis", "Avis signalés", "5", ""],
  ["users", "Utilisateurs", "", "Données"],
  ["catalogue", "Prestations", "", ""],
  ["missions", "Appels d'offres", "12", ""],
  ["finances", "Commandes & finances", "", ""],
  ["preouverture", "Pré-ouverture", "", "Plateforme"],
  ["cms", "Journal & pages", "", ""],
  ["reglages", "Réglages", "", ""]
];

const DOSSIERS = [
  { name: "Atelier Virgule", initials: "AV", role: "Relecture sur épreuves · Paris", when: "il y a 3 h", pieces: "4 pièces", metier: "Correction", tag: "Priorité" },
  { name: "Imprimerie Feuillage", initials: "IF", role: "Imprimeur offset · Angers", when: "il y a 6 h", pieces: "6 pièces", metier: "Impression", tag: "" },
  { name: "Nora Belkacem", initials: "NB", role: "Traductrice AR→FR · à distance", when: "hier", pieces: "3 pièces", metier: "Traduction", tag: "" },
  { name: "Studio Bel Écho", initials: "BE", role: "Narration & livre audio · Lille", when: "hier", pieces: "5 pièces", metier: "Audio", tag: "Relance" },
  { name: "Marc Tissier", initials: "MT", role: "Agent littéraire · Paris", when: "il y a 2 j", pieces: "2 pièces", metier: "Agents", tag: "" },
  { name: "Librairie du Passage", initials: "LP", role: "Libraire indépendant · Dijon", when: "il y a 3 j", pieces: "4 pièces", metier: "Librairie", tag: "" }
];

class Component extends DCLogic {
  state = {
    screen: "dash", query: "", verifFilter: 0, dossier: 0,
    controles: [true, true, true, false, false],
    modFilter: 0, userFilter: 0, litige: 0, decision: 0,
    preFilter: 0, reglagesTab: 0,
    commission: ["8 %", "8 %", "6 %"],
    iaToggles: [true, true, true, false],
    metiersOn: [true, true, true, true, true, true, true, true, true, true, false, false]
  };

  go(s) { return () => this.setState({ screen: s }); }
  avatar(initials, size) {
    const bg = (initials.charCodeAt(0) * 7) % 2 === 0 ? NAVY : ORANGE;
    return `width:${size}px;height:${size}px;min-width:${size}px;border-radius:50%;background:${bg};color:#FFF;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk',monospace;font-size:${size >= 44 ? 15 : size >= 34 ? 12 : 10}px;`;
  }
  pill(tone) {
    const map = {
      orange: `background: #FDF3F0; color: ${ORANGE};`,
      navy: "background: #EEF3F8; color: #022746;",
      green: "background: #ECF5EF; color: #2E6B45;",
      grey: "background: #F4F6F9; color: #66768A;"
    };
    return `font-size: 12px; padding: 5px 10px; border-radius: 999px; font-family: 'Space Grotesk', monospace; ${map[tone] || map.grey}`;
  }
  chip(on) {
    return `border: 1px solid ${on ? NAVY : "#E1E7ED"}; background: ${on ? NAVY : "#FFF"}; color: ${on ? "#FFF" : "#4A5A6B"}; border-radius: 999px; padding: 8px 14px; font-size: 13px;`;
  }

  renderVals() {
    const s = this.state;
    const d = DOSSIERS[s.dossier];
    const litiges = [
      { num: "LIT-2026-041", title: "Rapport de lecture non livré", parties: "Éditions Pampa / Paul Ferrand", montant: "640 €", urgence: "72 h dépassées", tone: "orange", commande: "2477-01" },
      { num: "LIT-2026-040", title: "Couverture soupçonnée générée par IA", parties: "Camille D. / Studio Halo", montant: "480 €", urgence: "IA", tone: "orange", commande: "2468-03" },
      { num: "LIT-2026-038", title: "Retard de 3 semaines sur l'impression", parties: "Collectif Encre Vive / Imprimerie Baudry", montant: "1 190 €", urgence: "En échange", tone: "navy", commande: "2455-02" },
      { num: "LIT-2026-035", title: "Périmètre de correction contesté", parties: "Nora B. / Atelier Virgule", montant: "260 €", urgence: "Décision rédigée", tone: "grey", commande: "2441-04" }
    ];
    const L = litiges[s.litige];

    return {
      query: s.query, onQuery: e => this.setState({ query: e.target.value }),

      nav: NAV.map(([id, label, badge, group]) => ({
        label, badge, group,
        onClick: this.go(id),
        groupStyle: group
          ? `padding: 18px 20px 8px; font-family: 'Space Grotesk', monospace; font-size: 10px; letter-spacing: .14em; text-transform: uppercase; color: #5C7793;`
          : "display: none;",
        style: `display: flex; align-items: center; gap: 8px; margin: 0 10px; padding: 10px 12px; border-radius: 8px; font-size: 14px; cursor: pointer; color: ${s.screen === id ? "#FFF" : "#B9CBDC"}; background: ${s.screen === id ? "rgba(255,255,255,.12)" : "transparent"}; box-shadow: ${s.screen === id ? `inset 3px 0 0 ${ORANGE}` : "none"};`,
        badgeStyle: badge ? `font-family: 'Space Grotesk', monospace; font-size: 11px; background: ${ORANGE}; color: #FFF; padding: 2px 7px; border-radius: 999px;` : "display: none;"
      })),

      isDash: s.screen === "dash", isVerif: s.screen === "verif", isModeration: s.screen === "moderation",
      isUsers: s.screen === "users", isCatalogue: s.screen === "catalogue", isMissions: s.screen === "missions",
      isFinances: s.screen === "finances", isLitiges: s.screen === "litiges", isAvis: s.screen === "avis",
      isPreouverture: s.screen === "preouverture", isCms: s.screen === "cms", isReglages: s.screen === "reglages",
      goModeration: this.go("moderation"),

      kpis: [
        { k: "Prestataires actifs", v: "5 890", d: "+214 cette semaine", up: true },
        { k: "Missions ouvertes", v: "148", d: "+12", up: true },
        { k: "Commandes du mois", v: "312", d: "+8 %", up: true },
        { k: "Commission encaissée", v: "24 180 €", d: "+11 %", up: true },
        { k: "Délai de modération", v: "9 h", d: "cible 24 h", up: true }
      ].map(k => ({ ...k, deltaStyle: `font-size: 13px; margin-top: 4px; color: ${k.up ? "#2E6B45" : ORANGE};` })),

      chart: [["jan", 30, 8], ["fév", 42, 10], ["mar", 38, 6], ["avr", 55, 14], ["mai", 61, 9], ["juin", 74, 16], ["juil", 82, 12], ["août", 91, 24]].map(([m, ok, pend]) => ({
        m,
        barOk: `width: 100%; height: ${ok * 0.9}%; background: ${NAVY}; border-radius: 0 0 3px 3px;`,
        barPending: `width: 100%; height: ${pend * 0.9}%; background: ${ORANGE}; border-radius: 3px 3px 0 0;`
      })),

      files: [
        { label: "Profils à vérifier", note: "justificatif d'activité et engagement IA", n: 24, age: "6 h", sla: "Dans les temps", tone: "green", screen: "verif" },
        { label: "Contenus signalés", note: "7 soupçons d'IA générative", n: 18, age: "3 h", sla: "Dans les temps", tone: "green", screen: "moderation" },
        { label: "Missions à modérer", note: "avant publication publique", n: 12, age: "28 h", sla: "SLA dépassé", tone: "orange", screen: "missions" },
        { label: "Litiges ouverts", note: "médiation sous 72 h", n: 4, age: "4 j", sla: "1 en retard", tone: "orange", screen: "litiges" },
        { label: "Avis contestés", note: "contestation par le prestataire", n: 5, age: "18 h", sla: "Dans les temps", tone: "green", screen: "avis" }
      ].map(f => ({ ...f, onClick: this.go(f.screen), slaStyle: this.pill(f.tone) })),

      activite: [
        { txt: "Profil « Imprimerie Feuillage » validé, badge vérifié attribué", who: "Awa D.", when: "il y a 12 min", tone: "green" },
        { txt: "Prestation retirée : illustration soupçonnée générée par IA", who: "Thomas B.", when: "il y a 40 min", tone: "orange" },
        { txt: "Litige LIT-2026-041 : pièce complémentaire demandée", who: "Thomas B.", when: "il y a 1 h", tone: "navy" },
        { txt: "Commission du niveau Expert passée de 6,5 % à 6 %", who: "Samuel O.", when: "il y a 3 h", tone: "navy" },
        { txt: "Vague 3 d'invitations envoyée à 180 correcteurs", who: "Awa D.", when: "hier", tone: "navy" },
        { txt: "Avis contesté maintenu après examen des échanges", who: "Awa D.", when: "hier", tone: "grey" }
      ].map(a => ({
        ...a,
        dot: `width: 8px; height: 8px; min-width: 8px; border-radius: 50%; margin-top: 6px; background: ${a.tone === "orange" ? ORANGE : a.tone === "green" ? "#2E6B45" : a.tone === "navy" ? NAVY : "#C3CEDA"};`
      })),

      verifFilters: ["Tous (24)", "Priorité (5)", "Relances (3)", "Refus proposés (2)"].map((label, i) => ({
        label, onClick: () => this.setState({ verifFilter: i }), style: this.chip(s.verifFilter === i)
      })),
      dossiers: DOSSIERS.map((x, i) => ({
        ...x, onClick: () => this.setState({ dossier: i }),
        avatar: this.avatar(x.initials, 38),
        row: `display: flex; gap: 14px; align-items: center; padding: 15px 18px; border-bottom: 1px solid #F2F5F8; cursor: pointer; background: ${s.dossier === i ? "#FBFCFE" : "#FFF"}; box-shadow: ${s.dossier === i ? `inset 3px 0 0 ${ORANGE}` : "none"};`,
        tagStyle: x.tag ? this.pill(x.tag === "Priorité" ? "orange" : "navy") : "display: none;"
      })),
      dossierName: d.name, dossierRole: d.role, dossierInitials: d.initials, dossierAvatar: this.avatar(d.initials, 46),
      dossierMeta: [
        { k: "Métier déclaré", v: d.metier }, { k: "Statut juridique", v: "Micro-entreprise" },
        { k: "SIRET", v: "vérifié" }, { k: "Références", v: "2 sur 2 confirmées" },
        { k: "Engagement IA", v: "signé" }
      ],
      dossierPieces: [
        { ext: "PDF", name: "avis-de-situation-insee.pdf", ok: "Conforme", tone: "green" },
        { ext: "PDF", name: "attestation-assurance.pdf", ok: "Conforme", tone: "green" },
        { ext: "PDF", name: "engagement-sans-ia-signe.pdf", ok: "Signé", tone: "orange" },
        { ext: "JPG", name: "portfolio-3-realisations.jpg", ok: "À contrôler", tone: "grey" }
      ].map(p => ({ ...p, okStyle: this.pill(p.tone) })),
      controles: [
        "Identité et existence légale vérifiées",
        "Références professionnelles contactées",
        "Engagement sans IA générative signé",
        "Portfolio contrôlé — pas d'image générée",
        "Tarifs cohérents avec le marché du métier"
      ].map((label, i) => ({
        label,
        onClick: () => { const arr = s.controles.slice(); arr[i] = !arr[i]; this.setState({ controles: arr }); },
        check: s.controles[i] ? "✓" : "",
        row: "display: flex; gap: 10px; align-items: center; cursor: pointer;",
        box: `width: 18px; height: 18px; min-width: 18px; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #FFF; border: 1.5px solid ${s.controles[i] ? NAVY : "#C3CEDA"}; background: ${s.controles[i] ? NAVY : "#FFF"};`
      })),

      modFilters: ["Tout (18)", "IA générative (7)", "Hors plateforme (4)", "Droits & plagiat (4)", "Autre (3)"].map((label, i) => ({
        label, onClick: () => this.setState({ modFilter: i }), style: this.chip(s.modFilter === i)
      })),
      signalements: [
        { motif: "IA générative", tone: "orange", source: "Signalement client", when: "il y a 2 h", title: "Couverture illustrée — « Le Sel des jours »", who: "Studio Halo", where: "Prestation nº 1284, commande 2468-03", extrait: "Le client relève des mains à six doigts et une typographie déformée sur le visuel livré. Le prestataire n'a pas fourni de fichiers de travail intermédiaires malgré deux demandes.", risque: "Risque élevé" },
        { motif: "IA générative", tone: "orange", source: "Contrôle interne", when: "il y a 5 h", title: "Présentation de vitrine — texte suspect", who: "Correction Plume & Cie", where: "Profil nº 4471", extrait: "Formulations génériques répétées à l'identique sur trois profils créés le même jour, depuis la même adresse IP. Métiers déclarés incohérents entre eux.", risque: "Faisceau d'indices" },
        { motif: "Hors plateforme", tone: "navy", source: "Détection messagerie", when: "hier", title: "Proposition de règlement par virement direct", who: "Marc T. → Éditions La Ligne", where: "Conversation nº 8842", extrait: "« On peut régler ça en direct, ça vous évitera la commission. » Message intercepté avant envoi, expéditeur averti automatiquement.", risque: "Premier avertissement" },
        { motif: "Droits", tone: "grey", source: "Signalement prestataire", when: "hier", title: "Portfolio reprenant des visuels d'un tiers", who: "Atelier Lumen", where: "Profil nº 3120", extrait: "Deux illustrations du portfolio apparaissent sur le site d'une autre illustratrice inscrite, avec une antériorité établie.", risque: "À instruire" },
        { motif: "Autre", tone: "grey", source: "Signalement client", when: "il y a 2 j", title: "Prestation au périmètre trompeur", who: "Rapido Correction", where: "Prestation nº 2016", extrait: "« Correction complète » annoncée à 39 € pour 500 000 signes : périmètre irréaliste au regard de la charte tarifaire du métier.", risque: "À instruire" }
      ].map(x => ({ ...x, motifStyle: this.pill(x.tone) })),

      userFilters: ["Tous", "Prestataires", "Porteurs de projet", "Organisations", "Suspendus"].map((label, i) => ({
        label, onClick: () => this.setState({ userFilter: i }), style: this.chip(s.userFilter === i)
      })),
      users: [
        { name: "Marion Vasseur", initials: "MV", email: "marion@vasseur-correction.fr", metier: "Correction", niveau: "Confirmée", missions: "87", note: "4,9", statut: "Actif", tone: "green" },
        { name: "Atelier Kess", initials: "AK", email: "contact@atelierkess.fr", metier: "Illustration", niveau: "Experte", missions: "41", note: "5,0", statut: "Actif", tone: "green" },
        { name: "Imprimerie Baudry", initials: "IB", email: "devis@baudry-impression.fr", metier: "Impression", niveau: "Expert", missions: "213", note: "4,8", statut: "Actif", tone: "green" },
        { name: "Éditions La Ligne", initials: "EL", email: "fabrication@editionslaligne.fr", metier: "Éditeur", niveau: "Organisation", missions: "34", note: "—", statut: "Actif", tone: "green" },
        { name: "Studio Halo", initials: "SH", email: "studio.halo@mail.fr", metier: "Illustration", niveau: "Nouveau", missions: "3", note: "3,2", statut: "Sous enquête", tone: "orange" },
        { name: "Rapido Correction", initials: "RC", email: "rapido.corr@mail.fr", metier: "Correction", niveau: "Nouveau", missions: "1", note: "2,8", statut: "Suspendu", tone: "orange" },
        { name: "Sofia Renard", initials: "SR", email: "sofia.renard@trad.fr", metier: "Traduction", niveau: "Confirmée", missions: "64", note: "4,9", statut: "Actif", tone: "green" },
        { name: "Librairie du Passage", initials: "LP", email: "passage@librairie.fr", metier: "Librairie", niveau: "Nouveau", missions: "0", note: "—", statut: "En attente", tone: "navy" }
      ].map(u => ({ ...u, avatar: this.avatar(u.initials, 30), statutStyle: this.pill(u.tone) })),

      catalogue: [
        { title: "Correction complète d'un roman jusqu'à 90 000 signes", metier: "Correction", by: "Marion Vasseur", prix: "420 €", vues: "742", cmd: "12", statut: "En ligne", tone: "green" },
        { title: "Couverture illustrée + déclinaisons réseaux", metier: "Illustration", by: "Atelier Kess", prix: "480 €", vues: "1 204", cmd: "18", statut: "En ligne", tone: "green" },
        { title: "300 exemplaires broché, papier bouffant", metier: "Impression", by: "Imprimerie Baudry", prix: "1 190 €", vues: "980", cmd: "31", statut: "En ligne", tone: "green" },
        { title: "Enregistrement livre audio, 6 h de narration", metier: "Audio", by: "Studio Bel Écho", prix: "2 100 €", vues: "310", cmd: "4", statut: "En attente", tone: "navy" },
        { title: "Correction complète à 39 € tous volumes", metier: "Correction", by: "Rapido Correction", prix: "39 €", vues: "88", cmd: "1", statut: "Retirée", tone: "orange" },
        { title: "Illustration de couverture, style « aquarelle »", metier: "Illustration", by: "Studio Halo", prix: "260 €", vues: "412", cmd: "3", statut: "Suspendue", tone: "orange" },
        { title: "Mise en page intérieure, fichiers prêts à imprimer", metier: "Maquette", by: "Studio Grain", prix: "650 €", vues: "534", cmd: "9", statut: "En ligne", tone: "green" }
      ].map(c => ({ ...c, statutStyle: this.pill(c.tone) })),

      missions: [
        { title: "Recherche correcteur pour essai historique, 240 pages", by: "Éditions du Fleuve Noirci", metier: "Correction", budget: "600 – 900 €", candidatures: 7, when: "il y a 2 j", flag: "", tone: "" },
        { title: "Illustrateur album jeunesse 3-6 ans, 24 pages", by: "Camille D.", metier: "Illustration", budget: "1 800 – 2 500 €", candidatures: 14, when: "il y a 3 j", flag: "", tone: "" },
        { title: "Correction de 12 romans, forfait global", by: "Presse Rapide SARL", metier: "Correction", budget: "900 €", candidatures: 0, when: "il y a 4 h", flag: "Budget hors marché", tone: "orange" },
        { title: "Traduction ES→FR d'un recueil de nouvelles", by: "Éditions Pampa", metier: "Traduction", budget: "3 200 €", candidatures: 4, when: "il y a 4 j", flag: "", tone: "" },
        { title: "Rewriting de fiches produits, gros volume", by: "Contenu Web Plus", metier: "Rédaction", budget: "400 €", candidatures: 0, when: "il y a 6 h", flag: "Hors périmètre livre", tone: "orange" },
        { title: "Narrateur pour livre audio, 7 h de texte", by: "Studio Bel Écho", metier: "Audio", budget: "2 400 €", candidatures: 6, when: "il y a 1 sem.", flag: "", tone: "" }
      ].map(m => ({ ...m, flagStyle: m.flag ? this.pill("orange") : "display: none;" })),

      finKpis: [
        { k: "Volume d'affaires", v: "302 400 €", note: "312 commandes", dark: false },
        { k: "Commission encaissée", v: "24 180 €", note: "taux moyen 8,0 %", dark: true },
        { k: "Panier moyen", v: "969 €", note: "+6 % vs juillet", dark: false },
        { k: "Impayés", v: "3 840 €", note: "4 factures en retard", dark: false }
      ].map(k => ({
        ...k, card: `border-radius: 12px; padding: 18px; ${k.dark ? `background: ${NAVY}; color: #E4EDF5;` : "background: #FFF; border: 1px solid #E8ECF1; color: #022746;"}`
      })),
      commandes: [
        { num: "2481-03", title: "Correction complète — essai historique", parties: "Fleuve Noirci → M. Vasseur", montant: "780 €", commission: "62 €", statut: "En cours", tone: "navy" },
        { num: "2477-01", title: "Couverture illustrée + déclinaisons", parties: "Camille D. → Atelier Kess", montant: "480 €", commission: "38 €", statut: "Livrée", tone: "green" },
        { num: "2469-02", title: "300 ex. broché papier bouffant", parties: "Encre Vive → Baudry", montant: "1 190 €", commission: "95 €", statut: "En cours", tone: "navy" },
        { num: "2468-03", title: "Illustration de couverture", parties: "Camille D. → Studio Halo", montant: "480 €", commission: "38 €", statut: "En litige", tone: "orange" },
        { num: "2455-04", title: "Maquette intérieure, 240 pages", parties: "La Ligne → Studio Grain", montant: "650 €", commission: "52 €", statut: "Réglée", tone: "green" },
        { num: "2441-01", title: "Traduction ES→FR, recueil", parties: "Pampa → S. Renard", montant: "3 200 €", commission: "256 €", statut: "Réglée", tone: "green" },
        { num: "2429-02", title: "Préparation de copie, 3 titres", parties: "Encre Vive → M. Vasseur", montant: "820 €", commission: "66 €", statut: "Retard", tone: "orange" }
      ].map(c => ({ ...c, statutStyle: this.pill(c.tone) })),
      impayes: [
        { who: "Presse Rapide SARL", retard: "facture 2418-02 · 41 jours", montant: "1 240 €" },
        { who: "Contenu Web Plus", retard: "facture 2402-01 · 36 jours", montant: "980 €" },
        { who: "Ateliers Mémoires", retard: "facture 2396-03 · 12 jours", montant: "1 620 €" }
      ],
      commissionMetier: [
        { metier: "Correction", montant: "7 420 €", pct: 100 },
        { metier: "Illustration", montant: "5 860 €", pct: 79 },
        { metier: "Impression", montant: "4 910 €", pct: 66 },
        { metier: "Traduction", montant: "3 480 €", pct: 47 },
        { metier: "Presse & com", montant: "1 320 €", pct: 18 }
      ].map(c => ({ ...c, bar: `height: 100%; width: ${c.pct}%; background: ${ORANGE};` })),

      litiges: litiges.map((x, i) => ({
        ...x, onClick: () => this.setState({ litige: i }),
        row: `padding: 16px 18px; border-bottom: 1px solid #F2F5F8; cursor: pointer; background: ${s.litige === i ? "#FBFCFE" : "#FFF"}; box-shadow: ${s.litige === i ? `inset 3px 0 0 ${ORANGE}` : "none"};`,
        urgenceStyle: this.pill(x.tone)
      })),
      litigeNum: L.num, litigeTitle: L.title, litigeParties: L.parties, litigeMontant: L.montant, litigeCommande: L.commande,
      litigeTimeline: [
        { when: "18 août", label: "Commande passée", note: "Devis accepté, contrat de prestation signé par les deux parties." },
        { when: "2 sept.", label: "Livraison contestée", note: "Le client conteste le périmètre livré et refuse la validation." },
        { when: "3 sept.", label: "Échange encadré ouvert", note: "72 h pour trouver un accord dans la messagerie, modérateur en lecture." },
        { when: "6 sept.", label: "Médiation saisie", note: "Aucun accord. Pièces réunies : brief, livrable, échanges, contrat." }
      ],
      decisions: [
        { label: "Règlement intégral au prestataire", note: "Le livrable est conforme au brief contractuel." },
        { label: "Répartition 50 / 50", note: "Périmètre ambigu, responsabilité partagée." },
        { label: "Remboursement intégral du client", note: "Livrable non conforme ou manquement à la charte." },
        { label: "Reprise du travail sous 10 jours", note: "Le prestataire complète, sans supplément." }
      ].map((x, i) => ({
        ...x, onClick: () => this.setState({ decision: i }),
        row: `display: flex; gap: 12px; align-items: flex-start; padding: 13px 15px; border: 1px solid ${s.decision === i ? NAVY : "#E8ECF1"}; border-radius: 10px; cursor: pointer; background: ${s.decision === i ? "#FBFCFE" : "#FFF"};`,
        dot: `width: 16px; height: 16px; min-width: 16px; border-radius: 50%; margin-top: 2px; border: 1.5px solid ${s.decision === i ? NAVY : "#C3CEDA"}; box-shadow: ${s.decision === i ? `inset 0 0 0 3px #FFF, inset 0 0 0 8px ${NAVY}` : "none"};`
      })),

      avisSignales: [
        { motif: "Contestation prestataire", tone: "navy", note: "1,0", when: "il y a 4 h", txt: "Travail bâclé, délais non tenus, je déconseille.", auteur: "Presse Rapide SARL", cible: "Marion Vasseur", mission: "commande 2418-02", contestation: "La commande a été annulée par le client avant livraison, puis facturée. Aucun livrable n'a été rendu : l'avis ne porte pas sur une prestation réalisée." },
        { motif: "Soupçon de faux avis", tone: "orange", note: "5,0", when: "hier", txt: "Parfait, rapide, je recommande vivement !", auteur: "Compte créé le 24 août", cible: "Rapido Correction", mission: "commande 2471-05", contestation: "Trois avis 5 étoiles déposés le même jour depuis la même adresse IP que le prestataire." },
        { motif: "Propos inappropriés", tone: "orange", note: "2,0", when: "il y a 2 j", txt: "Avis contenant des propos personnels sur la prestataire, masqué en attente d'examen.", auteur: "Client anonymisé", cible: "Nora Belkacem", mission: "commande 2460-01", contestation: "Signalé par la prestataire au titre de la charte : attaque personnelle sans lien avec la prestation." },
        { motif: "Contestation prestataire", tone: "navy", note: "3,0", when: "il y a 3 j", txt: "Bon travail mais deux allers-retours de plus que prévu.", auteur: "Éditions Pampa", cible: "Studio Grain", mission: "commande 2444-02", contestation: "Le prestataire produit les échanges montrant que les allers-retours supplémentaires venaient de changements de brief côté client." }
      ].map(a => ({ ...a, motifStyle: this.pill(a.tone) })),

      preKpis: [
        { k: "Prestataires inscrits", v: "5 890", d: "objectif 6 500" },
        { k: "Liste d'attente clients", v: "2 140", d: "+310 cette semaine" },
        { k: "Invitations envoyées", v: "3 vagues", d: "1 240 accès ouverts" },
        { k: "Métiers couverts", v: "10 / 12", d: "agents et salons à combler" }
      ],
      preFilters: ["Tous", "Auteurs", "Clients"].map((label, i) => ({
        label, onClick: () => this.setState({ preFilter: i }), style: this.chip(s.preFilter === i)
      })),
      attente: [
        { name: "Claire Lemoine", email: "claire.lemoine@mail.fr", profil: "Autrice", when: "24 août", acces: "Ouvert", tone: "green" },
        { name: "Éditions du Cardan", email: "contact@cardan.fr", profil: "Éditeur", when: "24 août", acces: "Vague 4", tone: "navy" },
        { name: "Yann Prigent", email: "yann.prigent@mail.fr", profil: "Imprimeur", when: "23 août", acces: "Vague 4", tone: "navy" },
        { name: "Salon du livre de Brest", email: "orga@salonbrest.fr", profil: "Événement", when: "22 août", acces: "À qualifier", tone: "orange" },
        { name: "Amina Cherif", email: "amina.cherif@mail.fr", profil: "Autrice", when: "22 août", acces: "Ouvert", tone: "green" },
        { name: "Librairie Ombres", email: "bonjour@ombres.fr", profil: "Libraire", when: "21 août", acces: "Vague 4", tone: "navy" },
        { name: "Collectif Papier Bleu", email: "collectif@papierbleu.fr", profil: "Collectif", when: "20 août", acces: "Ouvert", tone: "green" }
      ].map(a => ({ ...a, accesStyle: this.pill(a.tone) })),
      couverture: [
        { metier: "Correction", n: "1 105", pct: 100, color: "#022746" },
        { metier: "Illustration", n: "860", pct: 78, color: "#022746" },
        { metier: "Librairie", n: "690", pct: 62, color: "#022746" },
        { metier: "Agents littéraires", n: "62", pct: 12, color: "#D85D3F" },
        { metier: "Salons & événements", n: "98", pct: 18, color: "#D85D3F" }
      ].map(c => ({ ...c, bar: `height: 100%; width: ${c.pct}%; background: ${c.pct < 25 ? ORANGE : NAVY};` })),

      articles: [
        { title: "Combien coûte vraiment la fabrication d'un roman en autoédition ?", cat: "Tarifs", auteur: "Léa Rousset", vues: "8 420", statut: "Publié", tone: "green" },
        { title: "Cession de droits en illustration : les cinq lignes à ne pas oublier", cat: "Contrats", auteur: "Léa Rousset", vues: "3 180", statut: "Publié", tone: "green" },
        { title: "Préparation de copie ou correction : ce que vous achetez", cat: "Métier", auteur: "Awa Diallo", vues: "2 640", statut: "Publié", tone: "green" },
        { title: "Pourquoi nous interdisons l'IA générative", cat: "Plateforme", auteur: "Samuel Ohayon", vues: "—", statut: "Relecture", tone: "orange" },
        { title: "Choisir son papier sans se ruiner", cat: "Fabrication", auteur: "Léa Rousset", vues: "—", statut: "Brouillon", tone: "grey" },
        { title: "Page « Tarifs et commission »", cat: "Page", auteur: "Samuel Ohayon", vues: "5 910", statut: "Publié", tone: "green" }
      ].map(a => ({ ...a, statutStyle: this.pill(a.tone) })),

      reglagesNav: ["Commission & niveaux", "Politique IA", "Métiers", "Modération", "Équipe & droits"].map((label, i) => ({
        label, onClick: () => this.setState({ reglagesTab: i }),
        style: `padding: 11px 13px; border-radius: 9px; font-size: 14px; cursor: pointer; background: ${s.reglagesTab === i ? "#F4F6F9" : "transparent"}; color: ${s.reglagesTab === i ? NAVY : "#66768A"}; font-weight: ${s.reglagesTab === i ? 500 : 400};`
      })),
      reglagesTitle: ["Commission & niveaux", "Politique IA", "Métiers", "Modération", "Équipe & droits"][s.reglagesTab],
      commissionRows: ["Nouveau", "Confirmé", "Expert"].map((niveau, i) => ({
        niveau, pct: s.commission[i],
        seuil: ["dès l'inscription", "à partir de 30 missions", "à partir de 120 missions"][i],
        onPct: e => { const arr = s.commission.slice(); arr[i] = e.target.value; this.setState({ commission: arr }); }
      })),
      iaReglages: [
        { label: "Engagement obligatoire à l'inscription", note: "Bloque la création de compte prestataire sans signature." },
        { label: "Rappel au dépôt de contenu", note: "Bandeau sur la création de prestation et le portfolio." },
        { label: "Retrait automatique au second signalement fondé", note: "Suspension du profil en attente d'examen." },
        { label: "Contrôle systématique des visuels à la publication", note: "Coûteux en temps de modération : 4 h par jour environ." }
      ].map((x, i) => ({
        ...x, onClick: () => { const arr = s.iaToggles.slice(); arr[i] = !arr[i]; this.setState({ iaToggles: arr }); },
        track: `width: 44px; height: 24px; min-width: 44px; border-radius: 999px; background: ${s.iaToggles[i] ? ORANGE : "#DCE3EA"}; display: flex; align-items: center; padding: 3px; box-sizing: border-box; justify-content: ${s.iaToggles[i] ? "flex-end" : "flex-start"};`,
        knob: "width: 18px; height: 18px; border-radius: 50%; background: #FFF; display: block;"
      })),
      metiersReglage: ["Auteurs", "Illustrateurs", "Correcteurs", "Traducteurs", "Maquettistes", "Éditeurs", "Imprimeurs", "Presse & com", "Libraires", "Narrateurs audio", "Agents littéraires", "Salons & événements"].map((label, i) => ({
        label,
        onClick: () => { const arr = s.metiersOn.slice(); arr[i] = !arr[i]; this.setState({ metiersOn: arr }); },
        style: `border: 1px solid ${s.metiersOn[i] ? ORANGE : "#E1E7ED"}; background: ${s.metiersOn[i] ? "#FDF3F0" : "#FFF"}; color: ${s.metiersOn[i] ? ORANGE : "#8496A8"}; border-radius: 999px; padding: 9px 15px; font-size: 14px;`
      }))
    };
  }
}

