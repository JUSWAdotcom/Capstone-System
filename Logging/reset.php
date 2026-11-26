<?php
// Forgot password. Step 1: type your Student ID and the email of your account.
// If they match, a 6-digit code is emailed to the account (version 45: it is never shown on the page). Step 2: type the code and a new password.
require __DIR__.'/../config.php';
require __DIR__.'/security.php';

$error = '';
$notice = '';

// "Start again" link
if (isset($_GET['again'])) {
    forget_reset_code();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $step = $_POST['a'] ?? '';
    $ip = my_ip();

    if ($step === 'find') {
        // ----- step 1: is this Student ID + email really one account? -----
        $sid = trim($_POST['sid'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $who = 'id:' . strtolower($sid);

        if (wrong_tries('reset', $ip) >= IP_RESET_FAILS || wrong_tries('reset', $who) >= 5) {
            $error = 'Too many wrong tries. Please wait 15 minutes.';
        } else {
            $s = $pdo->prepare("SELECT * FROM users WHERE student_id=? AND LOWER(email)=? AND role<>'admin' LIMIT 1");
            $s->execute([$sid, $email]);
            $user = $s->fetch();

            if ($user && !mail_ready() && !SHOW_CODES_ON_SCREEN) {
                $error = 'We cannot email a reset code yet because email sending is not set up. Please ask your teacher or the admin for help.';
            } elseif ($user) {
                $code = make_reset_code($user['id']);
                if (mail_ready()) {
                    // the code goes to the email of the account. It is NOT shown on the page.
                    if (wrong_tries('vip', $ip) >= IP_CODES) {
                        forget_reset_code();
                        $error = 'Too many codes were asked for. Please wait 15 minutes.';
                    } else {
                        add_wrong_try('vip', $ip);
                        $m = mail_code_message('reset', preg_replace('/[\r\n]+/', ' ', $user['full_name']), $code);
                        if (send_mail($user['email'], $m['subject'], $m['text'], $mail_err, $m['html'])) {
                            $_SESSION['reset_mailed'] = mask_email($user['email']);
                        } else {
                            error_log('CodeSmartAi-X: could not send the reset email: ' . $mail_err);
                            forget_reset_code();
                            $error = 'We could not send the email: ' . mail_problem($mail_err) . ' Please ask your teacher or the admin for help.';
                        }
                    }
                }
            } else {
                add_wrong_try('reset', $ip);
                add_wrong_try('reset', $who);
                $error = 'That ID and email do not belong to the same account.';
            }
        }
    }

    if ($step === 'resend') {
        // ----- step 2: "Send a new code" (version 46): a NEW code goes to the same email and the old one stops working -----
        $s = $pdo->prepare("SELECT * FROM users WHERE id=? AND role<>'admin'");
        $s->execute([(int)($_SESSION['reset_user'] ?? 0)]);
        $user = $s->fetch();
        if (!$user || !reset_code_is_alive() || empty($_SESSION['reset_mailed']) || !mail_ready()) {
            forget_reset_code();
            $error = 'Please start again: type your Student ID and email.';
        } elseif (time() - ($_SESSION['reset_time'] ?? 0) < 60) {
            $error = 'Please wait one minute before asking for a new code. Look in the Spam folder too.';
        } elseif (wrong_tries('vip', $ip) >= IP_CODES) {
            $error = 'Too many codes were asked for. Please wait 15 minutes.';
        } else {
            add_wrong_try('vip', $ip);
            $mailed = $_SESSION['reset_mailed'];
            $code = make_reset_code($user['id']);
            $_SESSION['reset_mailed'] = $mailed;
            $m = mail_code_message('reset', preg_replace('/[\r\n]+/', ' ', $user['full_name']), $code);
            if (send_mail($user['email'], $m['subject'], $m['text'], $mail_err, $m['html'])) {
                $notice = 'A new code was sent to ' . $mailed . '. The old code does not work any more.';
            } else {
                error_log('CodeSmartAi-X: could not send the reset email: ' . $mail_err);
                forget_reset_code();
                $error = 'We could not send the email: ' . mail_problem($mail_err) . ' Please ask your teacher or the admin for help.';
            }
        }
    }

    if ($step === 'change' && !reset_code_is_alive()) {
        $error = 'The code is not valid any more. Please start again.';
    } elseif ($step === 'change') {
        // ----- step 2: the code and the new password -----
        $new = $_POST['new'] ?? '';
        $again = $_POST['again'] ?? '';
        $user_id = (int)$_SESSION['reset_user'];

        $s = $pdo->prepare('SELECT * FROM users WHERE id=?');
        $s->execute([$user_id]);
        $user = $s->fetch();
        if (!$user || $user['role'] === 'admin') { // the admin password is never reset with the on-screen code
            forget_reset_code();
            header('Location: '.BASE.'Logging/reset.php');
            exit;
        }

        $problem = password_problem($new, (string)$user['student_id'], $user['email']);

        if ($new !== $again) {
            $error = 'The two new passwords are not the same.';
        } elseif ($problem != '') {
            $error = $problem;
        } else {
            $error = check_reset_code($_POST['code'] ?? '');
            if ($error == '') {
                save_new_password($user_id, $new);
                if (!empty($_SESSION['reset_mailed'])) {
                    mark_verified($user_id); // the code came to the email of the account: the email is proven
                }
                clear_wrong_tries('reset', 'id:' . strtolower((string)$user['student_id']));
                forget_reset_code();
                unset($_SESSION['user']);
                header('Location: '.BASE.'Logging/login.php?reset=1');
                exit;
            }
        }
    }
}

head('Forgot password');
if ($error) {
    echo '<p class="err">' . e($error) . '</p>';
}
if ($notice) {
    echo '<p class="ok">' . e($notice) . '</p>';
}

if (reset_code_is_alive()) {
    // ----- show step 2 -----
?>
<form method="post" class="card auth">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="a" value="change">
<h2>Your code</h2>
<?php if (!empty($_SESSION['reset_mailed'])) { ?>
<p>Your Student ID and email match. We sent a 6-digit code to <b><?= e($_SESSION['reset_mailed']) ?></b>.</p>
<p><small>Type it below and choose a new password. The code works for 15 minutes. Look in the Spam folder too.</small></p>
<?php } else { ?>
<p>Your Student ID and email match. Your code is:</p>
<p class="codebox"><?= e($_SESSION['reset_code']) ?></p>
<p><small>Testing only (SHOW_CODES_ON_SCREEN is on in config.php). Type it below and choose a new password. The code works for 15 minutes.</small></p>
<?php } ?>
<label>Type the code<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="off" autofocus></label>
<?= pw('New password (8+ characters)', 8, 'new', 'new-password') ?>
<?= pw('Type the new password again', 8, 'again', 'new-password') ?>
<button>Change password</button>
<a href="Logging/reset.php?again=1">Start again</a>
</form>
<?php if (!empty($_SESSION['reset_mailed'])) { ?>
<form method="post" class="card auth resend">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="a" value="resend">
<p><b>No code, or it expired?</b> We can email you a new one.</p>
<button class="alt">Send a new code</button>
</form>
<?php } ?>
<?php
} else {
    // ----- show step 1 -----
?>
<form method="post" class="card auth">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="a" value="find">
<p>Type your Student ID (teachers: your Teacher ID) and the email you used when you signed up. If they match, we email you a code to choose a new password.</p>
<label>Student ID / Teacher ID<input name="sid" required autofocus autocomplete="username"></label>
<label>Email<input type="email" name="email" required autocomplete="email"></label>
<button>Get my code</button>
<a href="Logging/login.php">Back to log in</a>
</form>
<?php
}
foot();
