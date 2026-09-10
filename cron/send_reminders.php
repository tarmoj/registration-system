<?php
/**
 * Weekly reminder script — run every Friday at 15:00 via cron.
 * Crontab: 0 15 * * 5 php /path/to/registration-system/cron/send_reminders.php
 *
 * For each registrant in an ensemble, if they have not confirmed attendance
 * for the coming session of that ensemble, send a bilingual reminder email
 * with clickable JAH/EI quick-vote links.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers/email.php';

$etLang    = require __DIR__ . '/../lang/et.php';
$enLang    = require __DIR__ . '/../lang/en.php';
$schedules = unserialize(ENSEMBLE_SCHEDULES);

/**
 * Return the next scheduled session date (>= today) from an ensemble's date list, or null.
 */
function nextSessionDate(array $dates): ?string {
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    foreach ($dates as $date) {
        if ($date >= $today) return $date;
    }
    return null;
}

$pdo = db();

// All registrations with their ensemble memberships
$registrations = $pdo->query(
    'SELECT r.id, r.name, r.email, re.ensemble_id
     FROM registrations r
     JOIN registration_ensembles re ON re.registration_id = r.id
     ORDER BY r.id, re.ensemble_id'
)->fetchAll();

// Index ensembles by id
$ensembles = $pdo->query('SELECT id, name, name_est FROM ensembles')->fetchAll();
$ensMap    = [];
foreach ($ensembles as $e) {
    $ensMap[(int)$e['id']] = $e;
}

// Group by registration
$byReg = [];
foreach ($registrations as $row) {
    $byReg[$row['id']][] = $row;
}

foreach ($byReg as $regId => $rows) {
    $email = $rows[0]['email'];
    $name  = $rows[0]['name'];

    $remindBlocks = [];  // collect reminder HTML blocks, one per ensemble
    $token        = hash_hmac('sha256', $email, SECRET_KEY);
    $unsubUrl     = BASE_URL . '/unsubscribe.php?email=' . urlencode($email) . '&token=' . $token;

    foreach ($rows as $row) {
        $eid = (int)$row['ensemble_id'];
        if (!isset($schedules[$eid])) continue;

        $nextDate = nextSessionDate($schedules[$eid]['dates']);

        // Only remind if there is a scheduled session within the coming weekend
        // (skip silently during gap weeks with no session)
        if ($nextDate === null) continue;
        $daysUntil = (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($nextDate))->days;
        if ($daysUntil > 6) continue;

        // Check if attendance already set for this registration / ensemble / date
        $stmt = $pdo->prepare(
            'SELECT attendance FROM attendance
             WHERE registration_id = ? AND ensemble_id = ? AND date = ?'
        );
        $stmt->execute([$regId, $eid, $nextDate]);
        $att = $stmt->fetchColumn();

        // Skip if already answered
        if ($att === 'yes' || $att === 'no') continue;

        // Build quick-vote URLs
        $baseUrl = BASE_URL . '/attendance.php?email=' . urlencode($email)
                   . '&token=' . $token
                   . '&ensemble_id=' . $eid
                   . '&date=' . $nextDate;
        $yesUrl  = $baseUrl . '&vote=yes';
        $noUrl   = $baseUrl . '&vote=no';

        $ensNameEt = $ensMap[$eid]['name_est'] ?? '';
        $ensNameEn = $ensMap[$eid]['name']     ?? '';

        $blockEt = sprintf($etLang['reminder_body'], $ensNameEt, $nextDate, $yesUrl, $noUrl);
        $blockEn = sprintf($enLang['reminder_body'], $ensNameEn, $nextDate, $yesUrl, $noUrl);

        $remindBlocks[] = "<p><strong>&#8212; Eesti &#8212;</strong></p>\n$blockEt\n<p><strong>&#8212; English &#8212;</strong></p>\n$blockEn";
    }

    if (empty($remindBlocks)) continue;

    $subject  = $etLang['reminder_subject'] . ' / ' . $enLang['reminder_subject'];
    $nameHtml = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $unsubEt  = sprintf($etLang['reminder_unsubscribe'], htmlspecialchars($unsubUrl, ENT_QUOTES, 'UTF-8'));
    $unsubEn  = sprintf($enLang['reminder_unsubscribe'], htmlspecialchars($unsubUrl, ENT_QUOTES, 'UTF-8'));
    $body     = '<!DOCTYPE html><html lang="et"><head><meta charset="UTF-8"></head>'
              . '<body style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:1rem;line-height:1.6">'
              . "<p>Tere $nameHtml,</p>"
              . '<hr>'
              . implode("\n<hr>\n", $remindBlocks)
              . '<hr style="margin-top:2rem">'
              . '<p style="font-size:0.85em;color:#555">' . $unsubEt . '</p>'
              . '<p style="font-size:0.85em;color:#555">' . $unsubEn . '</p>'
              . '</body></html>';

    sendEmail($email, $subject, $body, true);
    echo "Reminder sent to $email\n";
}

echo "Done.\n";
