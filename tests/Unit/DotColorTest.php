<?php

/**
 * The dot colours' contract. What makes `LcdsDotColor` worth a suite is that
 * its labels and its values deliberately DISAGREE: the client calls the two
 * colours « Vert » and « Rouge », while the design system and the stylesheet
 * call them `turquoise` and `orange`.
 *
 * That divergence is the whole point of the enum, and also the thing most
 * likely to be "fixed" by mistake. Renaming a value to match its label would
 * orphan `.tag--turquoise` / `.tag--orange` in components/tag.scss and drop the
 * dot colour from every tag already stored in the database — silently, since a
 * missing CSS class renders a transparent circle rather than an error.
 */

require_once dirname(__DIR__, 2) . '/website/app/themes/lcds/inc/enums/LcdsDotColor.php';

it('gives every colour a label', function (LcdsDotColor $color) {
    expect($color->label())->toBeString()->not->toBe('');
})->with(LcdsDotColor::cases());

it('keeps every value usable as a CSS class suffix', function (LcdsDotColor $color) {
    expect($color->value)->toMatch('/^[a-z0-9-]{1,32}$/');
})->with(LcdsDotColor::cases());

it('keeps the stored values the ones the stylesheet knows', function () {
    // components/tag.scss carries `.tag--turquoise` and `.tag--orange`, and the
    // database already holds these strings. They are not free to change.
    expect(array_column(LcdsDotColor::cases(), 'value'))
        ->toBe(['turquoise', 'orange']);
});

it('offers every case to the contributor, under the client\'s own names', function () {
    expect(LcdsDotColor::choices())->toBe([
        'turquoise' => 'Vert',
        'orange' => 'Rouge',
    ]);
});

it('falls back rather than break the render on an unusable value', function (mixed $value) {
    expect(LcdsDotColor::fromValue($value, LcdsDotColor::Turquoise))
        ->toBe(LcdsDotColor::Turquoise);
    // Each dataset is wrapped: Pest SPREADS a plain array over the closure's
    // arguments, so the bare `[]` case arrived as zero arguments.
})->with([[''], ['vert'], ['rouge'], ['Turquoise'], [null], [0], [[]], ['turquoise ']]);

it('resolves a stored value to its case', function () {
    expect(LcdsDotColor::fromValue('orange', LcdsDotColor::Turquoise))
        ->toBe(LcdsDotColor::Orange);
});
