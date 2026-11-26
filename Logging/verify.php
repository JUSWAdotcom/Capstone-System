<?php
// Verify the email (version 47). The sign-up form is waiting in the session (Logging/register.php). The account is made HERE,
// only when the right 6-digit code from the email is typed. Without the code there is no account at all.
require __DIR__.'/../config.php';
require __DIR__.'/security.php';

$p = signup_pending();
if (!$p) {
    // nothing is waiting (the 30 minutes are over, or the sign-up was finished or cancelled)
    unset($_SESSION['signup'], $_SESSION['verify_uid'], $_SESSION['verify_test_code']);
    flash('Your sign-up is not waiting any more (the code works for 30 minutes). Please sign up again.',true);
    header('Location: '.BASE.'Logging/register.php');
    exit;
}

$error = '';
$note = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $act = $_POST['a'] ?? '';

    if ($act === 'check') {
        $typed = trim((string)($_POST['code'] ?? ''));
        if ((int)$p['tries'] >= 5) {
            unset($_SESSION['signup']);
            flash('Too many wrong codes, so no account was made. Please sign up again.',true);
            header('Location: '.BASE.'Logging/register.php');
            exit;
        } elseif (!preg_match('/^[0-9]{6}$/', $typed) || !hash_equals((string)$p['code'], hash('sha256', $typed))) {
            $_SESSION['signup']['tries'] = (int)$p['tries'] + 1;
            $error = 'That code is not correct.';
        } elseif (signup_taken($p['sid'], $p['email'])) {
            unset($_SESSION['signup']);
            flash('That Student ID or email was registered by somebody else in the meantime, so no account was made. If it is yours, tell your teacher or the admin.', true);
            header('Location: '.BASE.'Logging/register.php');
            exit;
        } else {
            // the right code: NOW the account is made (the email is proven)
            try {
                $s = $pdo->prepare('INSERT INTO users(full_name,student_id,year_level,email,password_hash,role,email_verified,approved) VALUES(?,?,?,?,?,?,1,?)');
                $s->execute([$p['name'], $p['sid'], $p['year'], $p['email'], $p['hash'], $p['role'], (int)$p['approved']]);
                $new_id = (int)$pdo->lastInsertId();
                foreach (['vcode', 'vsend', 'login'] as $kind) { // a new id can be an id that was used before: start with clean counters
                    clear_wrong_tries($kind, 'u' . $new_id);
                }
            } catch (PDOException $x) {
                unset($_SESSION['signup']);
                flash('That Student ID or email is already registered, so no account was made.',true);
                header('Location: '.BASE.'Logging/register.php');
                exit;
            }
            unset($_SESSION['signup'], $_SESSION['verify_uid'], $_SESSION['verify_test_code']);
            header('Location: '.BASE.'Logging/login.php?verified=1'.((int)$p['approved'] === 0 ? '&wait=1' : ''));
            exit;
        }
    } elseif ($act === 'resend') {
        if (time() - (int)$p['sent'] < 60) {
            $error = 'Please wait one minute before asking for a new code. Look in the Spam folder too.';
        } else {
            $error = signup_send_code($p);
            if ($error === '') {
                $_SESSION['signup'] = $p;
                $note = 'A new code was sent to ' . mask_email($p['email']) . '. The old code does not work any more.';
            }
        }
    } elseif ($act === 'change') {
        // the email was mistyped: use another one and send a new code there
        $new = trim($_POST['email'] ?? '');
        if (!filter_var($new, FILTER_VALIDATE_EMAIL) || strlen($new) > 100) {
            $error = 'Type a valid email.';
        } elseif (!email_domain_ok($new)) {
            $error = 'Please use your school email address (' . ALLOWED_EMAIL_DOMAINS . ').';
        } elseif (signup_taken($p['sid'], $new)) {
            $error = 'That email is already registered.';
        } else {
            $q = $p;
            $q['email'] = $new;
            $error = signup_send_code($q);
            if ($error === '') {
                $p = $q;
                $_SESSION['signup'] = $p;
                $note = 'The email was changed. A code was sent to ' . mask_email($new) . '.';
            }
        }
    } elseif ($act === 'cancel') {
        unset($_SESSION['signup']);
        flash('Your sign-up was cancelled. No account was made.');
        header('Location: '.BASE.'Logging/register.php');
        exit;
    }
    $p = signup_pending() ?: $p;
}

head('Verify your email');
flash();
if ($error) {
    echo '<p class="err">' . e($error) . '</p>';
}
if ($note) {
    echo '<p class="card ok">' . e($note) . '</p>';
}
?>
<form method="post" class="card auth">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="a" value="check">
<?php if (empty($p['show'])) { ?>
<p>We sent a 6-digit code to <b><?= e(mask_email($p['email'])) ?></b>. Type it below to create your account.</p>
<p><small>Your account is made only after you type the right code. The code works for 30 minutes. Look in the Spam folder too.</small></p>
<?php } else { ?>
<p>Type the 6-digit code to create your account.</p>
<p class="card"><b>Testing only</b> (SHOW_CODES_ON_SCREEN is on in config.php): the code is shown here:</p>
<p class="codebox"><?= e($p['show']) ?></p>
<?php } ?>
<label>Type the code<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" autofocus></label>
<button>Verify and create my account</button>
</form>

<form method="post" class="card auth">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="a" value="resend">
<p>No code, or it expired?</p>
<button class="alt">Send a new code</button>
</form>

<form method="post" class="card auth">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="a" value="change">
<details>
<summary>Wrong email? Change it</summary>
<label>Correct email<input type="email" name="email" maxlength="100" required autocomplete="email"></label>
<button class="alt">Change email and send a code</button>
</details>
</form>

<form method="post" class="auth">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="a" value="cancel">
<p><button class="linkbtn">Cancel the sign-up</button> &middot; <a href="Logging/login.php?open=1">Back to log in</a></p>
</form>
<?php foot();
