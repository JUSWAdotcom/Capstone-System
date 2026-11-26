<?php
// Change password (version 47): two steps.
// Step 1: a 6-digit code is emailed to the account ("Send me a code").
// Step 2: the code + the OLD password + the new password (twice).
// So somebody who only knows the password, or who only finds the laptop logged in, cannot change it.
require __DIR__.'/../config.php';
require __DIR__.'/security.php';

$u = need('all'); // students, teachers and the admin

// get this user from the database
$s = $pdo->prepare('SELECT * FROM users WHERE id=?');
$s->execute([$u['id']]);
$me = $s->fetch();
$uid = (int)$me['id'];

// ---------- the change-password code (kept in the session, works for 15 minutes, 5 wrong tries) ----------
function pwc_alive($uid) {
    $c = $_SESSION['pwc'] ?? null;
    if (!$c || (int)$c['uid'] !== (int)$uid) {
        return false;
    }
    if (time() - (int)$c['time'] > 900) {
        unset($_SESSION['pwc']);
        return false;
    }
    return true;
}

// can a code be sent? With email set up: yes. Without email: only for testing (SHOW_CODES_ON_SCREEN),
// and for the admin, who must be able to change the first password before Admin > Email is set up.
$mail_on = mail_ready();
$on_screen = !$mail_on && (SHOW_CODES_ON_SCREEN || $me['role'] === 'admin');
$can_send = $mail_on || $on_screen;
$masked = mask_email($me['email']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $a = $_POST['a'] ?? '';

    if ($a === 'send') {
        // ----- step 1: make a code and email it -----
        if (!$can_send) {
            $error = 'We cannot email a code yet because email sending is not set up. Please tell the admin (Admin > Email).';
        } elseif (pwc_alive($uid) && time() - (int)$_SESSION['pwc']['sent'] < 60) {
            $error = 'Please wait one minute before asking for a new code. Look in the Spam folder too.';
        } elseif (wrong_tries('pwsend', (string)$uid) >= 5) {
            $error = 'Too many codes were asked for. Please wait 15 minutes, then try again.';
        } else {
            $code = (string)random_int(100000, 999999);
            add_wrong_try('pwsend', (string)$uid);
            $ok = true;
            $show = !$mail_on;
            $why = '';
            if ($mail_on) {
                $m = mail_code_message('change', $me['full_name'], $code);
                if (!send_mail($me['email'], $m['subject'], $m['text'], $err, $m['html'])) {
                    error_log('CodeSmartAi-X: could not send the change-password email: ' . $err);
                    if ($me['role'] === 'admin') { // the admin must never be stuck: show the code, with the reason
                        $show = true;
                        $why = mail_problem($err);
                    } else {
                        $error = 'We could not send the email: ' . mail_problem($err) . ' Please tell the admin.';
                        $ok = false;
                    }
                }
            }
            if ($ok) {
                $_SESSION['pwc'] = ['uid' => $uid, 'code' => $code, 'time' => time(), 'sent' => time(), 'tries' => 0, 'show' => $show, 'why' => $why];
                flash(!$show ? 'We sent a 6-digit code to ' . $masked . '. Look in the Spam folder too.' : ($why !== '' ? 'The code could not be emailed, so it is shown below.' : 'Your code is ready (shown below, because email is not set up yet).'));
                header('Location: ' . BASE . 'Logging/password.php');
                exit;
            }
        }
    } elseif ($a === 'change') {
        // ----- step 2: check the code, the old password and the new password -----
        $typed = trim((string)($_POST['code'] ?? ''));
        $old = $_POST['old'] ?? '';
        $new = $_POST['new'] ?? '';
        $again = $_POST['again'] ?? '';
        $problem = password_problem($new, (string)$me['student_id'], $me['email']);

        if (!pwc_alive($uid)) {
            $error = 'Your code expired or was not sent yet. Press "Send me a code".';
        } elseif ((int)$_SESSION['pwc']['tries'] >= 5) {
            unset($_SESSION['pwc']);
            $error = 'Too many wrong codes. Press "Send me a code" to get a new one.';
        } elseif (!preg_match('/^[0-9]{6}$/', $typed) || !hash_equals((string)$_SESSION['pwc']['code'], $typed)) {
            $_SESSION['pwc']['tries']++;
            $error = 'That code is not correct.';
        } elseif (wrong_tries('pw', (string)$uid) >= 5) {
            $error = 'Too many wrong tries. Please wait 15 minutes, or use "I do not remember my old password" below.';
        } elseif (!password_verify($old, $me['password_hash'])) {
            add_wrong_try('pw', (string)$uid);
            $error = 'Your old password is not correct.';
        } elseif ($new !== $again) {
            $error = 'The two new passwords are not the same.';
        } elseif ($new === $old) {
            $error = 'Your new password must be different from the old one.';
        } elseif ($me['role'] === 'admin' && hash_equals(ADMIN_START_PASSWORD, $new)) {
            $error = 'Do not use the first password from config.php again.';
        } elseif ($problem != '') {
            $error = $problem;
        } else {
            save_new_password($uid, $new);
            unset($_SESSION['pwc']);
            clear_wrong_tries('pwsend', (string)$uid);
            session_regenerate_id(true);
            // this device stays logged in, other devices are logged out
            $s = $pdo->prepare('SELECT password_hash FROM users WHERE id=?');
            $s->execute([$uid]);
            $_SESSION['user']['pwv'] = pwv($s->fetchColumn());
            flash('Your password was changed. You are still logged in here, but any other device was logged out.');
            header('Location: ' . BASE . 'Logging/password.php');
            exit;
        }
    }
}

