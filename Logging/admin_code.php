<?php
// STEP 2 OF THE ADMIN LOGIN (version 43). After the right admin password, a 6-digit code goes to the admin email.
// Only the person who can open that inbox can finish the login, so a leaked password (or a guessed admin name) is not enough.
// Wrong codes are limited (5), the code works for 30 minutes, and every try is written in the admin sign-in log.
require __DIR__.'/../config.php';
require __DIR__.'/security.php';
$w = $_SESSION['admin_wait'] ?? null;
if (!$w || time() - (int)$w['at'] > 1800) {
    unset($_SESSION['admin_wait']);
    header('Location: '.BASE.'Logging/login.php?open=1');
    exit;
}
$s = $pdo->prepare("SELECT * FROM users WHERE id=? AND role='admin'");
$s->execute([(int)$w['id']]);
$adm = $s->fetch();
if (!$adm) {
    unset($_SESSION['admin_wait']);
    header('Location: '.BASE.'Logging/login.php?open=1');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    if (($_POST['a'] ?? '') === 'resend') {
        $sent = admin_email_real($adm['email']) ? send_verify_code($adm['id'], $adm['email'], $adm['full_name'], 'admin') : '';
        if (!admin_email_real($adm['email'])) { $_SESSION['verify_test_code'] = make_verify_code($adm['id']); }
        flash($sent !== '' ? $sent : (!empty($_SESSION['verify_test_code']) ? 'A new code is shown below (it could not be emailed).' : 'A new code was sent.'));
        header('Location: '.BASE.'Logging/admin_code.php');
        exit;
    }
    $error = check_verify_code($adm['id'], $_POST['code'] ?? '');
    if ($error === '') {
        $pdo->prepare('DELETE FROM email_codes WHERE user_id=?')->execute([(int)$adm['id']]);
        clear_wrong_tries('vcode', 'u' . (int)$adm['id']);
        clear_wrong_tries('vsend', 'u' . (int)$adm['id']);
        unset($_SESSION['admin_wait'], $_SESSION['verify_test_code']);
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $adm['id'], 'name' => $adm['full_name'], 'role' => 'admin', 'sid' => $adm['student_id'], 'pwv' => pwv($adm['password_hash'])];
        $_SESSION['seen'] = time();
        admin_log('login');
        header('Location: '.BASE.'Admin/admin.php');
        exit;
    }
    admin_log('wrong_code');
}
head('Admin login: step 2');
flash();
if ($error) {
    echo '<p class="err">' . e($error) . '</p>';
}
echo '<form method="post" class="card auth"><input type="hidden" name="t" value="' . csrf() . '"><input type="hidden" name="a" value="check">';
if (!empty($_SESSION['verify_test_code'])) {
    // version 47: the code sits on its own line (it used to be inside the sentence and covered the text), and the page no longer
    // says "sent to ..." when nothing was emailed
    $why = !empty($_SESSION['mail_fail']) ? 'The code could not be emailed: ' . e($_SESSION['mail_fail'])
         : (mail_ready() ? 'The admin email is still the example address, so the code could not be emailed.' : 'Email sending is not set up yet, so the code could not be emailed.');
    echo '<p>The password was right. ' . $why . ' Your code is:</p>
<p class="codebox">' . e($_SESSION['verify_test_code']) . '</p>
<p class="testnote">Anyone who knows the admin password can see this code. Set up <b>Admin &gt; Email</b> (Gmail and its App password) so the code is emailed to ' . e(mask_email($adm['email'])) . ' instead.</p>';
} else {
    echo '<p>The password was right. To finish, type the 6-digit code that was sent to <b>' . e(mask_email($adm['email'])) . '</b>. It works for 30 minutes. Look in the Spam folder too.</p>';
}
echo '<label>Code<input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus></label><button>Log in</button></form>
<form method="post" class="auth" style="margin-top:.6rem"><input type="hidden" name="t" value="' . csrf() . '"><input type="hidden" name="a" value="resend"><button class="alt">Send a new code</button> <a href="Logging/logout.php" onclick="event.preventDefault();this.closest(\'form\').nextElementSibling.submit()">Cancel</a></form>
<form method="post" action="Logging/logout.php" hidden><input type="hidden" name="t" value="' . csrf() . '"></form>';
foot();
