<?php

/**
 * The account screen a contributor is left with.
 *
 * The load-bearing assertion is the last one: EVERY row the core renders must be
 * named, as kept, hidden, or handled elsewhere. A WordPress update that adds a
 * field would otherwise surface it silently — which is exactly what this screen
 * was trimmed to avoid.
 *
 * The hiding itself is styling, not a barrier, and is not tested as one: these
 * are the account\'s own fields, which a contributor may change. What guards
 * anything is the role\'s capabilities and the screen guard.
 */

require_once dirname(__DIR__, 2) . '/website/app/themes/lcds/inc/enums/LcdsProfileField.php';

it('keeps exactly the fields a contributor was left', function () {
    expect(LcdsProfileField::keptRows())->toBe([
        'user-admin-color-wrap',
        'user-admin-bar-front-wrap',
        'user-user-login-wrap',
        'user-first-name-wrap',
        'user-last-name-wrap',
        'user-nickname-wrap',
        'user-email-wrap',
    ]);
});

it('never hides a field it keeps', function () {
    expect(array_intersect(LcdsProfileField::hiddenRows(), LcdsProfileField::keptRows()))->toBe([]);
});

it('names every row the core renders', function () {
    $source = file_get_contents(dirname(__DIR__, 2) . '/website/wordpress-core/wp-admin/user-edit.php');

    expect($source)->toBeString();

    preg_match_all('/class="([a-z-]*user-[a-z-]+-wrap)"/', (string) $source, $matches);

    $rendered = array_unique($matches[1]);

    expect($rendered)->not->toBeEmpty();

    $named = array_merge(
        LcdsProfileField::keptRows(),
        LcdsProfileField::hiddenRows(),
        LcdsProfileField::rowsHandledElsewhere(),
    );

    expect(array_values(array_diff($rendered, $named)))->toBe([]);
});
