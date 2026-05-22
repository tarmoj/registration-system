<?php
/**
 * One-time import: registrations.csv → registrations table.
 * Run from the project root: php import_registrations.php
 *
 * CSV columns (row 1 is header, ignored):
 *   Timestamp, Nimi, Email, Instrument/Hääl,
 *   Kirjeldage oma senist muusikalist kogemust/tausta,
 *   Kas sooviksid osaleda:,   ← skipped
 *   Mida soovid lisada?
 *
 * Mapped to: name, email, instrument, experience, comments
 * Skips rows with duplicate emails (already in the table).
 */

require_once __DIR__ . '/db.php';

$file = __DIR__ . '/registrations.csv';
if (!file_exists($file)) {
    echo "File not found: $file\n";
    exit(1);
}

$pdo = db();

$checkStmt = $pdo->prepare('SELECT id FROM registrations WHERE email = ?');
$insertStmt = $pdo->prepare(
    'INSERT INTO registrations (created_at, name, email, instrument, experience, comments)
     VALUES (?, ?, ?, ?, ?, ?)'
);

$handle = fopen($file, 'r');
// Skip header row
fgetcsv($handle);

$inserted = 0;
$skipped  = 0;
$row      = 0;

while (($cols = fgetcsv($handle)) !== false) {
    $row++;
    if (count($cols) < 5) {
        echo "Row $row: too few columns, skipping.\n";
        $skipped++;
        continue;
    }

    // Parse Google Forms timestamp: M/D/YYYY H:MM:SS → MySQL datetime
    $rawTs    = trim($cols[0]);
    $dt       = DateTime::createFromFormat('n/j/Y G:i:s', $rawTs);
    $createdAt = $dt ? $dt->format('Y-m-d H:i:s') : null;

    $name       = trim($cols[1]);
    $email      = trim($cols[2]);
    $instrument = trim($cols[3]);
    $experience = trim($cols[4]);
    // cols[5] = ensemble choice — skipped
    $comments   = isset($cols[6]) ? trim($cols[6]) : '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Row $row: invalid email '$email', skipping.\n";
        $skipped++;
        continue;
    }

    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        echo "Row $row: $email already exists, skipping.\n";
        $skipped++;
        continue;
    }

    $insertStmt->execute([$createdAt, $name, $email, $instrument, $experience, $comments]);
    echo "Row $row: inserted $name <$email>\n";
    $inserted++;
}

fclose($handle);

echo "\nDone. Inserted: $inserted, Skipped: $skipped\n";
