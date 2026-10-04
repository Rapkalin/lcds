<?php

/**
 * Amorçage de la page « Conseils » — joué par bin/init.sh via `wp eval-file`.
 *
 * Crée la page, lui pose le gabarit `template-conseils.php` — c'est ce gabarit,
 * et non l'identifiant d'URL, qui déclenche le groupe de champs — puis garnit
 * ses quatre sections.
 *
 * La copie vient de la maquette `CONSEILS/LCDS_conseils.pdf`. Les RÉPONSES de
 * la foire aux questions y sont du LOREM IPSUM, et elles sont reprises telles
 * quelles : la rédaction est à faire par le client, même parti pris que les
 * légendes du cabinet — voir readme/contribution.md. Les trois premières
 * sections, elles, portent le texte définitif.
 *
 * AUCUN VISUEL. Cette page n'en porte aucun : les seuls de la maquette sont la
 * bande de l'application et le pied de page, qui viennent des réglages du site
 * et suivent toutes les pages. Ce seed ne lit donc pas `lcds_demo_media`.
 *
 * IDEMPOTENT. Une page déjà en place n'est jamais réécrite : le contenu saisi
 * par un contributeur ne doit pas être écrasé au redémarrage d'un conteneur.
 * Passer `force` en argument POSITIONNEL pour la recréer volontairement :
 * `wp eval-file bin/seed-conseils.php force`. WP-CLI refuse les options
 * inconnues sur eval-file, un `--force` serait rejeté.
 *
 * @package lcds
 */

if (! defined('WP_CLI')) {
    return;
}

$force = in_array('force', (array) ($args ?? []), true);
$existing = get_page_by_path('conseils');
$page_id = $existing instanceof WP_Post ? (int) $existing->ID : 0;

if (! $force && $page_id > 0 && get_post_status($page_id) === 'publish') {
    WP_CLI::log('==> [init] Page « Conseils » déjà en place (ID ' . $page_id . ').');

    return;
}

if ($page_id === 0) {
    $page_id = (int) wp_insert_post([
        'post_type' => 'page',
        'post_title' => 'Conseils',
        'post_name' => 'conseils',
        'post_status' => 'publish',
    ]);
}

if ($page_id === 0) {
    WP_CLI::warning('Création de la page « Conseils » impossible.');

    return;
}

// C'est le gabarit qui porte la localisation du groupe de champs : sans lui, la
// page s'affiche mais aucun champ n'apparaît en administration.
update_post_meta($page_id, '_wp_page_template', 'template-conseils.php');

/*
 * Les champs sont désignés par leur CLÉ et non par leur nom. `titre_h1` et
 * `sections` sont aussi les noms des champs de l'accueil : résolu par le nom,
 * `update_field` tombe sur CEUX-LÀ, et les sous-champs s'évaluent alors contre
 * les layouts de l'accueil, qui n'ont pas de `groupes`. Mesuré — les quatre
 * sections s'écrivaient, leurs groupes disparaissaient en silence.
 */
update_field('field_lcds_conseils_h1', 'Conseils', $page_id);

/**
 * Une question de la foire aux questions.
 *
 * @return array{titre: string, reponse: string, ouvert: bool}
 */
$question = static fn(string $titre, string $reponse = '', bool $ouvert = false): array => [
    'titre' => $titre,
    'reponse' => $reponse,
    'ouvert' => $ouvert,
];

// Le texte que la maquette laisse en attente de rédaction. Deux paragraphes,
// comme elle les dessine.
$lorem = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum '
    . 'in ipsum vel nulla aliquam bibendum. Vivamus libero eros, venenatis a '
    . 'convallis nec, tempus ac mi. Nam a urna vitae turpis tempor ullamcorper '
    . 'non sed ligula.</p>'
    . '<p>Vestibulum consectetur nunc at arcu tincidunt scelerisque. Mauris non '
    . 'mauris est. Nunc pharetra dui ut aliquam congue. Aliquam non mauris est. '
    . 'Aliquam posuere urna ac dui consectetur, vel vulputate sem porta.</p>';

