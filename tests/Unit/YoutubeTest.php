<?php

/**
 * The hero's YouTube contract.
 *
 * A contributor pastes whatever link YouTube shows them; only an 11-character
 * identifier may come out, and the theme rebuilds the embed URL on the
 * cookie-less domain itself. Anything else must come out empty, so that the
 * hero falls back to its photo instead of embedding a foreign page.
 */

require_once dirname(__DIR__, 2) . '/website/app/themes/lcds/inc/youtube.php';
require_once dirname(__DIR__, 2) . '/website/app/themes/lcds/inc/enums/LcdsVideoSource.php';

it('reads the identifier from every link YouTube hands out', function (string $url) {
    expect(lcds_youtube_id($url))->toBe('dQw4w9WgXcQ');
})->with([
    'watch' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'watch with extra parameters' => 'https://www.youtube.com/watch?list=PL1&v=dQw4w9WgXcQ&t=42s',
    'mobile' => 'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
    'without www' => 'https://youtube.com/watch?v=dQw4w9WgXcQ',
    'short link' => 'https://youtu.be/dQw4w9WgXcQ?si=abc',
    'embed' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
    'cookie-less embed' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
    'shorts' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
    'live' => 'https://www.youtube.com/live/dQw4w9WgXcQ',
    'surrounding spaces' => '  https://youtu.be/dQw4w9WgXcQ  ',
    'upper-case host' => 'https://WWW.YouTube.com/watch?v=dQw4w9WgXcQ',
]);

it('rejects anything that does not designate a YouTube video', function (string $url) {
    expect(lcds_youtube_id($url))->toBe('');
})->with([
    'empty' => '',
    'bare identifier' => 'dQw4w9WgXcQ',
    'foreign host' => 'https://vimeo.com/watch?v=dQw4w9WgXcQ',
    'look-alike host' => 'https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ',
    'host as a suffix' => 'https://notyoutube.com/watch?v=dQw4w9WgXcQ',
    'script scheme' => 'javascript://youtube.com/watch?v=dQw4w9WgXcQ',
    'channel page' => 'https://www.youtube.com/@lcds',
    'watch without video' => 'https://www.youtube.com/watch?list=PL1',
    'identifier too short' => 'https://youtu.be/dQw4w9WgXc',
    'identifier too long' => 'https://youtu.be/dQw4w9WgXcQQ',
    'markup in the identifier' => 'https://www.youtube.com/watch?v=dQw4"><scri',
    'array injection' => 'https://www.youtube.com/watch?v[]=dQw4w9WgXcQ',
]);

it('keeps the field group conditions on the video sources the enum declares', function () {
    $group = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2) . '/website/app/themes/lcds/acf-json/group_lcds_homepage.json'),
        true,
    );
    $heroFields = $group['fields'][1]['layouts']['layout_lcds_hero']['sub_fields'];
    $conditions = [];

    foreach ($heroFields as $field) {
        foreach ($field['conditional_logic'] ?: [] as $rules) {
            foreach ($rules as $rule) {
                if ($rule['field'] === 'field_lcds_hero_video_source') {
                    $conditions[] = $rule['value'];
                }
            }
        }
    }

    expect($conditions)->toEqualCanonicalizing(array_column(LcdsVideoSource::cases(), 'value'));
});

it('falls back to the uploaded file for a missing or stale source', function (mixed $value) {
    expect(LcdsVideoSource::fromValue($value, LcdsVideoSource::File))->toBe(LcdsVideoSource::File);
})->with([null, '', 'vimeo', 42]);
