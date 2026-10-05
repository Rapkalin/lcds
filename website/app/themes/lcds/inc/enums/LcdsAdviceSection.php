<?php

/**
 * Les formes de section de la page « Conseils » — source unique.
 *
 * Les DEUX PREMIÈRES partagent la même carcasse : un titre collant, puis des
 * groupes de deux colonnes, étiquette à gauche et contenu à droite. Ce qui
 * change, c'est ce que porte la colonne de droite, et c'est tout :
 *
 *   accordeon    des entrées dépliables, et un filet entre deux groupes ;
 *   description  un titre, du texte, un bouton d'action, sans filet.
 *
 * La troisième n'a pas cette carcasse et c'est assumé — l'enum est l'ALLOW-LIST
 * des sections de la page, pas la description d'un composant :
 *
 *   galerie      le rail de visuels de l'accueil, sans son texte.
 *
 * La VALEUR est le nom du layout de contenu flexible tel qu'il est enregistré
 * en base. C'est donc elle qui sert d'allow-list : `tryFrom()` sur le nom rendu
 * par `get_row_layout()` écarte tout ce qui n'est pas prévu, plutôt que de le
 * laisser atteindre le composant.
 *
 * Ajouter une forme = ajouter un cas, son layout dans
 * `acf-json/group_lcds_conseils.json` sous le MÊME nom, et son bras dans le
 * composant. `tests/Unit/AdviceSectionTest.php` fait échouer la CI si l'enum et
 * le JSON divergent : une forme déclarée d'un seul côté est muette, sans erreur.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

enum LcdsAdviceSection: string
{
    case Accordion = 'accordeon';
    case Description = 'description';
    case Gallery = 'galerie';

    /**
     * Les groupes de cette forme sont-ils séparés par un filet ?
     *
     * Relevé sur `CONSEILS/LCDS_conseils.pdf` : un SEUL filet dans toute la
     * page, entre les deux groupes de la foire aux questions. Les trois
     * groupes de « coûts et prise en charge », eux, ne sont séparés que par le
     * même écart de 80px, sans trait.
     */
    public function hasGroupRule(): bool
    {
        return match ($this) {
            self::Accordion => true,
            // La galerie n'a pas de groupes et ne passe pas par `block-advice` :
            // la valeur n'est jamais lue. Le bras existe parce qu'un `match`
            // doit être exhaustif, pas parce qu'il décrit quelque chose.
            self::Description, self::Gallery => false,
        };
    }

    /**
     * Forme correspondant à un nom de layout, ou `null` si personne ne la
     * connaît.
     *
     * Rendre `null` et non un repli : un layout inconnu vient d'une
     * configuration ACF qui a divergé du code, et rendre la mauvaise section
     * serait pire que n'en rendre aucune.
     */
    public static function fromLayout(mixed $layout): ?self
    {
        return is_string($layout) ? self::tryFrom($layout) : null;
    }
}
