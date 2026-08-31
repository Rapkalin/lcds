<?php

/**
 * The WebP module's configuration contract. Both values are read by the
 * conversion filters and never validated at runtime: a typo here would silently
 * produce unreadable images or convert formats that must not be touched.
 */

require_once dirname(__DIR__, 2) . '/website/app/mu-plugins/webp/config.php';

it('keeps the encoding quality within the encoder range', function () {
    expect(LCDS_WEBP_QUALITY)->toBeInt()->toBeGreaterThan(0)->toBeLessThanOrEqual(100);
});

it('only ever converts raster image mime types', function () {
    expect(LCDS_WEBP_SOURCE_FORMATS)->not->toBeEmpty();

    foreach (LCDS_WEBP_SOURCE_FORMATS as $mime) {
        expect($mime)->toMatch('#^image/[a-z0-9.+-]+$#');
    }
});

it('never converts vector or animated formats', function () {
    // Documented decision (readme/images.md): SVG is vector and GIF is animated,
    // neither survives this pipeline. Listing one here would corrupt uploads.
    expect(LCDS_WEBP_SOURCE_FORMATS)
        ->not->toContain('image/svg+xml')
        ->not->toContain('image/gif');
});