update_field('field_lcds_conseils_sections', [
    /*
     * Première section : aucune étiquette, et c'est la maquette qui le veut.
     * La colonne de gauche ne porte que le titre, et le chapô fait face au
     * vide.
     */
    [
        'acf_fc_layout' => 'accordeon',
        'titre' => 'Les urgences en orthodontie',
        'chapo' => '<p>Les urgences sont rarement graves en orthodontie. Toutefois, '
            . 'nous vous donnons ci-dessous quelques conseils. Si votre problème '
            . 'dépasse les cas évoqués, téléphonez-nous.</p>',
        'groupes' => [
            [
                'etiquette' => '',
                'puce' => 'orange',
                'questions' => [
                    $question(
                        'Appareil amovible cassé ou perdu',
                        '<p>Prévenez-nous plus ou moins rapidement pour le réparer ou le '
                        . 'refaire. Des empreintes destinées au prothésiste seront souvent '
                        . 'à réaliser au prochain rendez-vous. Nous nous gardons la '
                        . 'possibilité de vous demander une participation forfaitaire à la '
                        . 'réfection de l’appareil, en fonction des circonstances. Si '
                        . 'l’incident a eu lieu dans le contexte scolaire, n’oubliez pas de '
                        . 'faire une déclaration à votre assurance.</p>',
                        true,
                    ),
                    $question('Choc sur une dent'),
                    $question('Arc piquant, dépassant'),
                    $question('Sensibilité liée à la pression des bagues'),
                ],
            ],
        ],
    ],

    [
        'acf_fc_layout' => 'description',
        'titre' => 'Coûts et prise en charge',
        'groupes' => [
            [
                'etiquette' => '',
                'puce' => 'orange',
                'titre' => 'Quel est le coût d’un traitement orthodontique ?',
                'texte' => '<p>Le tarif des traitements d’orthodontie est libre. Le coût d’un '
                    . 'traitement orthodontique varie selon la pathologie, le type d’appareil '
                    . 'et la durée du traitement (de 6 à 36 mois) et sa prise en charge par la '
                    . 'Sécurité sociale est en fonction de l’âge du patient.<br />'
                    . 'Une première consultation a pour but de réaliser un bilan orthodontique '
                    . 'afin de déterminer si un traitement s’impose et à quel moment le '
                    . 'commencer. Sans engagement, elle permet de vous informer sur les '
                    . 'différentes options possibles.<br />'
                    . 'Le coût du traitement dépendra du type d’appareil, de la durée du '
                    . 'traitement et du problème initial.<br />'
                    . 'Dans tous les cas, un devis (pour la Sécurité sociale et votre '
                    . 'mutuelle) vous sera remis avant de débuter le traitement. Ses modalités '
                    . 'administratives vous seront également communiquées.</p>',
                'lien' => '',
            ],
            [
                'etiquette' => 'avant l’âge de 16 ans',
                'puce' => 'orange',
                'titre' => '',
                'texte' => '<p>Les traitements d’orthodontie sont pris en charge par '
                    . 'l’Assurance Maladie sous réserve d’obtenir l’accord préalable de votre '
                    . 'caisse de Sécurité sociale et s’ils sont commencés avant le 16e '
                    . 'anniversaire de l’enfant. La somme prise en charge est toujours la '
                    . 'même, quels que soient le praticien et le type d’appareil, soit '
                    . '193,50 € par semestre.<br />'
                    . 'La demande d’accord préalable est à compléter avec votre praticien. '
                    . 'Elle est ensuite à adresser au Dentiste conseil de votre caisse '
                    . 'd’Assurance Maladie. Si dans un délai de 15 jours, vous ne recevez pas '
                    . 'de notification de refus, considérez que votre demande a été '
                    . 'acceptée.<br />'
                    . 'L’accord de votre caisse d’Assurance Maladie étant valable 6 mois, vous '
                    . 'devez débuter les soins avant la fin de ce délai. Une demande peut être '
                    . 'faite pour 2 semestres consécutifs. Dans ce cas, le praticien obtient '
                    . 'un accord pour un an et l’entente préalable est valable un an.<br />'
                    . 'Votre orthodontiste vous remettra un devis détaillé avant de commencer '
                    . 'le traitement.<br />'
                    . 'Six semestres de prise en charge sont prévus quelle que soit la '
                    . 'pathologie et le praticien.</p>',
                'lien' => '',
            ],
            [
                'etiquette' => 'après l’âge de 16 ans',
                'puce' => 'orange',
                'titre' => '',
                'texte' => '<p>Après l’âge de 16 ans, le traitement est à la charge du '
                    . 'patient. Votre Organisme complémentaire vous informera des conditions '
                    . 'éventuellement prévues par votre contrat d’adhésion.<br />'
                    . 'A titre exceptionnel, une prise en charge d’un semestre (non '
                    . 'renouvelable) de traitement par l’Assurance Maladie est possible, si '
                    . 'une préparation orthodontique est nécessaire en préalable à une '
                    . 'intervention de chirurgie maxillo-faciale. La présentation du '
                    . 'certificat du chirurgien vous sera demandée.<br />'
                    . 'La différence entre les honoraires et le remboursement de la Sécurité '
                    . 'Sociale peut être prise en charge, partiellement ou en totalité, par '
                    . 'votre complémentaire de santé. Ce remboursement varie selon le contrat '
                    . 'souscrit. Renseignez-vous auprès de votre mutuelle.<br />'
                    . 'La période de contention (consolidation des résultats obtenus) est '
                    . 'prise en charge sur une durée de deux ans. Le remboursement varie selon '
                    . 'qu’il s’agit de la 1ère ou de la 2e année de soin.</p>',
                'lien' => '',
            ],
        ],
    ],

    [
        'acf_fc_layout' => 'description',
        'titre' => 'Les conseils de brossage',
        'groupes' => [
            [
                // Relevé sur la maquette : la puce vaut rgb(4, 139, 140), le
                // turquoise du système — et non l'orange des autres sections.
                'etiquette' => 'l’entretien',
                'puce' => 'turquoise',
                'titre' => '',
                'texte' => '<p>Si l’on utilise des bagues, le brossage des dents doit être '
                    . 'effectué avec encore plus de soins et de minutie qu’en temps normal. '
                    . 'Les microbes, les bactéries ont beaucoup plus de recoins où se cacher. '
                    . 'Bien brosser ses dents permet aux dents de se déplacer dans une gencive '
                    . 'saine, sans frottement (pas de saleté sur les fils ralentissant les '
                    . 'déplacements).</p>'
                    . '<p>Les premiers signes d’une hygiène mal adaptée sont une gencive qui '
                    . 'rougit, gonfle ou saigne lors du brossage. Dans ce cas, il ne faut '
                    . 'surtout pas éviter les zones sensibles qui saignent, mais au contraire '
                    . 'augmenter la fréquence et la durée du brossage à leur niveau.<br />'
                    . 'Dans le cas d’un brossage insuffisant, sur une période plus longue, un '
                    . 'dépôt de plaque dentaire autour des bagues et à la limite de la gencive '
                    . 'peut entraîner un début de déminéralisation de l’émail se traduisant '
                    . 'par des marques blanchâtres. Dans un premier temps, ces marques sont '
                    . 'réversibles à l’aide de traitements fluorés, mais irréversibles à un '
                    . 'stade plus avancé, le stade suivant étant une carie. Dans ces deux '
                    . 'derniers cas, il peut être indiqué de retirer l’appareil même si le '
                    . 'traitement n’est pas terminé.</p>'
                    . '<p>C’est pourquoi, il est impératif de bien se brosser les dents après '
                    . 'chaque repas, notamment entre les gencives et l’appareil, car les '
                    . 'bactéries s’y développent préférentiellement. Une brosse à dents '
                    . 'électrique n’est pas incompatible avec un traitement d’orthodontie, '
                    . 'mais une brosse à dent classique peut suffire.<br />'
                    . 'La brosse utilisée doit être à poils souples, avec une tête de petite '
                    . 'dimension pour accéder à tous les recoins. En complément, il est '
                    . 'possible d’utiliser des brossettes interdentaires, des hydropulseurs et '
                    . 'des fils dentaires pour atteindre les zones situées entre les dents et '
                    . 'derrière les fils.</p>',
                // La maquette ne donne PAS de destination à ce bouton : le
                // tutoriel n'existe pas encore. L'ancre renvoie en haut de page
                // plutôt que de laisser un lien mort, et c'est au client de la
                // remplacer.
                'lien' => [
                    'title' => 'voir le tutoriel',
                    'url' => '#',
                    'target' => '',
                ],
            ],
        ],
    ],

    [
        'acf_fc_layout' => 'accordeon',
        'titre' => 'Foire aux questions',
        'chapo' => '',
        'groupes' => [
            [
                'etiquette' => 'chez l’enfant',
                'puce' => 'orange',
                'questions' => [
                    $question('À quel âge consulter un orthodontiste pour enfants ?', $lorem, true),
                    $question('Quelles technologies utilisez-vous pour traiter les enfants ?'),
                    $question('Les traitements orthodontiques précoces sont-ils douloureux ?'),
                    $question('Quels signes doivent alerter les parents ?'),
                    $question('Les aligneurs transparents sont-ils adaptés aux jeunes enfants ?'),
                ],
            ],
            [
                'etiquette' => 'chez l’adolescent',
                'puce' => 'orange',
                'questions' => [
                    $question('À quel âge un adolescent doit-il consulter un orthodondiste ?', $lorem, true),
                    $question('Quels sont les traitements les plus adaptés aux adolescents ?'),
                    $question('Combien de temps dure un traitement orthodontique chez l’adolescent ?'),
                    $question('Les traitements sont-ils remboursés ?'),
                    $question('Un traitement orthodontique fait-il mal ?'),
                    $question('Mon adolescent devra-t-il changer ses habitudes alimentaires ?'),
                    $question('Pourquoi traiter l’adolescent maintenant et ne pas attendre ?'),
                ],
            ],
        ],
    ],
], $page_id);

$sections = get_field('field_lcds_conseils_sections', $page_id);
$sections = is_array($sections) ? $sections : [];
$groupes = 0;
$questions = 0;

foreach ($sections as $section) {
    foreach ((is_array($section['groupes'] ?? null) ? $section['groupes'] : []) as $groupe) {
        $groupes++;
        $questions += count(is_array($groupe['questions'] ?? null) ? $groupe['questions'] : []);
    }
}

WP_CLI::log(sprintf(
    '==> [init] Page « Conseils » amorcée (ID %d, %d sections, %d groupes, %d questions).',
    $page_id,
    count($sections),
    $groupes,
    $questions,
));
