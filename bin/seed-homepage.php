<?php

/**
 * Amorçage de la page d'accueil — joué par bin/init.sh via `wp eval-file`.
 *
 * Crée la page, la désigne comme page d'accueil du site, et y insère les quatre
 * blocs de section garnis. Tous les champs sont remplis, y compris les images
 * quand les visuels de démonstration sont en place : un contributeur doit voir
 * l'aperçu de la section qu'il modifie, pas un bloc vide.
 *
 * La copie vient des maquettes, sauf là où elles portent du lorem ipsum (texte
 * de la carte du hero, panneaux de l'accordéon) : ces passages sont une
 * rédaction de démonstration, à remplacer par le client — voir
 * readme/contribution.md.
 *
 * IDEMPOTENT. Une page d'accueil déjà en place n'est jamais réécrite : le
 * contenu saisi par un contributeur ne doit pas être écrasé au redémarrage d'un
 * conteneur. Passer `force` en argument POSITIONNEL pour la recréer
 * volontairement : `wp eval-file bin/seed-homepage.php force`. WP-CLI refuse
 * les options inconnues sur eval-file, un `--force` serait rejeté.
 *
 * @package lcds
 */

if (! defined('WP_CLI')) {
    return;
}

$force = in_array('force', (array) ($args ?? []), true);
$existing = (int) get_option('page_on_front');

if (! $force && $existing > 0 && get_post_status($existing) === 'publish') {
    WP_CLI::log('==> [init] Page d\'accueil déjà en place (ID ' . $existing . ').');

    return;
}

/**
 * Traduit un tableau de valeurs imbriqué en métadonnées ACF.
 *
 * ACF n'attend pas seulement `galerie_0_forme` : il exige, à côté de chaque
 * valeur, une clé préfixée d'un `_` portant la CLÉ du champ. Sans elle il ne
 * sait pas à quel champ la valeur appartient, et les répéteurs remontent vides
 * — constaté : le hero fonctionnait, les trois blocs à répéteur rendaient rien.
 *
 * Les clés sont résolues depuis le groupe de champs, jamais écrites en dur :
 * renommer une clé dans le JSON ne doit pas casser cet amorçage.
 */
$to_meta = static function (array $fields, array $values, string $prefix = '') use (&$to_meta): array {
    $meta = [];

    foreach ($fields as $field) {
        $name = (string) $field['name'];

        if (! array_key_exists($name, $values)) {
            continue;
        }

        $path = $prefix . $name;

        if (($field['type'] ?? '') === 'repeater') {
            $rows = is_array($values[$name]) ? $values[$name] : [];
            $meta[$path] = count($rows);
            $meta['_' . $path] = $field['key'];

            foreach ($rows as $index => $row) {
                $meta = array_merge($meta, $to_meta(
                    (array) ($field['sub_fields'] ?? []),
                    (array) $row,
                    $path . '_' . $index . '_',
                ));
            }

            continue;
        }

        $meta[$path] = $values[$name];
        $meta['_' . $path] = $field['key'];
    }

    return $meta;
};

