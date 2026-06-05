<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

session_start();

// ── Authentication ─────────────────────────────────────────────────────────
const ADMIN_PASSWORD = '***CHANGE_ME***';

if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_auth'] = true;
    } else {
        $loginError = 'Wrong password.';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_auth']);
    header('Location: admin.php');
    exit;
}

if (empty($_SESSION['admin_auth'])) {
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login</title>
<style>
  body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
  .box { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.15); min-width: 280px; }
  h2 { margin-top: 0; }
  input[type=password] { width: 100%; padding: .5rem; margin: .5rem 0 1rem; box-sizing: border-box; font-size: 1rem; border: 1px solid #ccc; border-radius: 4px; }
  button { width: 100%; padding: .6rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; }
  button:hover { background: #1d4ed8; }
  .error { color: #dc2626; font-size: .9rem; margin-bottom: .5rem; }
</style>
</head>
<body>
<div class="box">
  <h2>Admin Login</h2>
  <?php if (!empty($loginError)): ?>
    <p class="error"><?= htmlspecialchars($loginError) ?></p>
  <?php endif; ?>
  <form method="post" action="admin.php">
    <label>Password</label>
    <input type="password" name="password" autofocus required>
    <button type="submit">Log in</button>
  </form>
</div>
</body>
</html>
<?php
    exit;
}

// ── Fetch data ─────────────────────────────────────────────────────────────
$pdo = db();

// All ensembles ordered by id
$ensembles = $pdo->query('SELECT id, name FROM ensembles ORDER BY id')->fetchAll();
$ensembleMap = array_column($ensembles, 'name', 'id'); // id => name

// All registrations
$registrations = $pdo->query(
    'SELECT id, created_at, name, email, instrument, experience, comments
     FROM registrations ORDER BY id'
)->fetchAll();

// Ensemble ids per registration
$reRows = $pdo->query(
    'SELECT registration_id, ensemble_id FROM registration_ensembles ORDER BY registration_id, ensemble_id'
)->fetchAll();

$regEnsembles = []; // registration_id => [ensemble_id, ...]
foreach ($reRows as $row) {
    $regEnsembles[$row['registration_id']][] = $row['ensemble_id'];
}

// Emails grouped by ensemble
$ensembleEmails = []; // ensemble_id => [email, ...]
foreach ($reRows as $row) {
    $eid = $row['ensemble_id'];
    // Get email from registrations array
    foreach ($registrations as $reg) {
        if ($reg['id'] === $row['registration_id']) {
            $ensembleEmails[$eid][] = $reg['email'];
            break;
        }
    }
}

// All emails
$allEmails = array_column($registrations, 'email');

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin — Registrations</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: sans-serif; margin: 0; background: #f5f5f5; color: #111; }
  header { background: #1e3a5f; color: #fff; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
  header h1 { margin: 0; font-size: 1.3rem; }
  a.logout { color: #93c5fd; font-size: .9rem; text-decoration: none; }
  a.logout:hover { text-decoration: underline; }
  main { padding: 1.5rem; }
  h2 { margin-top: 1.5rem; font-size: 1.1rem; color: #1e3a5f; }

  /* Registrations table */
  .table-wrap { overflow-x: auto; }
  table { border-collapse: collapse; width: 100%; background: #fff; font-size: .875rem; }
  th, td { border: 1px solid #d1d5db; padding: .45rem .7rem; white-space: nowrap; }
  th { background: #1e3a5f; color: #fff; text-align: left; }
  tr:nth-child(even) td { background: #f9fafb; }
  td.wrap { white-space: normal; max-width: 260px; word-break: break-word; }
  td.center { text-align: center; }

  /* Ensemble key */
  .ens-key { margin-bottom: 1rem; font-size: .85rem; }
  .ens-key span { display: inline-block; margin-right: 1.2rem; }
  .badge { display: inline-block; background: #1e3a5f; color: #fff; border-radius: 3px; padding: 1px 6px; font-size: .78rem; margin-right: 3px; }

  /* Mailing lists */
  .mailing-box { background: #fff; border: 1px solid #d1d5db; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; }
  .mailing-box h3 { margin: 0 0 .5rem; font-size: .95rem; color: #374151; }
  .mailing-list { font-size: .85rem; word-break: break-all; color: #374151; line-height: 1.6; }
  .copy-btn { margin-top: .4rem; font-size: .78rem; padding: .25rem .6rem; background: #e5e7eb; border: 1px solid #9ca3af; border-radius: 4px; cursor: pointer; }
  .copy-btn:hover { background: #d1d5db; }
  .copied { color: #16a34a; font-size: .78rem; margin-left: .5rem; display: none; }
</style>
</head>
<body>
<header>
  <h1>Registrations Admin</h1>
  <a class="logout" href="admin.php?logout=1">Log out</a>
</header>
<main>

  <!-- ── Ensemble key ───────────────────────────────────────────────────── -->
  <div class="ens-key">
    <strong>Ensembles:</strong>
    <?php foreach ($ensembles as $e): ?>
      <span><span class="badge"><?= $e['id'] ?></span><?= htmlspecialchars($e['name']) ?></span>
    <?php endforeach; ?>
  </div>

  <!-- ── Registrations table ───────────────────────────────────────────── -->
  <h2>Registrations (<?= count($registrations) ?>)</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Registered</th>
          <th>Name</th>
          <th>Email</th>
          <th>Instrument</th>
          <th>Ensembles</th>
          <th>Experience</th>
          <th>Comments</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($registrations as $reg): ?>
          <?php
            $eids = $regEnsembles[$reg['id']] ?? [];
            sort($eids);
            $ensStr = implode(', ', $eids);
          ?>
          <tr>
            <td class="center"><?= $reg['id'] ?></td>
            <td><?= htmlspecialchars(substr($reg['created_at'], 0, 10)) ?></td>
            <td><?= htmlspecialchars($reg['name']) ?></td>
            <td><?= htmlspecialchars($reg['email']) ?></td>
            <td><?= htmlspecialchars($reg['instrument'] ?? '') ?></td>
            <td class="center"><?= htmlspecialchars($ensStr) ?></td>
            <td class="wrap"><?= htmlspecialchars($reg['experience'] ?? '') ?></td>
            <td class="wrap"><?= htmlspecialchars($reg['comments'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- ── Mailing lists ─────────────────────────────────────────────────── -->
  <h2>Mailing Lists</h2>

  <div class="mailing-box">
    <h3>All members (<?= count($allEmails) ?>)</h3>
    <div class="mailing-list" id="ml-all"><?= htmlspecialchars(implode(', ', $allEmails)) ?></div>
    <button class="copy-btn" onclick="copyList('ml-all', this)">Copy</button>
    <span class="copied">Copied!</span>
  </div>

  <?php foreach ($ensembles as $e): ?>
    <?php
      $emails = array_unique($ensembleEmails[$e['id']] ?? []);
      $mlId = 'ml-ens-' . $e['id'];
    ?>
    <div class="mailing-box">
      <h3><?= htmlspecialchars($e['name']) ?> — ensemble <?= $e['id'] ?> (<?= count($emails) ?>)</h3>
      <div class="mailing-list" id="<?= $mlId ?>"><?= htmlspecialchars(implode(', ', $emails)) ?></div>
      <button class="copy-btn" onclick="copyList('<?= $mlId ?>', this)">Copy</button>
      <span class="copied">Copied!</span>
    </div>
  <?php endforeach; ?>

</main>
<script>
function copyList(id, btn) {
  const text = document.getElementById(id).textContent;
  navigator.clipboard.writeText(text).then(() => {
    const msg = btn.nextElementSibling;
    msg.style.display = 'inline';
    setTimeout(() => { msg.style.display = 'none'; }, 2000);
  });
}
</script>
</body>
</html>
