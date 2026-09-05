<?php

/**
 * The homepage layout catalogue's contract.
 *
 * `front-page.php` dispatches on the layout NAME: `get_row_layout()` becomes
 * `layouts/<name>.php`. Two failure modes follow, and both are silent:
 *
 *  - a layout declared in the field group with no template renders nothing at
 *    all, and the contributor sees an empty section with no error;
 *  - a template with no declared layout is dead code nobody can reach.
 *
 * Both are asserted against the FILES ON DISK and the JSON in the repository —
 * no hardcoded list, so adding a section can only pass by being complete.
 */

const LCDS_ROOT = __DIR__ . '/../../';
const LCDS_GROUP = LCDS_ROOT . 'website/app/themes/lcds/acf-json/group_lcds_homepage.json';

/**
 * @return array<int, string>
 */
function lcds_declared_layouts(): array
{
    $group = json_decode((string) file_get_contents(LCDS_GROUP), true);
    $names = [];

    foreach ($group['fields'] as $field) {
        if (($field['name'] ?? '') !== 'sections') {
            continue;
        }

        foreach ((array) $field['layouts'] as $layout) {
            $names[] = (string) $layout['name'];
        }
    }

    sort($names);

    return $names;
}

/**
 * @return array<int, string>
 */
function lcds_layout_templates(): array
{
    $names = array_map(
        static fn(string $path): string => basename($path, '.php'),
        (array) glob(LCDS_ROOT . 'website/app/themes/lcds/layouts/*.php'),
    );

    sort($names);

    return $names;
}

it('declares at least one layout', function () {
    // Sans ce garde-fou, deux catalogues vides se compareraient comme égaux.
    expect(lcds_declared_layouts())->not->toBeEmpty();
});

it('gives every declared layout a template', function () {
    expect(lcds_declared_layouts())->toBe(lcds_layout_templates());
});

it('never leaves a template unreachable', function (string $template) {
    expect(lcds_declared_layouts())->toContain($template);
})->with(lcds_layout_templates());

it('keeps every layout name usable inside a template path', function (string $layout) {
    expect($layout)->toMatch('/^[a-z0-9-]{1,32}$/');
})->with(lcds_declared_layouts());

it('locates the field group on the front page, not on a page template', function () {
    // front-page.php n'est pas un gabarit sélectionnable : la règle doit suivre
    // le réglage « Lecture », pas un choix du contributeur.
    $group = json_decode((string) file_get_contents(LCDS_GROUP), true);

    expect($group['location'])->toBe([[['param' => 'page_type', 'operator' => '==', 'value' => 'front_page']]]);
});

it('hides the block editor content so there is a single contribution surface', function () {
    $group = json_decode((string) file_get_contents(LCDS_GROUP), true);

    expect($group['hide_on_screen'])->toContain('the_content');
});
