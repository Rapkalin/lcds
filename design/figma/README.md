# Relevés Figma

Le connecteur Figma est **limité en nombre d'appels** (offre Starter). Ce dossier
est le cache : tout ce qui a déjà été relevé est ici, **versionné**, et ne doit
plus jamais être redemandé à Figma.

## Les PDF sont devenus la source principale

Les maquettes exportées en PDF vivent hors du dépôt, dans
`~/Documents/Perso/LCDS/maquettes/`. **Elles remplacent le connecteur pour tout
ce qui est géométrie et texte**, et ne coûtent aucun appel.

Le point décisif : les pages PDF font **exactement la taille des cadres Figma**
(1440 × 4268 pour la page d'accueil, 1440 × 4724,06 pour les blocs de fin). Les
coordonnées du PDF sont donc celles de la maquette, au point près.

L'outillage est `poppler` (déjà installé) :

```bash
pdfinfo  "HP_01_LCDS_hp full.pdf"                      # confirmer la taille de page
pdftotext -layout "…​.pdf" -                             # toute la copie, lisible
pdftotext -bbox   "…​.pdf" sortie.xhtml                  # position de CHAQUE mot
pdftoppm -png -r 72 -x 0 -y 900 -W 1440 -H 1328 "…​.pdf" out   # rendu 1:1 d'une zone
```

`-r 72` fait correspondre 1 point à 1 pixel : les coordonnées relevées sur le
rendu sont directement celles de la maquette. Pour un détail, monter à `-r 216`
et multiplier les coordonnées par 3.

Ce que ça permet, et que le connecteur ne donnait pas :

- **les couleurs exactes**, en échantillonnant les pixels — c'est ainsi qu'a été
  trouvé `#A8BED6`, absent des variables de bibliothèque ;
- **les cotes exactes** de tout élément, y compris les formes sans texte ;
- **la copie réelle**, à recopier sans faute de frappe.

> Piège : les libellés à fort interlettrage (étiquettes, boutons d'action) sortent
> de `pdftotext` avec des espaces parasites — « l e pa r c o u r s d e s o i n ».
> Normaliser avant de recopier.

Ce qui reste du ressort du connecteur : la **structure** des composants (quel
nœud est une instance de quoi), les **variantes**, et les tokens nommés. Le PDF
est un aplat, il ne dit pas ce qui est un composant.

## Protocole

1. **Avant tout appel, lire ce dossier.** `inventory.md` dit ce qui est déjà
   relevé. Un nœud présent dans `nodes/` ne se redemande pas.
2. **Après tout appel, écrire la réponse ici**, dans `nodes/<id>-<nom>.md`, avec
   la date du relevé. Une réponse utilisée puis jetée est un appel gaspillé.
3. **Un relevé n'est refait que si la maquette a changé** sur ce nœud. Dans ce
   cas, remplacer le fichier et mettre à jour sa date.

## Règles de consommation

Établies après avoir brûlé le quota en une session, sur 15 appels dont la moitié
n'apportait rien.

**Un seul outil par défaut : `get_design_context`.** Il renvoie en **un appel**
le code de référence, une capture d'écran *et* les tokens utilisés. Ne pas passer
`excludeScreenshot` : la capture est incluse, la redemander ensuite avec
`get_screenshot` double la dépense.

**Ne pas utiliser `get_metadata`.** Deux raisons, la seconde décisive :

- il ne renvoie ni couleur, ni police, ni contenu de composant ;
- **il n'est pas exhaustif.** Vérifié : il renvoie le cadre `hero` (`226:1189`)
  comme dépourvu d'enfants alors qu'il contient une image de fond et une carte
  flottante. Reproductible, ce n'est pas un cache.

> Corollaire : **ne jamais conclure à l'absence de quelque chose** depuis
> `get_metadata`. J'ai annoncé un hero vide sur cette base — il ne l'était pas.

**Interroger le parent, pas chaque enfant.** `get_design_context` descend tout le
sous-arbre. Un appel sur une section rapporte tous ses blocs. Sept appels de
relevé sur des enfants là où deux sur les racines auraient suffi, c'est le
principal gaspillage constaté.

**`get_variable_defs` : une fois par fichier, pas par nœud.** Les variables sont
celles de la bibliothèque, pas du nœud — deux nœuds racines très éloignés ont
renvoyé les **mêmes huit tokens**. Le relevé est dans `tokens.md`, il est fait.

**Télécharger les ressources tout de suite.** Les URL renvoyées par Figma
**expirent au bout de 7 jours**. Un relevé conservé avec ses URL d'images est un
relevé à moitié périmé : récupérer les octets dans le dépôt au moment du relevé.

## Ce que ces outils ne montrent pas

- **Les interactions de prototype** (clic, survol, Smart Animate) et les
  transitions entre variantes. Elles ne sont pas exposées. Un
  `get_motion_context` vide signifie « aucune animation par images clés », pas
  « aucune animation ».
- **Les états** autres que celui maquetté. La section services a été relevée avec
  un accordéon ouvert : les autres états sont à demander au designer.
