<?php
/**
 * One-time script: seed the ensembles table.
 * Run once from the command line: php seed_ensembles.php
 */

require_once __DIR__ . '/db.php';

$ensembles = [
    [1, 'Classical Music Ensemble',             'Klassikalise muusika ansambel'],
    [2, 'Improvisation Ensemble',               'Improvisatsiooniansambel'],
    [3, 'Classical Music Ensemble (beginners)', 'Klassikalise muusika ansambel (algajad)'],
];

$pdo = db();
$stmt = $pdo->prepare(
    'INSERT INTO ensembles (id, name, name_est) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE name = VALUES(name), name_est = VALUES(name_est)'
);

foreach ($ensembles as $row) {
    $stmt->execute($row);
    echo "Upserted ensemble id={$row[0]}: {$row[2]}\n";
}

echo "Done.\n";
