<?php

/**
 * The site version's contract.
 *
 * `lcds_site_version()` reads `composer.json` at the repository root, which is
 * the single place the version is declared. Two things make it worth a suite:
 * the path is computed from ABSPATH and would break silently on a layout
 * change, and the footer must degrade rather than fail when the file is absent
 * — which is what happens on any environment where the deploy artefact forgot
 * to carry it.
 */

require_once dirname(__DIR__, 2) . '/website/app/themes/lcds/inc/template.php';

it('reads the version declared in composer.json', function () {
    $manifest = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
        true,
    );

    expect(lcds_site_version())->toBe($manifest['version']);
});

it('returns a version usable as a semantic version', function () {
    expect(lcds_site_version())->toMatch('/^\d+\.\d+\.\d+/');
});

it('resolves composer.json from ABSPATH, two levels up', function () {
    // Le chemin est calculé, pas écrit : un déplacement du docroot le casserait
    // sans erreur, et le pied de page perdrait sa version en silence.
    expect(dirname(ABSPATH, 2) . '/composer.json')->toBeFile();
});
