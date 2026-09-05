<?php

/**
 * The contributor's admin perimeter.
 *
 * `LcdsAdminScreen::isAllowed()` is the load-bearing part of the restriction:
 * hiding a menu entry protects nothing, the URL stays typeable. It is written
 * as a PURE function precisely so it can be asserted here — the guard that
 * calls it needs a live admin request and cannot be.
 *
 * It is an ALLOW-LIST. A screen added by a future plugin is denied by default;
 * a deny-list would have let it through and nobody would have noticed. The
 * tests below therefore check the default answer as much as the listed ones.
 */

require_once dirname(__DIR__, 2) . '/website/app/themes/lcds/inc/enums/LcdsAdminScreen.php';

it('allows the screens a contributor works in', function (string $pagenow, array $query) {
    expect(LcdsAdminScreen::isAllowed($pagenow, $query))->toBeTrue();
})->with([
    ['index.php', []],
    ['profile.php', []],
    ['upload.php', []],
    ['media-new.php', []],
    ['nav-menus.php', []],
    ['customize.php', []],
    ['edit.php', ['post_type' => 'page']],
    ['post-new.php', ['post_type' => 'page']],
    ['post.php', ['post_type' => 'page']],
    ['options-general.php', ['page' => 'lcds-settings']],
]);

it('denies everything else', function (string $pagenow, array $query) {
    expect(LcdsAdminScreen::isAllowed($pagenow, $query))->toBeFalse();
})->with([
    ['themes.php', []],
    ['site-editor.php', []],
    ['font-library.php', []],
    ['theme-editor.php', []],
    ['plugins.php', []],
    ['plugin-editor.php', []],
    ['users.php', []],
    ['user-new.php', []],
    ['tools.php', []],
    ['export.php', []],
    ['import.php', []],
    ['site-health.php', []],
    ['edit-comments.php', []],
    ['options.php', []],
    ['options-permalink.php', []],
    ['options-reading.php', []],
    ['options-writing.php', []],
    ['admin.php', ['page' => 'wpseo_page_academy']],
    // Les articles ne sont pas dans le périmètre : seules les pages le sont.
    ['edit.php', []],
    ['post-new.php', []],
    ['post-new.php', ['post_type' => 'post']],
]);

it('never lets an unknown screen through by default', function (string $pagenow) {
    expect(LcdsAdminScreen::isAllowed($pagenow, []))->toBeFalse();
})->with([['une-extension-future.php'], ['admin.php'], [''], ['../wp-config.php']]);

it('opens Settings on our page only, never on the core ones', function () {
    expect(LcdsAdminScreen::isAllowed('options-general.php', ['page' => 'lcds-settings']))->toBeTrue();
    expect(LcdsAdminScreen::isAllowed('options-general.php', []))->toBeFalse();
    expect(LcdsAdminScreen::isAllowed('options-general.php', ['page' => 'autre']))->toBeFalse();
});

it('never lists a screen as both forbidden and reachable', function () {
    foreach (LcdsAdminScreen::forbiddenMenus() as $slug) {
        expect(LcdsAdminScreen::isAllowed($slug, []))->toBeFalse();
    }
});
