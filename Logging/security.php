<?php
// Small helper functions for login safety, password rules, the reset code and the email verification code.
// Used by Logging/login.php, Logging/register.php, Logging/verify.php, Logging/password.php and Logging/reset.php
require_once __DIR__ . '/mailer.php';

// ---------- the computer the visitor is using ----------
function my_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '0';
}

// ---------- counting wrong tries (table login_attempts) ----------
// kind = what was tried (login, ip, tcode, scode, join, pw, reset, vcode, vsend, vip), who = the account or the computer

// version 48: limits for ONE computer or network (IP address). A whole class can sit behind one school internet connection
// (one IP address for 40 students), so these are high. The limits for ONE account or ONE email stay strict (5 wrong passwords,
// 5 wrong codes, 3 emailed codes in 15 minutes).
const IP_LOGIN_FAILS = 300; // wrong passwords from one network in 15 minutes
const IP_CODES = 100;       // codes emailed for sign-ups / password resets from one network in 15 minutes
const IP_RESET_FAILS = 100; // wrong Student ID + email pairs on "Forgot password" from one network in 15 minutes
const IP_CODE_FAILS = 50;   // wrong class codes / teacher codes from one network in 15 minutes

// how many wrong tries there were in the last 15 minutes
function wrong_tries($kind, $who) {
    global $pdo;
    $s = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE kind=? AND who=? AND tried_at > NOW() - INTERVAL 15 MINUTE');
    $s->execute([$kind, $who]);
    return (int)$s->fetchColumn();
}

// write down one more wrong try
function add_wrong_try($kind, $who) {
    global $pdo;
    $s = $pdo->prepare('INSERT INTO login_attempts(kind, who) VALUES(?, ?)');
    $s->execute([$kind, $who]);
    // clean up tries that are older than one day. Version 48: only now and then (1 time in 50), because it reads the whole table,
    // and many students logging in at the same moment should not wait for each other
    if (random_int(1, 50) === 1) {
        try { $pdo->exec('DELETE FROM login_attempts WHERE tried_at < NOW() - INTERVAL 1 DAY'); } catch (Throwable $x) {}
    }
}

// forget the wrong tries (after a good login or a new password)
function clear_wrong_tries($kind, $who) {
    global $pdo;
    // version 48: look first (a plain read locks nothing). Most logins have no wrong tries, so nothing is deleted and
    // 40 students who log in at the same moment do not lock the table for each other
    $s = $pdo->prepare('SELECT 1 FROM login_attempts WHERE kind=? AND who=? LIMIT 1');
    $s->execute([$kind, $who]);
    if (!$s->fetchColumn()) {
        return;
    }
    $s = $pdo->prepare('DELETE FROM login_attempts WHERE kind=? AND who=?');
    $s->execute([$kind, $who]);
}

// ---------- password rules ----------
// returns a message when the password is not good, or '' when it is fine
function password_problem($password, $student_id = '', $email = '') {
    $lower = strtolower($password);

    if (strlen($password) < 8 || strlen($password) > 72) {
        return 'Your password must be 8 to 72 characters long.';
    }
    $easy = ['12345678', '123456789', '1234567890', 'password', 'password1', 'password123',
             'qwerty123', 'qwertyuiop', '11111111', '00000000', 'iloveyou', 'abc12345', 'admin123'];
    if (in_array($lower, $easy, true)) {
        return 'That password is too easy to guess. Pick something different.';
    }
    if ($student_id != '' && $lower == strtolower($student_id)) {
        return 'Your password cannot be the same as your Student ID.';
    }
    if ($email != '' && $lower == strtolower($email)) {
        return 'Your password cannot be the same as your email.';
    }
    if (preg_match('/^(.)\1+$/', $password)) {
        return 'Do not use the same character over and over.';
    }
    return '';
}

// save a new password for this user id
function save_new_password($user_id, $new_password) {
    global $pdo;
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $s = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
    $s->execute([$hash, $user_id]);
    // the account is not locked any more
    clear_wrong_tries('login', 'u' . $user_id);
    clear_wrong_tries('pw', (string)$user_id);
}

// ---------- the reset code ("Forgot password") ----------
// The student types the Student ID and the email of the account. If they match,
// the system makes a 6-digit code and keeps it in the session. The code is EMAILED to the account when email is
// set up (Admin > Email). It is never shown on the page, except SHOW_CODES_ON_SCREEN for testing.

function make_reset_code($user_id) {
    $code = (string)random_int(100000, 999999);
    $_SESSION['reset_user'] = $user_id;
    $_SESSION['reset_code'] = $code;
    $_SESSION['reset_time'] = time();
    $_SESSION['reset_tries'] = 0;
    return $code;
}

