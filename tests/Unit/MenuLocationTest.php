<?php

/**
 * The menu locations' contract. `LcdsMenuLocation` is the single source of
 * truth: `register_nav_menus()` and the automatic seeding both derive from it,
 * so a case that is malformed here breaks the admin, not this file.
 *
 * `label()` is a `match` with no default arm — a case added without a label
 * raises \UnhandledMatchError. This suite turns that guard into a CI failure
 * instead of a broken admin screen.
 */

require_once dirname(__DIR__, 2) . '/website/app/themes/lcds/inc/enums/LcdsMenuLocation.php';

it('gives every menu location a label', function (LcdsMenuLocation $location) {
    expect($location->label())->toBeString()->not->toBe('');
})->with(LcdsMenuLocation::cases());

it('keeps every location value usable as a theme_location slug', function (LcdsMenuLocation $location) {
    expect($location->value)->toMatch('/^[a-z0-9-]{1,64}$/');
})->with(LcdsMenuLocation::cases());

it('exposes every case in the registry passed to register_nav_menus', function () {
    $registry = LcdsMenuLocation::registry();

    expect($registry)->toHaveCount(count(LcdsMenuLocation::cases()));

    foreach (LcdsMenuLocation::cases() as $location) {
        expect($registry)->toHaveKey($location->value, $location->label());
    }
});

it('never repeats a label across locations', function () {
    $labels = array_map(
        static fn(LcdsMenuLocation $location): string => $location->label(),
        LcdsMenuLocation::cases(),
    );

    // lcds_seed_menu_object() resolves menus BY NAME: two locations sharing a
    // label would silently be assigned the very same menu at seeding time.
    expect(array_unique($labels))->toHaveCount(count($labels));
});
