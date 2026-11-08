<?php

/**
 * Fiche de l'établissement, publiée dans les données structurées.
 *
 * @package lcds
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/enums/LcdsWeekDay.php';

const LCDS_TIME_PATTERN = '/^([01]\d|2[0-3]):[0-5]\d$/';

/**
 * Builds the schema.org properties that make the organisation a dentist.
 *
 * Without a street and a city, nothing is returned: a LocalBusiness without
 * an address is rejected by Google's rich results.
 *
 * @param array $fields Raw values of the « Fiche de l'établissement » group.
 */
function lcds_business_schema(array $fields): array
{
    $street = trim((string) ($fields['rue'] ?? ''));
    $city = trim((string) ($fields['ville'] ?? ''));

    if ($street === '' || $city === '') {
        return [];
    }

    $schema = [
        '@type' => ['Organization', 'Dentist'],
        'address' => array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $street,
            'postalCode' => trim((string) ($fields['code_postal'] ?? '')),
            'addressLocality' => $city,
            'addressCountry' => 'FR',
        ], static fn(string $value): bool => $value !== ''),
    ];

    $phone = trim((string) ($fields['telephone'] ?? ''));

    if ($phone !== '') {
        $schema['telephone'] = $phone;
    }

    $hours = lcds_business_opening_hours(is_array($fields['horaires'] ?? null) ? $fields['horaires'] : []);

    if ($hours !== []) {
        $schema['openingHoursSpecification'] = $hours;
    }

    return $schema;
}

/**
 * @param array $rows Rows of the « Horaires » repeater.
 */
function lcds_business_opening_hours(array $rows): array
{
    $specifications = [];

    foreach ($rows as $row) {
        $days = [];

        foreach ((is_array($row['jours'] ?? null) ? $row['jours'] : []) as $value) {
            $day = is_string($value) ? LcdsWeekDay::tryFrom($value) : null;

            if ($day !== null) {
                $days[] = $day->value;
            }
        }

        $opens = (string) ($row['ouverture'] ?? '');
        $closes = (string) ($row['fermeture'] ?? '');

        if ($days === [] || preg_match(LCDS_TIME_PATTERN, $opens) !== 1 || preg_match(LCDS_TIME_PATTERN, $closes) !== 1) {
            continue;
        }

        $specifications[] = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => $days,
            'opens' => $opens,
            'closes' => $closes,
        ];
    }

    return $specifications;
}
