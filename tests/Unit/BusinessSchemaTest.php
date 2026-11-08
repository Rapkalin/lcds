<?php

/**
 * The dentist schema's contract: what reaches search engines is only what a
 * rich result accepts. A LocalBusiness without an address is rejected, and a
 * malformed opening hour invalidates the whole specification.
 */

require_once dirname(__DIR__, 2) . '/website/app/themes/lcds/inc/business.php';

const LCDS_FULL_ADDRESS = ['rue' => '2 place Saint-Maurice', 'code_postal' => '38200', 'ville' => 'Vienne'];

it('publishes nothing without a street and a city', function (array $fields) {
    expect(lcds_business_schema($fields))->toBe([]);
})->with([
    'empty' => [[]],
    'no street' => [['ville' => 'Vienne']],
    'no city' => [['rue' => '2 place Saint-Maurice']],
    'blank values' => [['rue' => '  ', 'ville' => ' ']],
]);

it('turns the organisation into a dentist with its postal address', function () {
    expect(lcds_business_schema(LCDS_FULL_ADDRESS))->toBe([
        '@type' => ['Organization', 'Dentist'],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => '2 place Saint-Maurice',
            'postalCode' => '38200',
            'addressLocality' => 'Vienne',
            'addressCountry' => 'FR',
        ],
    ]);
});

it('adds the phone only when it is filled in', function () {
    expect(lcds_business_schema([...LCDS_FULL_ADDRESS, 'telephone' => ' 04 74 00 00 00 ']))
        ->toHaveKey('telephone', '04 74 00 00 00')
        ->and(lcds_business_schema([...LCDS_FULL_ADDRESS, 'telephone' => '']))
        ->not->toHaveKey('telephone');
});

it('publishes each opening range with schema.org day names', function () {
    $schema = lcds_business_schema([...LCDS_FULL_ADDRESS, 'horaires' => [
        ['jours' => ['Monday', 'Tuesday'], 'ouverture' => '09:00', 'fermeture' => '12:30'],
        ['jours' => ['Monday'], 'ouverture' => '14:00', 'fermeture' => '18:00'],
    ]]);

    expect($schema['openingHoursSpecification'])->toBe([
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday'], 'opens' => '09:00', 'closes' => '12:30'],
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday'], 'opens' => '14:00', 'closes' => '18:00'],
    ]);
});

it('drops a range that a rich result would reject', function (array $row) {
    expect(lcds_business_schema([...LCDS_FULL_ADDRESS, 'horaires' => [$row]]))
        ->not->toHaveKey('openingHoursSpecification');
})->with([
    'no day' => [['jours' => [], 'ouverture' => '09:00', 'fermeture' => '12:00']],
    'unknown day' => [['jours' => ['Lundi'], 'ouverture' => '09:00', 'fermeture' => '12:00']],
    'missing time' => [['jours' => ['Monday'], 'ouverture' => '', 'fermeture' => '12:00']],
    'malformed time' => [['jours' => ['Monday'], 'ouverture' => '9h', 'fermeture' => '12:00']],
    'out of range' => [['jours' => ['Monday'], 'ouverture' => '25:00', 'fermeture' => '12:00']],
    'injected markup' => [['jours' => ['Monday'], 'ouverture' => '<script>', 'fermeture' => '12:00']],
]);

it('offers every day to the contributor in French', function () {
    expect(LcdsWeekDay::choices())->toBe([
        'Monday' => 'Lundi',
        'Tuesday' => 'Mardi',
        'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi',
        'Friday' => 'Vendredi',
        'Saturday' => 'Samedi',
        'Sunday' => 'Dimanche',
    ]);
});
