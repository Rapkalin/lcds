<?php

/**
 * The advice section catalogue's contract.
 *
 * `template-conseils.php` turns the flexible content layout NAME into a
 * `LcdsAdviceSection` case, and the case decides what the right-hand column
 * renders. Two failure modes follow, and both are silent:
 *
 *  - a layout declared in the field group with no enum case is dropped by the
 *    allow-list, and the contributor sees nothing at all;
 *  - an enum case with no layout is dead code nobody can reach.
 *
 * Both are asserted against the JSON in the repository, no hardcoded list —
 * adding a form can only pass by being complete.
 */

require_once dirname(__DIR__, 2) . '/website/app/themes/lcds/inc/enums/LcdsAdviceSection.php';

const LCDS_ADVICE_GROUP = __DIR__ . '/../../website/app/themes/lcds/acf-json/group_lcds_conseils.json';

/**
 * @return array<int, string>
 */
function lcds_declared_advice_layouts(): array
{
    $group = json_decode((string) file_get_contents(LCDS_ADVICE_GROUP), true);
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
function lcds_advice_cases(): array
{
    $names = array_map(
        static fn(LcdsAdviceSection $section): string => $section->value,
        LcdsAdviceSection::cases(),
    );

    sort($names);

    return $names;
}

it('declares at least one form', function () {
    // Sans ce garde-fou, deux catalogues vides se compareraient comme égaux.
    expect(lcds_advice_cases())->not->toBeEmpty();
});

it('gives every declared layout an enum case', function () {
    expect(lcds_declared_advice_layouts())->toBe(lcds_advice_cases());
});

it('drops a layout nobody declares', function () {
    expect(LcdsAdviceSection::fromLayout('parcours'))->toBeNull();
    expect(LcdsAdviceSection::fromLayout(null))->toBeNull();
    expect(LcdsAdviceSection::fromLayout(['accordeon']))->toBeNull();
});

it('separates accordion groups with a rule, and description groups without', function () {
    // Relevé sur CONSEILS/LCDS_conseils.pdf : un SEUL filet dans toute la page,
    // entre les deux groupes de la foire aux questions. Les trois groupes de
    // « coûts et prise en charge » n'en ont aucun.
    expect(LcdsAdviceSection::Accordion->hasGroupRule())->toBeTrue();
    expect(LcdsAdviceSection::Description->hasGroupRule())->toBeFalse();
});

it('locates the field group on the page template, not on a slug', function () {
    $group = json_decode((string) file_get_contents(LCDS_ADVICE_GROUP), true);

    expect($group['location'])->toBe([[[
        'param' => 'page_template',
        'operator' => '==',
        'value' => 'template-conseils.php',
    ]]]);
});

it('hides the block editor content so there is a single contribution surface', function () {
    $group = json_decode((string) file_get_contents(LCDS_ADVICE_GROUP), true);

    expect($group['hide_on_screen'])->toContain('the_content');
});
