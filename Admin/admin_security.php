<?php
// ADMIN SECURITY (version 43): how the admin account is protected, the sign-in log, and changing the admin login ID / email.
// If the admin password or the admin name ever leaks: change the password (this logs out every other device at once),
// change the login ID here, and look at the sign-in log for logins you do not know.
require __DIR__.'/../config.php';
require __DIR__.'/../Logging/mailer.php';
$u = need('admin');
$s = $pdo->prepare('SELECT * FROM users WHERE id=?');
$s->execute([$u['id']]);
$me = $s->fetch();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $id = trim((string)($_POST['sid'] ?? ''));
    $em = trim((string)($_POST['email'] ?? ''));
    if (!password_verify((string)($_POST['current'] ?? ''), $me['password_hash'])) {
        admin_log('wrong_password');
        $err = 'Your current password is not correct.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $id)) {
        $err = 'The login ID can have 3 to 30 letters, numbers, dot, dash or underscore.';
    } elseif (!filter_var($em, FILTER_VALIDATE_EMAIL) || strlen($em) > 100) {
        $err = 'Type a real email address.';
    } else {
        try {
            $pdo->prepare('UPDATE users SET student_id=?, email=? WHERE id=?')->execute([$id, $em, $u['id']]);
            $_SESSION['user']['sid'] = $id;
            admin_log('changed_login');
            flash('Saved. From now on log in with the ID "' . $id . '". The step-2 code goes to ' . $em . '.');
            header('Location: '.BASE.'Admin/admin_security.php');
            exit;
        } catch (PDOException $x) {
            $err = 'That login ID or email is already used by another account.';
        }
    }
}
head('Admin security');
flash();
if ($err) {
    echo '<p class="err">' . e($err) . '</p>';
}
$two = ADMIN_TWO_STEP && admin_email_real($me['email']);
$checks = [
    [$two, 'Step-2 code by email', $two ? 'After the password, a 6-digit code is sent to ' . e(mask_email_admin($me['email'])) . '. A leaked password alone cannot open the admin area.' : (ADMIN_TWO_STEP ? 'Turned on, but the code cannot be emailed yet (Email is not set up, or the admin email below is still an example). Until then the code is shown on the screen.' : 'Turned off (ADMIN_TWO_STEP in config.php).')],
    [strcasecmp((string)$me['student_id'], 'admin') !== 0, 'Login ID is not "admin"', strcasecmp((string)$me['student_id'], 'admin') !== 0 ? 'Your login ID is not the easy-to-guess "admin".' : 'Your login ID is still "admin". Everybody can guess it: change it below.'],
    [!password_verify(ADMIN_START_PASSWORD, $me['password_hash']), 'Own password', 'The first password from config.php was changed.'],
    [true, 'Wrong passwords are locked', 'After 5 wrong passwords the account is locked for 15 minutes. Every try is written in the sign-in log below.'],
    [true, 'Logged out when idle', 'The admin is logged out after ' . ADMIN_IDLE_MINUTES . ' minutes without using the site (ADMIN_IDLE_MINUTES).'],
    [trim(ADMIN_ALLOWED_IPS) !== '', 'Only from school computers', trim(ADMIN_ALLOWED_IPS) !== '' ? 'The admin can only log in from: ' . e(ADMIN_ALLOWED_IPS) . '.' : 'Optional: put the school office computer address(es) in ADMIN_ALLOWED_IPS in config.php so nobody can log in as admin from home or a phone.'],
];
function mask_email_admin($e) { $p = explode('@', (string)$e); return count($p) === 2 ? mb_substr($p[0], 0, 2) . '***@' . $p[1] : $e; }
echo '<div class="card" style="max-width:none"><h2 style="margin-top:0">How the admin account is protected</h2><ul class="sec">';
foreach ($checks as $c) {
    echo '<li><span class="st ' . ($c[0] ? 'st-done">ON' : 'st-missed">CHECK') . '</span> <b>' . $c[1] . '</b><br><small>' . $c[2] . '</small></li>';
}
echo '</ul><p><small><b>If you think the password or the admin name leaked:</b> 1) <a href="Logging/password.php">change the password</a> now (every other device is logged out at once), 2) change the login ID below, 3) look at the sign-in log for times and computers you do not know.</small></p></div>';

echo '<h2>Login ID and email of the admin</h2><form method="post" class="card" style="max-width:none"><input type="hidden" name="t" value="' . csrf() . '">
<div class="two"><label>Login ID (type it in the ID box when you log in)<input name="sid" required maxlength="30" pattern="[A-Za-z0-9._\-]{3,30}" value="' . e($_POST['sid'] ?? $me['student_id']) . '"></label>
<label>Admin email (the step-2 code is sent here)<input type="email" name="email" required maxlength="100" value="' . e($_POST['email'] ?? $me['email']) . '"></label></div>
' . pw('Your current password (to confirm it is you)', 1, 'current') . '<button>Save</button> <small>Choose an ID that is hard to guess, for example wcc-cit-head-7.</small></form>';

$names = ['login' => 'Logged in', 'password_ok' => 'Password right, waiting for the code', 'wrong_password' => 'WRONG password', 'wrong_code' => 'WRONG code', 'blocked_computer' => 'BLOCKED: not an allowed computer', 'idle_logout' => 'Logged out (idle)', 'changed_login' => 'Login ID / email changed'];
$rows = $pdo->query('SELECT event,ip,agent,at FROM admin_log ORDER BY id DESC LIMIT 30')->fetchAll();
echo '<h2>Admin sign-in log</h2>';
if (!$rows) {
    echo '<p><small>Nothing yet.</small></p>';
} else {
    $short = function ($a) { foreach (['Edg' => 'Edge', 'OPR' => 'Opera', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari'] as $k => $v) { if (stripos($a, $k) !== false) { $b = $v; break; } } $os = stripos($a, 'Windows') !== false ? 'Windows' : (stripos($a, 'Android') !== false ? 'Android' : (stripos($a, 'iPhone') !== false || stripos($a, 'iPad') !== false ? 'iPhone / iPad' : (stripos($a, 'Mac') !== false ? 'Mac' : (stripos($a, 'Linux') !== false ? 'Linux' : '')))); return trim(($b ?? 'Browser') . ' on ' . ($os ?: 'unknown')); };
    table(['When', 'What happened', 'Computer (IP)', 'Browser'], array_map(fn($r) => [e(when($r['at'])), preg_match('/wrong|blocked/', $r['event']) ? '<span class="st st-missed">' . e($names[$r['event']] ?? $r['event']) . '</span>' : e($names[$r['event']] ?? $r['event']), e($r['ip']), e($short($r['agent']))], $rows));
}
foot();
