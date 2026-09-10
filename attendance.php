<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/email.php';

session_start();

// Language
if (isset($_GET['lang']) && in_array($_GET['lang'], ['et', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'et';
$t = require __DIR__ . "/lang/{$lang}.php";

$schedules = unserialize(ENSEMBLE_SCHEDULES);

// ── Helper: get upcoming session dates for an ensemble ─────────────────────
function ensembleDates(int $ensembleId): array {
    global $schedules;
    if (!isset($schedules[$ensembleId])) return [];
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    return array_values(array_filter(
        $schedules[$ensembleId]['dates'],
        fn(string $date) => $date >= $today
    ));
}

// ── Helper: validate HMAC token ────────────────────────────────────────────
function validToken(string $email, string $token): bool {
    return hash_equals(hash_hmac('sha256', $email, SECRET_KEY), $token);
}

// ── Handle quick-vote from reminder email ──────────────────────────────────
// ?email=…&token=…&ensemble_id=…&date=…&vote=yes|no
if (
    isset($_GET['vote'], $_GET['email'], $_GET['token'], $_GET['ensemble_id'], $_GET['date']) &&
    in_array($_GET['vote'], ['yes', 'no'], true) &&
    validToken($_GET['email'], $_GET['token'])
) {
    $vEmail      = $_GET['email'];
    $vEnsembleId = (int)$_GET['ensemble_id'];
    $vDate       = $_GET['date'];
    $vVote       = $_GET['vote'];

    // Validate date format
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $vDate)) {
        $pdo  = db();
        $reg  = $pdo->prepare('SELECT id FROM registrations WHERE email = ?');
        $reg->execute([$vEmail]);
        $regRow = $reg->fetch();

        if ($regRow) {
            $stmt = $pdo->prepare(
                'INSERT INTO attendance (registration_id, ensemble_id, date, attendance)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE attendance = VALUES(attendance)'
            );
            $stmt->execute([$regRow['id'], $vEnsembleId, $vDate, $vVote]);

            // Set session so the full page loads after vote
            $_SESSION['reg_email'] = $vEmail;
            $_SESSION['reg_id']    = (int)$regRow['id'];

            $ensName = $pdo->prepare('SELECT name, name_est FROM ensembles WHERE id = ?');
            $ensName->execute([$vEnsembleId]);
            $ensRow  = $ensName->fetch();
            $label   = $lang === 'et' ? ($ensRow['name_est'] ?? '') : ($ensRow['name'] ?? '');
            $key     = $vVote === 'yes' ? 'vote_confirmed_yes' : 'vote_confirmed_no';
            $voteMsg = sprintf($t[$key], $vDate, htmlspecialchars($label, ENT_QUOTES, 'UTF-8'));
        }
    }
}

// ── Login via URL token ────────────────────────────────────────────────────
if (!isset($_SESSION['reg_id']) && isset($_GET['email'], $_GET['token'])) {
    if (validToken($_GET['email'], $_GET['token'])) {
        $pdo  = db();
        $stmt = $pdo->prepare('SELECT id FROM registrations WHERE email = ?');
        $stmt->execute([$_GET['email']]);
        $row = $stmt->fetch();
        if ($row) {
            $_SESSION['reg_email'] = $_GET['email'];
            $_SESSION['reg_id']    = (int)$row['id'];
        }
    }
}

// ── Login via email form ───────────────────────────────────────────────────
$loginError = '';
if (!isset($_SESSION['reg_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_email'])) {
    $loginEmail = trim($_POST['login_email'] ?? '');
    if (filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
        if (strcasecmp($loginEmail, ADMIN_EMAIL) === 0) {
            $_SESSION['reg_email'] = $loginEmail;
            $_SESSION['reg_id']    = 0;
        } else {
            $pdo  = db();
            $stmt = $pdo->prepare('SELECT id FROM registrations WHERE email = ?');
            $stmt->execute([$loginEmail]);
            $row = $stmt->fetch();
            if ($row) {
                $_SESSION['reg_email'] = $loginEmail;
                $_SESSION['reg_id']    = (int)$row['id'];
            } else {
                $loginError = $t['login_error'];
            }
        }
    } else {
        $loginError = $t['login_error'];
    }
}

// ── AJAX: update attendance cell ───────────────────────────────────────────
if (
    isset($_SESSION['reg_id']) &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['ajax_attendance'])
) {
    header('Content-Type: application/json');
    $ensembleId = (int)($_POST['ensemble_id'] ?? 0);
    $date       = $_POST['date'] ?? '';
    $value      = $_POST['value'] ?? '';   // 'yes', 'no', or ''

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['ok' => false]);
        exit;
    }

    $attendance = in_array($value, ['yes', 'no'], true) ? $value : null;
    $pdo = db();

    if ($attendance === null) {
        $stmt = $pdo->prepare(
            'DELETE FROM attendance
             WHERE registration_id = ? AND ensemble_id = ? AND date = ?'
        );
        $stmt->execute([$_SESSION['reg_id'], $ensembleId, $date]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO attendance (registration_id, ensemble_id, date, attendance)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE attendance = VALUES(attendance)'
        );
        $stmt->execute([$_SESSION['reg_id'], $ensembleId, $date, $attendance]);
    }
    echo json_encode(['ok' => true, 'value' => $attendance]);
    exit;
}

