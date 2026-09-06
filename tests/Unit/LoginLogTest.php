<?php

/**
 * The login journal's bounds.
 *
 * A login is personal data, so the journal is bounded in BOTH directions — age
 * and count — and what falls out is deleted, not hidden. Both methods take the
 * instant as an argument rather than reading the clock, which is the only
 * reason retention can be asserted here at all.
 */

require_once dirname(__DIR__, 2) . '/website/app/themes/lcds/inc/enums/LcdsLoginLog.php';

const NOW = 1_800_000_000;

function entry(int $ageInDays, int $id = 1): array
{
    return ['id' => $id, 'login' => 'compte' . $id, 'time' => NOW - ($ageInDays * 86400)];
}

it('puts the newest login first', function () {
    $journal = LcdsLoginLog::record([entry(1)], 7, 'lucie', NOW);

    expect($journal)->toHaveCount(2);
    expect($journal[0]['id'])->toBe(7);
    expect($journal[0]['login'])->toBe('lucie');
    expect($journal[0]['time'])->toBe(NOW);
});

it('deletes what is older than the retention', function () {
    $journal = LcdsLoginLog::prune([
        entry(LcdsLoginLog::MAX_AGE_DAYS - 1, 1),
        entry(LcdsLoginLog::MAX_AGE_DAYS + 1, 2),
    ], NOW);

    expect(array_column($journal, 'id'))->toBe([1]);
});

it('caps the journal, keeping the newest', function () {
    $entrees = [];

    for ($jour = LcdsLoginLog::MAX_ENTRIES + 10; $jour >= 1; $jour--) {
        $entrees[] = entry(0, $jour);
    }

    $journal = LcdsLoginLog::prune($entrees, NOW);

    expect($journal)->toHaveCount(LcdsLoginLog::MAX_ENTRIES);
});

// Sans tri, une entrée arrivée en retard ferait tomber la plus récente au
// moment de couper.
it('sorts before it cuts', function () {
    $journal = LcdsLoginLog::prune([entry(10, 1), entry(0, 2), entry(5, 3)], NOW);

    expect(array_column($journal, 'id'))->toBe([2, 3, 1]);
});

it('drops an entry it cannot read', function (mixed $malformee) {
    expect(LcdsLoginLog::prune([$malformee, entry(1)], NOW))->toHaveCount(1);
})->with([
    'pas un tableau' => ['bonjour'],
    'sans instant' => [['id' => 1, 'login' => 'a']],
    'instant illisible' => [['id' => 1, 'login' => 'a', 'time' => 'hier']],
    'identifiant illisible' => [['id' => [], 'login' => 'a', 'time' => NOW]],
]);

it('reads the last login of each account off the sorted journal', function () {
    $journal = LcdsLoginLog::prune([entry(3, 1), entry(1, 2), entry(9, 1), entry(4, 2)], NOW);

    expect(LcdsLoginLog::latestPerUser($journal))->toBe([
        2 => NOW - (1 * 86400),
        1 => NOW - (3 * 86400),
    ]);
});

it('has no last login to report on an empty journal', function () {
    expect(LcdsLoginLog::latestPerUser([]))->toBe([]);
});
