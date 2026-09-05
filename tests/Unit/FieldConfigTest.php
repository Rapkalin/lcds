<?php

/**
 * The field configuration's contract: it lives in FILES, and those files must
 * be identical on every machine.
 *
 * ACF can serve a field group from local JSON or from the database. The whole
 * point of `acf-json/` is that a deployment carries the configuration with the
 * code, so no database migration is ever needed to ship a new field — unlike
 * 2bdm, whose nine groups and 165 fields exist only as database rows.
 *
 * ACF stamps `local` and `local_file` on a group when it LOADS it, and writes
 * them back when the group is saved from the admin. `local_file` is an absolute
 * path: committed, the file differs from one machine to the next without a
 * single field having changed, which defeats reviewing the configuration in a
 * diff. `lcds_strip_local_json_paths()` removes them after every save; this
 * suite is what makes a regression visible.
 */

const LCDS_ACF_JSON = __DIR__ . '/../../website/app/themes/lcds/acf-json/';

/**
 * @return array<int, string>
 */
function lcds_field_group_files(): array
{
    return (array) glob(LCDS_ACF_JSON . '*.json');
}

it('ships at least one field group as a file', function () {
    expect(lcds_field_group_files())->not->toBeEmpty();
});

it('keeps every field group file valid JSON', function (string $file) {
    expect(json_decode((string) file_get_contents($file), true))->toBeArray();
})->with(lcds_field_group_files());

it('never commits a machine-specific path', function (string $file) {
    $raw = (string) file_get_contents($file);

    // Échappé par ACF en `\/var\/www\/…` : on cherche les deux écritures.
    expect($raw)->not->toContain('local_file');
    expect($raw)->not->toContain('/var/www');
    expect($raw)->not->toContain('\\/var\\/www');
    expect($raw)->not->toContain('/Users/');
})->with(lcds_field_group_files());

it('gives every field group a key and a location', function (string $file) {
    $group = json_decode((string) file_get_contents($file), true);

    expect($group['key'] ?? '')->toStartWith('group_');
    expect($group['location'] ?? [])->not->toBeEmpty();
})->with(lcds_field_group_files());
