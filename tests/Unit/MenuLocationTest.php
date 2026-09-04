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

/**
 * The default items' contract. These live in the enum rather than in a
 * contributor's hands because header.php calls wp_nav_menu() with
 * `fallback_cb => false`: an empty menu renders NOTHING. Before the items were
 * seeded, a freshly deployed environment shipped a header with no navigation at
 * all — verified by emptying the menus and hitting the front.
 *
 * `items()` is a `match` with no default arm, so iterating over every case is
 * what turns a forgotten location into a CI failure rather than a location that
 * silently seeds nothing.
 */
it('decides explicitly what every location seeds', function (LcdsMenuLocation $location) {
    expect($location->items())->toBeArray();
})->with(LcdsMenuLocation::cases());

it('gives every seeded item a title and a destination', function (LcdsMenuLocation $location) {
    $items = $location->items();

    // Asserted before the loop, so a location that seeds nothing still carries
    // a real assertion instead of being reported as a risky test. It is also
    // the shape the seeding relies on: lcds_seed_menu_items() derives
    // menu-item-position from the array index.
    expect(array_is_list($items))->toBeTrue();

    foreach ($items as $item) {
        expect($item)->toHaveKeys(['title', 'url']);
        expect($item['title'])->toBeString()->not->toBe('');

        // An empty URL is accepted by wp_update_nav_menu_item() but renders an
        // <a> with no href — not a link: not focusable, unreachable by
        // keyboard. Verified against WordPress, not assumed.
        expect($item['url'])->toBeString()->not->toBe('');
    }
})->with(LcdsMenuLocation::cases());

it('seeds the two locations the header cannot render without', function () {
    // header.php has no fallback: these two empty means a header with neither
    // navigation nor call to action.
    expect(LcdsMenuLocation::Header->items())->not->toBeEmpty();
    expect(LcdsMenuLocation::HeaderCta->items())->not->toBeEmpty();
});

it('never repeats a title inside one location', function (LcdsMenuLocation $location) {
    $titles = array_column($location->items(), 'title');

    expect(array_unique($titles))->toHaveCount(count($titles));
})->with(LcdsMenuLocation::cases());
