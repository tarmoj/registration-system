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

function validToken(string $email, string $token): bool {
    return hash_equals(hash_hmac('sha256', $email, SECRET_KEY), $token);
}

function buildUnsubscribeUrl(string $email): string {
    $token = hash_hmac('sha256', $email, SECRET_KEY);
    return BASE_URL . '/unsubscribe.php?email=' . urlencode($email) . '&token=' . $token;
}

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$valid = $email !== '' && $token !== '' && validToken($email, $token);

$done      = false;
$error     = false;
$emailSent = false;
$emailErr  = false;

// No token: show email-request form or handle its submission
if (!$valid) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_email'])) {
        $inputEmail = trim($_POST['request_email'] ?? '');
        if (filter_var($inputEmail, FILTER_VALIDATE_EMAIL)) {
            $pdo  = db();
            $stmt = $pdo->prepare('SELECT id FROM registrations WHERE email = ?');
            $stmt->execute([$inputEmail]);
            if ($stmt->fetch()) {
                $unsubUrl = buildUnsubscribeUrl($inputEmail);

                $etLang = require __DIR__ . '/lang/et.php';
                $enLang = require __DIR__ . '/lang/en.php';
                $subject = $etLang['unsubscribe_email_subject'] . ' / ' . $enLang['unsubscribe_email_subject'];
                $body  = "--- Eesti ---\n" . sprintf($etLang['unsubscribe_email_body'], $unsubUrl);
                $body .= "\n--- English ---\n" . sprintf($enLang['unsubscribe_email_body'], $unsubUrl);
                sendEmail($inputEmail, $subject, $body);
                $emailSent = $inputEmail;
            } else {
                $emailErr = true;
            }
        } else {
            $emailErr = true;
        }
    }
    // Don't set $error — fall through to show the email form (or sent notice)
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_unsubscribe'])) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, name FROM registrations WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if ($row) {
        $regId = (int)$row['id'];
        $name  = $row['name'] ?? $email;
        $pdo->prepare('DELETE FROM attendance WHERE registration_id = ?')->execute([$regId]);
        $pdo->prepare('DELETE FROM registration_ensembles WHERE registration_id = ?')->execute([$regId]);
        $pdo->prepare('DELETE FROM registrations WHERE id = ?')->execute([$regId]);
        sendEmail(ADMIN_EMAIL, 'Koosmänguklubi – lahkumine', "$name, $email, lahkus Koosmänguklubbist.");
    }
    // Even if already gone, show success
    $done = true;
}

$registerUrl = htmlspecialchars(BASE_URL . '/register.php', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($t['title_unsubscribe'], ENT_QUOTES, 'UTF-8') ?></title>
<style>
  body { font-family: sans-serif; max-width: 600px; margin: 2rem auto; padding: 0 1rem; line-height: 1.6; }
  h1 { font-size: 1.4rem; }
  .lang-switch { float: right; font-size: 0.9rem; }
  .errors { color: #c00; background: #fee; border: 1px solid #c00; padding: 0.5rem 1rem; margin: 1rem 0; }
  .notice  { color: #060; background: #efe; border: 1px solid #060; padding: 0.5rem 1rem; margin: 1rem 0; }
  button[type=submit] { padding: 0.5rem 1.5rem; font-size: 1rem; cursor: pointer; color: #fff; background: #a00; border: none; border-radius: 3px; }
</style>
</head>
<body>

<div class="lang-switch">
  <a href="?lang=et&email=<?= urlencode($email) ?>&token=<?= urlencode($token) ?>">ET</a> |
  <a href="?lang=en&email=<?= urlencode($email) ?>&token=<?= urlencode($token) ?>">EN</a>
</div>

<h1><?= htmlspecialchars($t['title_unsubscribe'], ENT_QUOTES, 'UTF-8') ?></h1>

<?php if ($error): ?>
  <div class="errors"><?= $t['unsubscribe_invalid'] ?></div>

<?php elseif ($done): ?>
  <div class="notice"><?= sprintf($t['unsubscribe_done'], $registerUrl) ?></div>

<?php elseif (!$valid): ?>
  <?php if ($emailSent): ?>
    <div class="notice"><?= sprintf($t['unsubscribe_email_sent'], htmlspecialchars($emailSent, ENT_QUOTES, 'UTF-8')) ?></div>
  <?php else: ?>
    <?php if ($emailErr): ?>
      <div class="errors"><?= $t['unsubscribe_email_error'] ?></div>
    <?php endif; ?>
    <form method="post" action="unsubscribe.php?lang=<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
      <label for="request_email"><?= $t['unsubscribe_email_prompt'] ?></label><br>
      <input type="email" id="request_email" name="request_email" required
             style="padding:0.4rem;font-size:1rem;width:100%;box-sizing:border-box;margin:0.4rem 0">
      <button type="submit" style="background:#555"><?= $t['unsubscribe_email_btn'] ?></button>
    </form>
  <?php endif; ?>

<?php else: ?>
  <p><?= $t['unsubscribe_confirm'] ?></p>
  <form method="post"
        action="unsubscribe.php?email=<?= urlencode($email) ?>&token=<?= urlencode($token) ?>&lang=<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="confirm_unsubscribe" value="1">
    <button type="submit"><?= $t['unsubscribe_btn'] ?></button>
  </form>
<?php endif; ?>

</body>
</html>
