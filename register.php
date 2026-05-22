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

// Build attendance URL for a given email
function attendanceUrl(string $email): string {
    $token = hash_hmac('sha256', $email, SECRET_KEY);
    return BASE_URL . '/attendance.php?email=' . urlencode($email) . '&token=' . $token;
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize
    $name       = trim(htmlspecialchars($_POST['name']       ?? '', ENT_QUOTES, 'UTF-8'));
    $email      = trim($_POST['email']      ?? '');
    $instrument = trim(htmlspecialchars($_POST['instrument'] ?? '', ENT_QUOTES, 'UTF-8'));
    $experience = trim(htmlspecialchars($_POST['experience'] ?? '', ENT_QUOTES, 'UTF-8'));
    $comments   = trim(htmlspecialchars($_POST['comments']   ?? '', ENT_QUOTES, 'UTF-8'));
    $ensembleIds = array_map('intval', (array)($_POST['ensembles'] ?? []));

    // Validate
    if ($name === '') {
        $errors[] = $t['error_name_required'];
    }
    if ($email === '') {
        $errors[] = $t['error_email_required'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = $t['error_email_invalid'];
    }
    if (empty($ensembleIds)) {
        $errors[] = $t['error_ensemble'];
    }

    // Check duplicate email
    if (empty($errors)) {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM registrations WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = $t['error_email_exists'];
        }
    }

    if (empty($errors)) {
        $pdo = db();
        $pdo->beginTransaction();

        // Insert registration
        $stmt = $pdo->prepare(
            'INSERT INTO registrations (name, email, instrument, experience, comments)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $instrument, $experience, $comments]);
        $registrationId = (int)$pdo->lastInsertId();

        // Validate ensemble IDs against DB to prevent injection of arbitrary IDs
        $validIds = $pdo->query('SELECT id FROM ensembles')->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare(
            'INSERT INTO registration_ensembles (registration_id, ensemble_id) VALUES (?, ?)'
        );
        foreach ($ensembleIds as $eid) {
            if (in_array($eid, $validIds, true)) {
                $stmt->execute([$registrationId, $eid]);
            }
        }

        $pdo->commit();

        // Build attendance URL
        $url = attendanceUrl($email);

        // Send bilingual confirmation email
        $etLang = require __DIR__ . '/lang/et.php';
        $enLang = require __DIR__ . '/lang/en.php';

        $subject = $etLang['email_confirm_subject'];
        $body  = "--- Eesti ---\n" . sprintf($etLang['email_confirm_body'], $url);
        $body .= "\n\n--- English ---\n" . sprintf($enLang['email_confirm_body'], $url);

        sendEmail($email, $subject, $body);

        $success = true;
        $successUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}

// Load ensembles for the form
$ensembleRows = db()->query('SELECT id, name, name_est FROM ensembles ORDER BY id')->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($t['title_register'], ENT_QUOTES, 'UTF-8') ?></title>
<style>
  body { font-family: sans-serif; max-width: 700px; margin: 2rem auto; padding: 0 1rem; line-height: 1.6; }
  h1 { font-size: 1.4rem; }
  .lang-switch { float: right; font-size: 0.9rem; }
  label { display: block; margin-top: 1rem; font-weight: bold; }
  input[type=text], input[type=email], textarea { width: 100%; box-sizing: border-box; padding: 0.4rem; font-size: 1rem; }
  .ensemble-list { list-style: none; padding: 0; }
  .ensemble-list li { margin: 0.3rem 0; }
  .errors { color: #c00; background: #fee; border: 1px solid #c00; padding: 0.5rem 1rem; margin: 1rem 0; }
  .success { color: #060; background: #efe; border: 1px solid #060; padding: 0.5rem 1rem; margin: 1rem 0; }
  button[type=submit] { margin-top: 1.2rem; padding: 0.5rem 1.5rem; font-size: 1rem; cursor: pointer; }
</style>
</head>
<body>

<div class="lang-switch">
  <a href="?lang=et">ET</a> | <a href="?lang=en">EN</a>
</div>

<h1><?= htmlspecialchars($t['title_register'], ENT_QUOTES, 'UTF-8') ?></h1>

<?= $t['intro'] ?>

<?php if ($success): ?>
  <div class="success"><?= sprintf($t['success_register'], '<a href="' . $successUrl . '">' . $successUrl . '</a>') ?></div>
<?php else: ?>

<?php if (!empty($errors)): ?>
  <div class="errors"><ul><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="register.php?lang=<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">

  <label for="name"><?= $t['label_name'] ?> *</label>
  <input type="text" id="name" name="name" required
         value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

  <label for="email"><?= $t['label_email'] ?> *</label>
  <input type="email" id="email" name="email" required
         value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

  <label for="instrument"><?= $t['label_instrument'] ?></label>
  <input type="text" id="instrument" name="instrument"
         value="<?= htmlspecialchars($_POST['instrument'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

  <label><?= $t['label_ensembles'] ?> *</label>
  <ul class="ensemble-list">
    <?php foreach ($ensembleRows as $row): ?>
    <li>
      <label style="font-weight:normal">
        <input type="checkbox" name="ensembles[]" value="<?= (int)$row['id'] ?>"
          <?= in_array((int)$row['id'], array_map('intval', (array)($_POST['ensembles'] ?? []))) ? 'checked' : '' ?>>
        <?= $lang === 'et'
              ? htmlspecialchars($row['name_est'], ENT_QUOTES, 'UTF-8')
              : htmlspecialchars($row['name'],     ENT_QUOTES, 'UTF-8') ?>
      </label>
    </li>
    <?php endforeach; ?>
  </ul>

  <label for="experience"><?= $t['label_experience'] ?></label>
  <textarea id="experience" name="experience" rows="4"><?= htmlspecialchars($_POST['experience'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

  <label for="comments"><?= $t['label_comments'] ?></label>
  <textarea id="comments" name="comments" rows="3"><?= htmlspecialchars($_POST['comments'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

  <button type="submit"><?= $t['btn_submit'] ?></button>

</form>
<?php endif; ?>

</body>
</html>