// is there a reset code that can still be used? (15 minutes)
function reset_code_is_alive() {
    if (empty($_SESSION['reset_code'])) {
        return false;
    }
    if (time() - ($_SESSION['reset_time'] ?? 0) > 900) {
        forget_reset_code();
        return false;
    }
    return true;
}

function forget_reset_code() {
    unset($_SESSION['reset_user'], $_SESSION['reset_code'], $_SESSION['reset_time'], $_SESSION['reset_tries'], $_SESSION['reset_mailed']);
}

// checks the code the student typed. Returns '' when it is right, or a message.
function check_reset_code($typed) {
    if (!reset_code_is_alive()) {
        return 'The code is not valid any more. Please start again.';
    }
    if ($_SESSION['reset_tries'] >= 5) {
        forget_reset_code();
        return 'Too many wrong codes. Please start again.';
    }
    if (trim($typed) !== $_SESSION['reset_code']) {
        $_SESSION['reset_tries']++;
        return 'That code is not correct.';
    }
    return '';
}

// ---------- email verification ("is this email really yours?") ----------
// A new account cannot log in until its owner types the 6-digit code that was sent to the email.
// users.email_verified is 0 until then. Accounts made before this check existed are counted as verified.
// The code is kept (hashed) in the table email_codes and works for 30 minutes, 5 wrong tries.

// ma***@gmail.com : the email without showing all of it
function mask_email($email) {
    $p = explode('@', (string)$email, 2);
    if (count($p) !== 2) {
        return '***';
    }
    return mb_substr($p[0], 0, 2) . '***@' . $p[1];
}

// is there a code that can still be used?
function verify_code_alive($uid) {
    global $pdo;
    $s = $pdo->prepare('SELECT 1 FROM email_codes WHERE user_id=? AND sent_at > NOW() - INTERVAL 30 MINUTE');
    $s->execute([(int)$uid]);
    return (bool)$s->fetchColumn();
}

// makes a new code for this account (an older code stops working). Returns the code.
function make_verify_code($uid) {
    global $pdo;
    $uid = (int)$uid;
    $code = (string)random_int(100000, 999999);
    $pdo->prepare('DELETE FROM email_codes WHERE user_id=?')->execute([$uid]);
    $pdo->prepare('INSERT INTO email_codes(user_id,code_hash) VALUES(?,?)')->execute([$uid, hash('sha256', $uid . ':' . $code)]);
    clear_wrong_tries('vcode', 'u' . $uid);
    return $code;
}

// too many codes asked? (3 per account and IP_CODES per network in 15 minutes: this also stops strangers from using the site to flood an inbox)
function verify_send_blocked($uid) {
    if (wrong_tries('vsend', 'u' . (int)$uid) >= 3 || wrong_tries('vip', my_ip()) >= IP_CODES) {
        return 'Too many codes were asked for. Please wait 15 minutes, then press "Send a new code" again.';
    }
    return '';
}

// makes a code and emails it. Returns '' when all is fine, or a message for the visitor.
// The code is only emailed. When email is not set up yet, nothing is sent and the visitor is told to ask the admin
// (SHOW_CODES_ON_SCREEN=true in config.php shows it on the page instead, only for testing on your own computer).
// $kind 'admin' = the admin's login code (step 2).
function send_verify_code($uid, $email, $name = '', $kind = 'signup') {
    $blocked = verify_send_blocked($uid);
    if ($blocked !== '') {
        return $blocked;
    }
    unset($_SESSION['verify_test_code']);
    if (!mail_ready() && !SHOW_CODES_ON_SCREEN) {
        return 'We cannot email your code yet because email sending is not set up. Please tell your teacher or the admin (Admin > Email), then press "Send a new code".';
    }
    $code = make_verify_code($uid);
    add_wrong_try('vsend', 'u' . (int)$uid);
    add_wrong_try('vip', my_ip());
    if (!mail_ready()) {
        $_SESSION['verify_test_code'] = $code; // SHOW_CODES_ON_SCREEN: testing only
        return '';
    }
    $name = trim(preg_replace('/[\r\n]+/', ' ', (string)$name));
    $m = mail_code_message($kind, $name, $code);
    unset($_SESSION['mail_fail']);
    if (!send_mail($email, $m['subject'], $m['text'], $err, $m['html'])) {
        error_log('CodeSmartAi-X: could not send the verification email: ' . $err);
        if ($kind === 'admin') {
            // version 47: never lock the admin out. When the email fails (for example a wrong App password in Admin > Email),
            // the code is shown on the screen this time, with the reason, so the admin can log in and fix the email setup.
            $_SESSION['verify_test_code'] = $code;
            $_SESSION['mail_fail'] = mail_problem($err);
            return '';
        }
        return 'We could not send the email: ' . mail_problem($err);
    }
    return '';
}

