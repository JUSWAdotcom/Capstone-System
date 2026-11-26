<?php
// Admin: email setup. The admin types the Gmail (or other mail server) that sends the verification code and the forgot-password code.
// The settings are saved in the database (table settings), so nobody has to edit config.php. The page can also send a test email
// and shows the real answer of the mail server when it refuses.
require __DIR__.'/../config.php';
require __DIR__.'/../Logging/mailer.php';
$u = need('admin');

$error = '';
$testError = '';

// a short help for the most common problems (the real answer of the mail server is always shown too)
function mail_hint($err) {
    if (stripos($err, 'certificate') !== false || stripos($err, 'SSL operation failed') !== false) {
        return 'This computer does not trust the certificate of the mail server. This often happens in XAMPP on Windows. Open "Other mail server" below, tick "Do not check the certificate", press Save and try again.';
    }
    if (preg_match('/\b(534|535|538)\b/', $err)) {
        return 'The mail server did not accept the Gmail address or the App password. Check that 2-Step Verification is ON for that Gmail, that you typed an App password (16 letters from Google) and not the normal Gmail password, and that the App password was made in the SAME Gmail as the address above.';
    }
    if (stripos($err, 'Could not connect') !== false) {
        return 'This computer could not reach the mail server. Check the internet connection, and that the firewall or antivirus does not block port 587.';
    }
    if (stripos($err, 'not an email address') !== false) {
        return 'Type the full Gmail address, for example codesmartaix@gmail.com.';
    }
    return '';
}

// What was used to log in to the mail server, in words. The password itself is never shown, only how long it is and what kind of characters it has.
// A Gmail App password is always exactly 16 small letters (a-z). Anything else is probably the normal Gmail password or a typing mistake.
function login_note($c) {
    if ($c['USER'] === '') {
        return '';
    }
    $pass = str_replace(' ', '', (string)$c['PASS']);
    $n = strlen($pass);
    $t = 'Login used: <b>' . e($c['USER']) . '</b> with a password of <b>' . $n . ' character' . ($n == 1 ? '' : 's') . '</b>.';
    if (stripos($c['HOST'], 'gmail') !== false || stripos($c['HOST'], 'google') !== false) {
        if (preg_match('/^[a-z]{16}$/', $pass)) {
            $t .= ' That is the right shape for a Gmail App password (16 small letters).';
        } else {
            $t .= ' <b>This is not the shape of a Gmail App password</b>, which is exactly 16 small letters (a to z). It is probably the normal Gmail password or a typing mistake. Make an App password and type it again.';
        }
    }
    return $t;
}

