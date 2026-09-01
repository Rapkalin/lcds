# Relevés Figma

Le connecteur Figma est **limité en nombre d'appels** (offre Starter). Ce dossier
est le cache : tout ce qui a déjà été relevé est ici, **versionné**, et ne doit
plus jamais être redemandé à Figma.

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
