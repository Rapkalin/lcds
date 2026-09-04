<?php

/**
 * The practical-info icons' contract.
 *
 * `LcdsInfoIcon` is the only place that maps a contributor's choice to a file:
 * `template()` builds `components/icon-<value>`. A value that no longer matches
 * a component renders an entry with no glyph and no error, so the mapping is
 * asserted against the FILES ON DISK rather than against a hardcoded list.
 *
 * `label()` and `template()` are `match` expressions with no default arm, so
 * iterating over every case turns a forgotten icon into a CI failure.
 */

require_once dirname(__DIR__, 2) . '/website/app/themes/lcds/inc/enums/LcdsInfoIcon.php';

const LCDS_THEME = __DIR__ . '/../../website/app/themes/lcds/';

it('gives every icon a label', function (LcdsInfoIcon $icon) {
    expect($icon->label())->toBeString()->not->toBe('');
})->with(LcdsInfoIcon::cases());

it('points every icon at a component that exists', function (LcdsInfoIcon $icon) {
    expect(LCDS_THEME . $icon->template() . '.php')->toBeFile();
})->with(LcdsInfoIcon::cases());

it('keeps every value usable inside a file name', function (LcdsInfoIcon $icon) {
    expect($icon->value)->toMatch('/^[a-z0-9-]{1,32}$/');
})->with(LcdsInfoIcon::cases());

it('offers every case to the contributor', function () {
    $choices = LcdsInfoIcon::choices();

    expect($choices)->toHaveCount(count(LcdsInfoIcon::cases()));

    foreach (LcdsInfoIcon::cases() as $icon) {
        expect($choices)->toHaveKey($icon->value, $icon->label());
    }
});

it('falls back rather than render an entry with no glyph', function (mixed $value) {
    expect(LcdsInfoIcon::fromValue($value, LcdsInfoIcon::Info))->toBe(LcdsInfoIcon::Info);
})->with([[''], ['adresse'], ['Pin'], [null], [0], [[]], ['pin ']]);

it('resolves a stored value to its case', function () {
    expect(LcdsInfoIcon::fromValue('clock', LcdsInfoIcon::Info))->toBe(LcdsInfoIcon::Clock);
});