$block = static function (string $name, string $group, array $fields) use ($to_meta): string {
    $definition = function_exists('acf_get_fields') ? acf_get_fields($group) : [];

    return '<!-- wp:acf/' . $name . ' ' . wp_json_encode([
        'name' => 'acf/' . $name,
        'data' => $to_meta((array) $definition, $fields),
        'mode' => 'auto',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' /-->';
};

$p = static fn(string ...$paragraphs): string => implode(
    "\n",
    array_map(static fn(string $text): string => '<p>' . $text . '</p>', $paragraphs),
);

/**
 * Identifiant du visuel de démonstration occupant un emplacement, ou '' si les
 * visuels ne sont pas en place.
 *
 * `bin/seed-demo.sh` extrait les photos des PDF de maquette et enregistre la
 * correspondance emplacement → identifiant dans l'option `lcds_demo_media`.
 * Ce script est hors du dépôt car il dépend de maquettes qui n'y sont pas : sur
 * un environnement où il n'a pas tourné, les champs image restent vides et les
 * blocs affichent leur indice de contribution. Le rendu se dégrade, l'amorçage
 * ne casse pas.
 */
$media_map = get_option('lcds_demo_media');
$media_map = is_array($media_map) ? $media_map : [];
$media = static fn(string $slot): int|string => isset($media_map[$slot])
    ? (int) $media_map[$slot]
    : '';

// Les maquettes ne portent aucune destination : les pages cibles n'existent pas
// encore. Un `#` rend le bouton visible dans l'aperçu — un lien VIDE fait
// disparaître le composant CTA, qui refuse de produire un lien mort.
$stub = '#';

$content = implode("\n\n", [
    $block('lcds-hero', 'group_lcds_hero', [
        'titre_h1' => 'Cabinet d’orthodontie à Vienne',
        'visuel' => $media('hero'),
        'carte_vignette' => $media('hero-thumbnail'),
        'carte_lien' => ['title' => 'Prendre RDV', 'url' => $stub, 'target' => ''],
        'carte_texte' => 'Du lundi au vendredi, de 9h à 18h.',
    ]),

    $block('lcds-histoire', 'group_lcds_histoire', [
        'etiquette' => 'l’histoire',
        'puce' => 'turquoise',
        'texte' => $p(
            'Depuis septembre 1986, le Docteur Yann Le Fur et son équipe n’ont cessé de développer la Clinique du Sourire. Après 40 ans, il s’est associé aux praticiens présents depuis plusieurs années afin de faire perdurer le professionnalisme et l’ambiance familiale du cabinet.',
            'Le cabinet est représenté aujourd’hui par le Dr Yann Le Fur, le Dr Sofia Denarié, le Dr Boris Fouquet, le Dr Martin Monteil et le Dr Alice Le Fur.',
        ),
        'cta' => ['title' => 'en savoir plus', 'url' => $stub, 'target' => ''],
        'galerie_libelle' => 'Le cabinet en images',
        'galerie' => [
            ['forme' => 'large', 'image' => $media('gallery-1'), 'image_2' => ''],
            ['forme' => 'pair', 'image' => $media('gallery-2a'), 'image_2' => $media('gallery-2b')],
            ['forme' => 'medium', 'image' => $media('gallery-3'), 'image_2' => ''],
            ['forme' => 'small', 'image' => $media('gallery-4'), 'image_2' => ''],
        ],
    ]),

    $block('lcds-traitements', 'group_lcds_traitements', [
        'etiquette' => 'Les différents traitements',
        'puce' => 'turquoise',
        'entrees' => [
            [
                'titre' => 'Interceptif',
                'texte' => $p('Mené chez l’enfant pendant la croissance, il corrige une anomalie dès son apparition — manque de place, décalage des mâchoires, mauvaise habitude de succion. Intervenir tôt évite souvent un traitement plus long à l’adolescence.'),
                'ouvert' => 0,
            ],
            [
                'titre' => 'Gouttières / Aligneurs',
                'texte' => $p('Une série de gouttières transparentes sur mesure déplace les dents par petits mouvements successifs. Amovibles et discrètes, elles demandent en contrepartie un port rigoureux de 22 heures par jour.'),
                'ouvert' => 0,
            ],
            [
                'titre' => 'Multibagues',
                // Seule entrée dépliée sur la maquette : c'est l'état ouvert
                // qui y a été dessiné.
                'texte' => $p('Des attaches collées sur chaque dent et reliées par un arc appliquent une force continue. C’est le traitement le plus polyvalent : il corrige les situations que les gouttières ne peuvent pas atteindre.'),
                'ouvert' => 1,
            ],
            [
                'titre' => 'Orthèses pour l’apnée du sommeil',
                'texte' => $p('L’orthèse d’avancée mandibulaire maintient la mâchoire inférieure en avant pendant la nuit et dégage le passage de l’air. Elle se porte sur prescription, après un enregistrement du sommeil.'),
                'ouvert' => 0,
            ],
            [
                'titre' => 'Protège-dents',
                'texte' => $p('Réalisé sur empreinte, il absorbe les chocs lors de la pratique sportive et protège dents, lèvres et articulations. Un modèle sur mesure tient mieux qu’un modèle thermoformé du commerce.'),
                'ouvert' => 0,
            ],
        ],
        'cta' => ['title' => 'voir tous les traitements', 'url' => $stub, 'target' => ''],
    ]),

    $block('lcds-parcours', 'group_lcds_parcours', [
        'etiquette' => 'le parcours de soin',
        'puce' => 'orange',
        'etapes' => [
            [
                'titre' => 'Première consultation',
                'texte' => $p(
                    'Ce premier contact a pour but de faire votre connaissance, de définir vos attentes et de déterminer les objectifs de traitement réalisables en fonction de votre situation clinique actuelle.',
                    'Vous pourrez exprimer le motif de votre venue et l’orthodontiste réalisera un premier examen clinique qui permettra de déterminer si un traitement orthodontique est indiqué.',
                    'Si un traitement est envisagé, nous vous proposerons un rendez-vous afin de réaliser le bilan orthodontique, indispensable pour détailler le diagnostic et les possibilités de traitement ainsi que la durée approximative de celui-ci.',
                ),
                'duree' => '',
                'visuels' => [
                    ['forme' => 'step-wide', 'image' => $media('gallery-1')],
                    ['forme' => 'step-narrow', 'image' => $media('gallery-3')],
                ],
            ],
            [
                'titre' => 'Bilan orthodontique',
                'texte' => $p(
                    'Le bilan permettra de préciser le diagnostic, les objectifs thérapeutiques, le type d’appareillage le plus indiqué et la durée du traitement.',
                    'Lors de ce bilan, nous réaliserons :',
                ) . "\n<ul><li>Des photos du visage et intra-buccale</li>"
                    . '<li>Des empreintes numériques pour réaliser un moulage d’étude</li>'
                    . '<li>Des clichés radiographiques (panoramique dentaire et téléradiographie de profil/face)</li></ul>',
                'duree' => '30 min',
                'visuels' => [
                    ['forme' => 'step-narrow', 'image' => $media('gallery-4')],
                    ['forme' => 'step-wide', 'image' => $media('gallery-2a')],
                ],
            ],
            [
                'titre' => 'Compte-rendu',
                'texte' => $p('Au terme du bilan orthodontique, l’analyse des différents éléments du dossier orthodontique permettent d’établir un plan de traitement précis, qui sera donc abordé au rendez-vous de compte-rendu. Des documents vous seront fournis également (le devis précis comportant la durée du traitement et le type d’appareillage choisit ainsi que la demande d’entente préalable pour les patients de moins de 16 ans).'),
                'duree' => '45 min',
                'visuels' => [['forme' => 'step-wide', 'image' => $media('gallery-5')]],
            ],
            [
                'titre' => 'Pose de l’appareillage',
                'texte' => $p('Pour l’appareil multi-attaches, l’orthodontiste place un écarteur afin d’écarter les lèvres et avoir une bonne visibilité sur les dents. Après un nettoyage des dents, un gel est appliqué sur chaque dent afin de les rendre un peu rugueuse, ce qui permettra à la colle qui fixe les brackets de mieux s’accrocher. Puis chaque bracket sera collé sur les dents, qui seront ensuite reliés par un fil.'),
                'duree' => '',
                'visuels' => [
                    ['forme' => 'step-narrow', 'image' => $media('gallery-2b')],
                    ['forme' => 'step-wide', 'image' => $media('gallery-1')],
                ],
            ],
            [
                'titre' => 'Rendez-vous de suivi de contrôle et d’activations',
                'texte' => $p('Ce sont des rendez-vous de routine qui se répèteront environ toutes les 5 à 10 semaines. C’est lors de ces rendez-vous que l’orthodontiste change l’arc, effectue des activations d’autres appareils.'),
                'duree' => '',
                'visuels' => [
                    ['forme' => 'step-narrow', 'image' => $media('gallery-3')],
                    ['forme' => 'step-wide', 'image' => $media('gallery-4')],
                ],
            ],
            [
                'titre' => 'Dépose de l’appareil et contention',
                'texte' => $p(
                    'C’est lors de ce rendez-vous que l’appareil est enlevé. Ce n’est pas pour ça que le traitement s’arrête brutalement, bien au contraire.',
                    'Le travail de stabilisation du résultat commence avec la mise en place d’une contention permanente ou amovible (fil de contention collé sur la face interne des dents et/ou gouttière en port nocturne)',
                    'La contention est obligatoire après le traitement d’orthodontie, surtout la première année suivant la dépose de votre appareil.',
                    'Après l’alignement, les dents ont toujours tendance à revenir en position initiale. De plus, les dents bougent tout au long de la vie sous pression de la langue, des lèvres, des joues et elles se déplacent naturellement vers l’avant avec l’âge, d’où l’importance de la contention.',
                ),
                'duree' => '',
                'visuels' => [],
            ],
        ],
    ]),

    $block('lcds-technologies', 'group_lcds_technologies', [
        'etiquette' => 'les technologies',
        'puce' => 'orange',
        'cta' => ['title' => 'voir toutes les technologies', 'url' => $stub, 'target' => ''],
        // La maquette ne nomme que deux cartes : la première y est OUVERTE,
        // donc son titre est masqué. Les autres titres et tous les textes sont
        // une rédaction de démonstration.
        'cartes' => [
            [
                'titre' => 'Radiographie panoramique',
                'texte' => $p(
                    'Un cliché unique de l’ensemble des dents et des maxillaires, pris en quelques secondes debout et sans contact. Il révèle les dents non encore sorties, les racines et l’état de l’os.',
                    'La dose de rayons est très faible, comparable à celle reçue lors d’un vol de deux heures.',
                ),
                'image' => $media('gallery-4'),
                'ouvert' => 1,
            ],
            [
                'titre' => 'Cone Beam 3D',
                'texte' => $p('L’imagerie tridimensionnelle reconstitue le volume osseux et la position exacte de chaque racine. Elle n’est prescrite que lorsque la panoramique ne suffit pas à décider, par exemple pour une dent incluse.'),
                'image' => $media('gallery-1'),
                'ouvert' => 0,
            ],
            [
                'titre' => 'Empreintes numériques',
                'texte' => $p('Une caméra intra-orale remplace la pâte à empreinte : le confort est sans comparaison, et le modèle obtenu sert directement à la fabrication des gouttières. Plus de haut-le-cœur, plus d’attente.'),
                'image' => $media('gallery-3'),
                'ouvert' => 0,
            ],
            [
                'titre' => 'Aligneurs sur mesure',
                'texte' => $p('Chaque série de gouttières est usinée d’après le modèle numérique, étape par étape jusqu’à la position visée. La simulation permet de montrer le résultat avant même de commencer.'),
                'image' => $media('gallery-5'),
                'ouvert' => 0,
            ],
        ],
    ]),

    $block('lcds-infos', 'group_lcds_infos', [
        'etiquette' => 'informations pratiques',
        'puce' => 'orange',
        'visuel' => $media('gallery-2a'),
        'entrees' => [
            [
                'icone' => 'pin',
                'titre' => 'Adresse du cabinet',
                'surtitre' => 'cabinet d’orthodontie lcds',
                'texte' => $p('2 place Saint-Maurice', '38200 Vienne'),
                'lien' => ['title' => 'voir le plan', 'url' => $stub, 'target' => ''],
            ],
            [
                'icone' => 'bus',
                'titre' => 'Moyens de transport',
                'surtitre' => 'bus',
                'texte' => $p(
                    'Bus - Jardin de Ville (lignes 1, 6, 5, 4 et 7)',
                    'Bus - Saint-Maurice (lignes 1, 6, 5, 4 et 7)',
                    'Bus - SNCF Brillier (ligne 7)',
                ),
                'lien' => '',
            ],
            [
                'icone' => 'info',
                'titre' => 'Accessibilité',
                'surtitre' => '',
                'texte' => $p('Entrée accessible', 'Parking gratuit'),
                'lien' => '',
            ],
            [
                'icone' => 'clock',
                'titre' => 'Horaires',
                'surtitre' => '',
                'texte' => $p('Du lundi au vendredi : 09:00 - 19:00', 'Le Samedi : 08:00 - 13:00'),
                'lien' => '',
            ],
            [
                'icone' => 'user',
                'titre' => 'Contact',
                'surtitre' => '',
                'texte' => $p('+33 (0) 4 74 78 33 22', 'contact@lacliniquedusourire.com'),
                'lien' => '',
            ],
        ],
    ]),
]);

// wp_slash() est indispensable : wp_insert_post() attend des données ÉCHAPPÉES
// et leur applique wp_unslash() en interne. Sans cela l'antislash des séquences
// d'échappement du JSON est mangé — constaté : le « \n » séparant deux
// paragraphes devenait un paragraphe contenant la lettre « n ».
$content = wp_slash($content);

$page_id = $existing > 0 && get_post_status($existing) !== false
    ? wp_update_post(['ID' => $existing, 'post_content' => $content, 'post_status' => 'publish'], true)
    : wp_insert_post([
        'post_type' => 'page',
        'post_title' => 'Accueil',
        'post_name' => 'accueil',
        'post_status' => 'publish',
        'post_content' => $content,
    ], true);

if (is_wp_error($page_id)) {
    WP_CLI::warning('Création de la page d\'accueil échouée : ' . $page_id->get_error_message());

    return;
}

update_option('show_on_front', 'page');
update_option('page_on_front', (int) $page_id);

WP_CLI::log(sprintf(
    '==> [init] Page d\'accueil amorcée (ID %d, %d blocs).',
    (int) $page_id,
    substr_count($content, '<!-- wp:acf/'),
));