// ── Fetch attendance data for display ──────────────────────────────────────
$tables = [];
if (isset($_SESSION['reg_id'])) {
    $pdo = db();

    // Ensembles that have at least one registrant
    $ensembleList = $pdo->query(
        'SELECT e.id, e.name, e.name_est
         FROM ensembles e
         WHERE EXISTS (
             SELECT 1 FROM registration_ensembles re WHERE re.ensemble_id = e.id
         )
         ORDER BY e.id'
    )->fetchAll();

    foreach ($ensembleList as $ens) {
        $eid   = (int)$ens['id'];
        $dates = ensembleDates($eid);
        if (empty($dates)) continue;

        // Participants for this ensemble with their registration IDs
        $participants = $pdo->prepare(
            'SELECT r.id, r.name
             FROM registrations r
             JOIN registration_ensembles re ON re.registration_id = r.id
             WHERE re.ensemble_id = ?
             ORDER BY r.name'
        );
        $participants->execute([$eid]);
        $rows = $participants->fetchAll();

        // Existing attendance values for this ensemble and date range
        $placeholders = implode(',', array_fill(0, count($dates), '?'));
        $attStmt = $pdo->prepare(
            "SELECT registration_id, date, attendance
             FROM attendance
             WHERE ensemble_id = ? AND date IN ($placeholders)"
        );
        $attStmt->execute(array_merge([$eid], $dates));
        $attMap = [];
        foreach ($attStmt->fetchAll() as $a) {
            $attMap[$a['registration_id']][$a['date']] = $a['attendance'];
        }

        $tables[] = [
            'ensemble'     => $ens,
            'dates'        => $dates,
            'participants' => $rows,
            'attMap'       => $attMap,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($t['title_attendance'], ENT_QUOTES, 'UTF-8') ?></title>
<style>
  body { font-family: sans-serif; max-width: 960px; margin: 2rem auto; padding: 0 1rem; line-height: 1.6; }
  h1 { font-size: 1.4rem; }
  .lang-switch { float: right; font-size: 0.9rem; }
  table { border-collapse: collapse; margin: 1rem 0 2rem; font-size: 0.9rem; }
  th, td { border: 1px solid #ccc; padding: 0.3rem 0.5rem; text-align: center; white-space: nowrap; }
  th { background: #f0f0f0; }
  td.name-cell { text-align: left; }
  td.att-cell { cursor: pointer; min-width: 2rem; }
  td.att-cell:hover { background: #e8f4ff; }
  td.att-yes  { color: #080; font-weight: bold; }
  td.att-no   { color: #c00; }
  .errors { color: #c00; background: #fee; border: 1px solid #c00; padding: 0.5rem 1rem; margin: 1rem 0; }
  .notice { color: #060; background: #efe; border: 1px solid #060; padding: 0.5rem 1rem; margin: 1rem 0; }
  input[type=email] { padding: 0.4rem; font-size: 1rem; }
  button { padding: 0.4rem 1.2rem; font-size: 1rem; cursor: pointer; }
</style>
</head>
<body>

<div class="lang-switch">
  <a href="?lang=et">ET</a> | <a href="?lang=en">EN</a>
</div>

<h1><?= htmlspecialchars($t['title_attendance'], ENT_QUOTES, 'UTF-8') ?></h1>

<?php if (isset($voteMsg)): ?>
  <div class="notice"><?= $voteMsg ?></div>
<?php endif; ?>

<?php if (!isset($_SESSION['reg_id'])): ?>
  <!-- Login form -->
  <?php if ($loginError): ?>
    <div class="errors"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <form method="post">
    <label for="login_email"><?= $t['login_prompt'] ?></label><br>
    <input type="email" id="login_email" name="login_email" required
           value="<?= htmlspecialchars($_POST['login_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit"><?= $t['login_btn'] ?></button>
  </form>

<?php else: ?>
  <p><?= $t['attendance_intro'] ?></p>

  <?php foreach ($tables as $tbl): ?>
    <?php
      $eid    = (int)$tbl['ensemble']['id'];
      $label  = $lang === 'et'
                  ? htmlspecialchars($tbl['ensemble']['name_est'], ENT_QUOTES, 'UTF-8')
                  : htmlspecialchars($tbl['ensemble']['name'],     ENT_QUOTES, 'UTF-8');
    ?>
    <h2><?= $label ?></h2>
    <table>
      <thead>
        <tr>
          <th><?= $lang === 'et' ? 'Nimi' : 'Name' ?></th>
          <?php foreach ($tbl['dates'] as $d): ?>
            <th><?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tbl['participants'] as $p): ?>
          <?php $isMe = ((int)$p['id'] === (int)$_SESSION['reg_id']); ?>
          <tr>
            <td class="name-cell"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <?php foreach ($tbl['dates'] as $d): ?>
              <?php
                $att   = $tbl['attMap'][$p['id']][$d] ?? null;
                $glyph = $att === 'yes' ? '+' : ($att === 'no' ? '−' : '');
                $cls   = $att === 'yes' ? 'att-yes' : ($att === 'no' ? 'att-no' : '');
              ?>
              <?php if ($isMe): ?>
                <td class="att-cell <?= $cls ?>"
                    data-ensemble="<?= $eid ?>"
                    data-date="<?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?>"
                    data-value="<?= $att ?? '' ?>"><?= $glyph ?></td>
              <?php else: ?>
                <td class="<?= $cls ?>"><?= $glyph ?></td>
              <?php endif; ?>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endforeach; ?>

  <?php if (empty($tables)): ?>
    <p><?= $lang === 'et' ? 'Hetkel pole aktiivseid ansambleid.' : 'No active ensembles at the moment.' ?></p>
  <?php endif; ?>

<script>
document.querySelectorAll('td.att-cell').forEach(function(cell) {
    cell.addEventListener('click', function() {
        var current = cell.dataset.value;   // 'yes', 'no', or ''
        var next    = current === ''    ? 'yes'
                    : current === 'yes' ? 'no'
                    : '';

        var body = new URLSearchParams({
            ajax_attendance: '1',
            ensemble_id:     cell.dataset.ensemble,
            date:            cell.dataset.date,
            value:           next
        });

        fetch('attendance.php', { method: 'POST', body: body })
            .then(function(r) { return r.json(); })
            .then(function(j) {
                if (!j.ok) return;
                cell.dataset.value = next;
                cell.textContent   = next === 'yes' ? '+' : (next === 'no' ? '−' : '');
                cell.className     = 'att-cell' +
                    (next === 'yes' ? ' att-yes' : next === 'no' ? ' att-no' : '');
            });
    });
});
</script>

<?php endif; ?>

</body>
</html>