$cur = mail_set();
$savedPass = $cur['SOURCE'] === 'page' && $cur['PASS'] !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $act = (string)($_POST['act'] ?? '');

    if ($act === 'save') {
        $user = trim((string)($_POST['user'] ?? ''));
        $pass = str_replace(' ', '', (string)($_POST['pass'] ?? ''));
        $name = trim(preg_replace('/[\r\n]+/', ' ', (string)($_POST['name'] ?? '')));
        $host = trim((string)($_POST['host'] ?? ''));
        $port = (int)($_POST['port'] ?? 0);
        $secure = (string)($_POST['secure'] ?? 'tls');
        $check = isset($_POST['nocheck']) ? '0' : '1';

        if (!filter_var($user, FILTER_VALIDATE_EMAIL) || strlen($user) > 100) {
            $error = 'Type the full email address that sends the mail, for example codesmartaix@gmail.com.';
        } elseif ($pass === '' && !$savedPass) {
            $error = 'Type the App password of that Gmail (16 letters from Google).';
        } elseif ($pass !== '' && !preg_match('/^[\x21-\x7E]{1,100}$/', $pass)) {
            $error = 'The App password has letters and numbers only (spaces are fine, they are ignored). Copy it again from Google.';
        } elseif (mb_strlen($name) > 40) {
            $error = 'Keep the sender name under 40 letters.';
        } else {
            $gmail = (bool)preg_match('/@(gmail|googlemail)\.com$/i', $user);
            if ($host === '') {
                $host = 'smtp.gmail.com';
                if (!$gmail) {
                    $error = 'This address is not a Gmail. Open "Other mail server" below and type the host of your mail server.';
                }
            }
            if ($error === '' && !preg_match('/^[A-Za-z0-9.-]{3,100}$/', $host)) {
                $error = 'The mail server name is not valid. Gmail uses smtp.gmail.com.';
            }
            if ($port < 1 || $port > 65535) {
                $port = 587;
            }
            if (!in_array($secure, ['tls', 'ssl', 'none'], true)) {
                $secure = 'tls';
            }
        }

        if ($error === '') {
            if ($pass === '') {
                $pass = $cur['PASS']; // empty field = keep the saved password
            }
            $rows = [
                'mail_host' => $host,
                'mail_port' => (string)$port,
                'mail_secure' => $secure === 'none' ? '' : $secure,
                'mail_user' => $user,
                'mail_pass' => $pass,
                'mail_from_name' => $name !== '' ? $name : 'CodeSmartAi-X',
                'mail_check' => $check,
            ];
            $pdo->prepare("DELETE FROM settings WHERE k IN ('" . implode("','", mail_keys()) . "')")->execute();
            $ins = $pdo->prepare('INSERT INTO settings(k,v) VALUES(?,?)');
            foreach ($rows as $k => $v) {
                $ins->execute([$k, $v]);
            }
            mail_set(true);
            $wrong = strpos(login_note(['HOST' => $host, 'USER' => $user, 'PASS' => $pass]), 'not the shape') !== false;
            // version 47: log in to Gmail right away, so a wrong App password is found now (not when a student signs up)
            $bad = $wrong ? '' : mail_login_check();
            if ($bad !== '') {
                $_SESSION['email_bad'] = 'Saved, BUT IT DOES NOT WORK: ' . $bad; // shown in red below
            } else {
                flash('Saved.' . ($wrong ? ' But check the red warning below before you test.' : ' Gmail accepted the address and the App password, so codes can be emailed now. You can also send a test email below.'));
            }
            header('Location: '.BASE.'Admin/email.php');
            exit;
        }
    } elseif ($act === 'test') {
        $to = trim((string)($_POST['to'] ?? ''));
        $now = time();
        $_SESSION['mtest'] = array_values(array_filter($_SESSION['mtest'] ?? [], fn($t) => $t > $now - 600));
        if (!mail_ready()) {
            $testError = 'Save the email setup first.';
        } elseif (!filter_var($to, FILTER_VALIDATE_EMAIL) || strlen($to) > 100) {
            $testError = 'Type the email address that should receive the test.';
        } elseif (count($_SESSION['mtest']) >= 5) {
            $testError = 'That was 5 tests in a short time. Please wait 10 minutes.';
        } else {
            $_SESSION['mtest'][] = $now;
            $err = '';
            $text = "Hello,\n\nThis is a test from CodeSmartAi-X. If you can read this, the email setup works and your students will get their verification codes.\n";
            $html = mail_layout('Email setup works', '<p style="margin:0 0 12px;">Hello,</p><p style="margin:0 0 14px;">This is a test from CodeSmartAI-X. If you can read this, the email setup works. This is how students will see their verification code:</p>'
                . mail_code_box('123456') . mail_small('This sample code does not work anywhere.'), 'The email setup works.');
            if (send_mail($to, 'CodeSmartAi-X test email', $text, $err, $html)) {
                flash('Test email sent to ' . $to . '. Open that inbox (look in Spam too). If it arrived, everything works.');
                header('Location: '.BASE.'Admin/email.php');
                exit;
            }
            $testError = $err;
        }
    } elseif ($act === 'off') {
        $pdo->prepare("DELETE FROM settings WHERE k IN ('" . implode("','", mail_keys()) . "')")->execute();
        mail_set(true);
        flash('The saved email setup was removed.' . (mail_ready() ? ' The settings of config.php are used.' : ' Email is OFF again: sign-up, password and admin codes cannot be emailed until you save a Gmail below.'));
        header('Location: '.BASE.'Admin/email.php');
        exit;
    }
    $cur = mail_set();
}

head('Email setup');
flash();
if (!empty($_SESSION['email_bad'])) { echo '<p class="card err">' . e($_SESSION['email_bad']) . '</p>'; unset($_SESSION['email_bad']); }

// ---- the state ----
if (mail_ready()) {
    $who = $cur['USER'] !== '' ? $cur['USER'] : $cur['HOST'];
    echo '<p class="card ok"><b>Email is ON.</b> The verification code and the forgot-password code are sent by email from <b>' . e($who) . '</b>'
        . ($cur['SOURCE'] === 'config' ? ' (the MAIL_ settings of config.php)' : '') . '.'
        . (strpos(login_note($cur), 'not the shape') === false ? '<br><small>' . login_note($cur) . '</small>' : '') . '</p>';
    if (strpos(login_note($cur), 'not the shape') !== false) {
        echo '<p class="err">Check the password. ' . login_note($cur) . '</p>';
    }
} else {
    echo '<p class="card"><b>Email is OFF.</b> Nothing is emailed, so new students cannot get their sign-up code, nobody can reset or change a password, and the admin step-2 code is shown on the screen. Fill in the form below (Gmail + App password) to switch email on.</p>';
}
if ($error) {
    echo '<p class="err">' . e($error) . '</p>';
}
?>
<div class="card" style="max-width:none">
<h2>How to get the Gmail ready (5 minutes, once)</h2>
<ol>
<li>Make a new Gmail for the system, for example <b>codesmartaix@gmail.com</b>. Students will see this address as the sender.</li>
<li>Log in to that Gmail, open <b>Google Account &rarr; Security</b> and turn on <b>2-Step Verification</b>.</li>
<li>Open <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener noreferrer">myaccount.google.com/apppasswords</a>, make an App password named CodeSmart and copy the 16 letters.</li>
<li>Type the Gmail and the App password below, press <b>Save</b>, then send a test email to yourself.</li>
</ol>
<p><small>Use the App password, never the normal Gmail password. A normal Gmail can send about 500 emails a day, which is enough for a class.</small></p>
</div>

