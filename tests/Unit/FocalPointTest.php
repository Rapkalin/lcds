<?php

/**
 * The focal point's contract.
 *
 * The revealed footer visual is cropped by `object-fit: cover`: only a band of
 * the photo shows. WHICH band is content, not layout — a mouth at the bottom of
 * the frame and a face at the top do not crop the same way — so a contributor
 * picks it.
 *
 * The enum value is a CSS class suffix (`is-focus-<value>`). A value that is not
 * usable as one silently produces an unstyled image, cropped from its centre
 * with no error anywhere. The `object-position` itself lives in the stylesheet,
 * which alone knows the corner radius it has to compensate for; that the three
 * rules resolve to three distinct positions is asserted by the front campaign.
 */

require_once dirname(__DIR__, 2) . '/website/app/themes/lcds/inc/enums/LcdsFocalPoint.php';

it('gives every focal point a label', function (LcdsFocalPoint $point) {
    expect($point->label())->toBeString()->not->toBe('');
})->with(LcdsFocalPoint::cases());

it('keeps every value usable as a CSS class suffix', function (LcdsFocalPoint $point) {
    expect($point->value)->toMatch('/^[a-z]{1,16}$/');
})->with(LcdsFocalPoint::cases());

it('keeps every value distinct, since each drives its own CSS rule', function () {
    $values = array_column(LcdsFocalPoint::cases(), 'value');

    expect(array_unique($values))->toHaveCount(count($values));
});

it('offers every case to the contributor', function () {
    $choices = LcdsFocalPoint::choices();

    expect($choices)->toHaveCount(count(LcdsFocalPoint::cases()));

    foreach (LcdsFocalPoint::cases() as $point) {
        expect($choices)->toHaveKey($point->value, $point->label());
    }
});

it('falls back to the centre rather than crop at random', function (mixed $value) {
    expect(LcdsFocalPoint::fromValue($value, LcdsFocalPoint::Center))->toBe(LcdsFocalPoint::Center);
})->with([[''], ['haut'], ['Top'], [null], [0], [[]], ['top ']]);

it('resolves a stored value to its case', function () {
    expect(LcdsFocalPoint::fromValue('bottom', LcdsFocalPoint::Center))->toBe(LcdsFocalPoint::Bottom);
});