head('Change password');
flash();
if ($error) {
    echo '<p class="err">' . e($error) . '</p>';
}

if (pwc_alive($uid)) {
    // ----- step 2 -----
    $c = $_SESSION['pwc'];
?>
<form method="post" class="card auth">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="a" value="change">
<p class="steps"><span class="done">1 Code sent</span><span class="now">2 New password</span></p>
<h2>Type the code and your new password</h2>
<?php if (empty($c['show'])) { ?>
<p>We sent a 6-digit code to <b><?= e($masked) ?></b>. It works for 15 minutes.</p>
<?php } else { ?>
<p><?= !empty($c['why']) ? 'The code could not be emailed: ' . e($c['why']) . ' Your code is:' : 'Email is not set up yet, so your code is shown here:' ?></p>
<p class="codebox"><?= e($c['code']) ?></p>
<p><small><?= $me['role'] === 'admin' ? 'After you change your password, open Admin &gt; Email and fix the email setup, so the codes are emailed.' : 'Testing only (SHOW_CODES_ON_SCREEN is on in config.php).' ?></small></p>
<?php } ?>
<label>Code from your email<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" autofocus></label>
<?= pw('Old password', 0, 'old', 'current-password') ?>
<?= pw('New password (8+ characters)', 8, 'new', 'new-password') ?>
<?= pw('Type the new password again', 8, 'again', 'new-password') ?>
<button>Change password</button>
</form>

<form method="post" class="card auth resend">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="a" value="send">
<p><b>No code, or it expired?</b> We can email you a new one. The old code then stops working.</p>
<button class="alt">Send a new code</button>
</form>
<?php
} else {
    // ----- step 1 -----
?>
<form method="post" class="card auth">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="a" value="send">
<p class="steps"><span class="now">1 Get a code</span><span>2 New password</span></p>
<h2>Change your password</h2>
<p>To keep your account safe, we first email a 6-digit code to <b><?= e($masked) ?></b>. Then you type the code, your old password and your new password.</p>
<?php if (!$can_send) { ?>
<p class="err">Email sending is not set up yet, so the code cannot be sent. Please tell the admin (Admin &gt; Email).</p>
<?php } ?>
<button<?= $can_send ? '' : ' disabled' ?>>Send me a code</button>
</form>
<?php
}
?>
<div class="card auth">
<h2>I do not remember my old password</h2>
<p>Type your Student ID and email. If they match your account, we email you a code to choose a new password.</p>
<a class="btn alt" href="Logging/reset.php">Use my Student ID and email</a>
</div>
<?php foot();