// checks the code the visitor typed. Returns '' when it is right, or a message.
function check_verify_code($uid, $typed) {
    global $pdo;
    $uid = (int)$uid;
    $s = $pdo->prepare('SELECT code_hash FROM email_codes WHERE user_id=? AND sent_at > NOW() - INTERVAL 30 MINUTE');
    $s->execute([$uid]);
    $hash = $s->fetchColumn();
    if (!$hash) {
        return 'There is no valid code. It may have expired. Press "Send a new code".';
    }
    if (wrong_tries('vcode', 'u' . $uid) >= 5) {
        $pdo->prepare('DELETE FROM email_codes WHERE user_id=?')->execute([$uid]);
        return 'Too many wrong codes. Press "Send a new code".';
    }
    $typed = trim((string)$typed);
    if (!preg_match('/^[0-9]{6}$/', $typed) || !hash_equals($hash, hash('sha256', $uid . ':' . $typed))) {
        add_wrong_try('vcode', 'u' . $uid);
        return 'That code is not correct.';
    }
    return '';
}

// the email is proven: the account can log in now
function mark_verified($uid) {
    global $pdo;
    $pdo->prepare('UPDATE users SET email_verified=1 WHERE id=?')->execute([(int)$uid]);
    $pdo->prepare('DELETE FROM email_codes WHERE user_id=?')->execute([(int)$uid]);
    clear_wrong_tries('vcode', 'u' . (int)$uid);
    clear_wrong_tries('vsend', 'u' . (int)$uid);
}

// ---------- version 47: a new account is made only AFTER the emailed code is typed ----------
// The sign-up form waits in the session (the password is already hashed) together with a 6-digit code (hashed too).
// NOTHING is saved in the users table until the right code is typed, so a sign-up without the code leaves no account.
// The code works for 30 minutes and 5 wrong tries; a new code can be asked for once a minute (3 per email, IP_CODES per network in 15 minutes).
function signup_pending() {
    $p = $_SESSION['signup'] ?? null;
    if (!is_array($p)) {
        return null;
    }
    if (time() - (int)$p['time'] > 1800) {
        unset($_SESSION['signup']);
        return null;
    }
    return $p;
}

// is this Student ID or email used by an account? (an unfinished sign-up of an older version does not count: it is removed)
function signup_taken($sid, $email) {
    global $pdo;
    $pdo->prepare('DELETE FROM users WHERE email_verified=0 AND role<>\'admin\' AND (student_id=? OR email=?)')->execute([$sid, $email]);
    $s = $pdo->prepare('SELECT 1 FROM users WHERE student_id=? OR email=?');
    $s->execute([$sid, $email]);
    return (bool)$s->fetchColumn() || strcasecmp($sid, ADMIN_ID) === 0 || strcasecmp($email, ADMIN_EMAIL) === 0;
}

// makes a new code for the waiting sign-up $p and emails it. Returns '' when it was sent, or a message.
function signup_send_code(array &$p) {
    $who = 'e' . substr(sha1(strtolower($p['email'])), 0, 20);
    if (wrong_tries('vsend', $who) >= 3 || wrong_tries('vip', my_ip()) >= IP_CODES) {
        return 'Too many codes were asked for. Please wait 15 minutes, then try again.';
    }
    if (!mail_ready() && !SHOW_CODES_ON_SCREEN) {
        return 'We cannot email your code yet because email sending is not set up, so your account cannot be made yet. Please tell your teacher or the admin (Admin > Email).';
    }
    $code = (string)random_int(100000, 999999);
    add_wrong_try('vsend', $who);
    add_wrong_try('vip', my_ip());
    $p['code'] = hash('sha256', $code);
    $p['time'] = time();
    $p['sent'] = time();
    $p['tries'] = 0;
    unset($p['show']);
    if (!mail_ready()) {
        $p['show'] = $code; // SHOW_CODES_ON_SCREEN: testing only
        return '';
    }
    $m = mail_code_message('signup', $p['name'], $code);
    if (!send_mail($p['email'], $m['subject'], $m['text'], $err, $m['html'])) {
        error_log('CodeSmartAi-X: could not send the sign-up email: ' . $err);
        return preg_match('/\b535\b|BadCredentials|not accepted/i', $err)
            ? 'We could not send your code because the school\'s email setup is not working (Gmail did not accept it). No account was made. Please tell your teacher or the admin.'
            : 'We could not send the email right now: ' . mail_problem($err) . ' No account was made yet. Please try again.';
    }
    return '';
}