<form method="post" class="card" style="max-width:none">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="act" value="save">
<h2>Gmail account</h2>
<label>Gmail address that sends the mail<input type="email" name="user" maxlength="100" required autocomplete="off" value="<?= e($_POST['user'] ?? ($cur['SOURCE'] === 'page' ? $cur['USER'] : 'codesmartaix@gmail.com')) ?>" placeholder="codesmartaix@gmail.com"></label>
<label>App password<span class="pw"><input type="password" name="pass" maxlength="100" autocomplete="new-password" <?= $savedPass ? '' : 'required' ?> placeholder="<?= $savedPass ? 'saved - leave empty to keep it' : 'abcd efgh ijkl mnop' ?>"><button type="button" class="eye" aria-label="Hold to show the password" title="Hold to show the password"><?= ico('eye') ?></button></span></label>
<label>Sender name students see<input name="name" maxlength="40" value="<?= e($_POST['name'] ?? ($cur['SOURCE'] === 'page' ? $cur['FROM_NAME'] : 'CodeSmartAi-X')) ?>"></label>
<details<?= ($cur['SOURCE'] === 'page' && ($cur['HOST'] !== 'smtp.gmail.com' || !$cur['CHECK'])) || isset($_POST['host']) && $error ? ' open' : '' ?>>
<summary>Other mail server (not Gmail), or a certificate error</summary>
<label>Mail server (host)<input name="host" maxlength="100" value="<?= e($_POST['host'] ?? ($cur['SOURCE'] === 'page' && $cur['HOST'] !== 'smtp.gmail.com' ? $cur['HOST'] : '')) ?>" placeholder="empty = smtp.gmail.com for a Gmail"></label>
<label>Port<input type="number" name="port" min="1" max="65535" value="<?= e($_POST['port'] ?? ($cur['SOURCE'] === 'page' ? $cur['PORT'] : 587)) ?>"></label>
<?php $sec = $_POST['secure'] ?? ($cur['SOURCE'] === 'page' ? ($cur['SECURE'] === '' ? 'none' : $cur['SECURE']) : 'tls'); ?>
<label>Connection<select name="secure">
<option value="tls"<?= $sec === 'tls' ? ' selected' : '' ?>>TLS (port 587, Gmail)</option>
<option value="ssl"<?= $sec === 'ssl' ? ' selected' : '' ?>>SSL (port 465)</option>
<option value="none"<?= $sec === 'none' ? ' selected' : '' ?>>No encryption (only a mail server on this computer)</option>
</select></label>
<label class="chk"><input type="checkbox" name="nocheck" value="1"<?= ($cur['SOURCE'] === 'page' && !$cur['CHECK']) || isset($_POST['nocheck']) ? ' checked' : '' ?>> Do not check the certificate of the mail server (only if the test says <i>certificate verify failed</i> on your own computer)</label>
</details>
<button>Save</button>
</form>

<?php if (mail_ready()) { ?>
<form method="post" class="card" style="max-width:none">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="act" value="test">
<h2>Send a test email</h2>
<?php if ($testError) { ?>
<p class="err">The email was not sent. The mail server said: <b><?= e($testError) ?></b></p>
<?php if (mail_hint($testError)) { echo '<p class="card">' . e(mail_hint($testError)) . '</p>'; } ?>
<?php if (login_note($cur) !== '') { echo '<p class="card">' . login_note($cur) . '</p>'; } ?>
<?php } ?>
<label>Send it to (your own email)<input type="email" name="to" maxlength="100" required value="<?= e($_POST['to'] ?? '') ?>" placeholder="your.email@gmail.com"></label>
<button>Send test email</button>
</form>
<?php } ?>

<?php if ($cur['SOURCE'] === 'page') { ?>
<form method="post" class="inl" onsubmit="return confirm('Remove the saved email setup?')">
<input type="hidden" name="t" value="<?= csrf() ?>">
<input type="hidden" name="act" value="off">
<button class="alt">Remove the saved email setup</button> <small>The site goes back to the MAIL_ settings of config.php (or email OFF when those are empty).</small>
</form>
<?php } ?>
<p><small>The App password is saved in your database (table settings) and is never shown again on this page. Keep your database backups private.</small></p>
<?php foot();
