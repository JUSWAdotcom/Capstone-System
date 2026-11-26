<?php
require __DIR__.'/../config.php';
require __DIR__.'/security.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    // version 47: after a fresh import of database/capstone_db.sql there is no admin yet. Make it now, even if this browser
    // still has a session from before the import (then the automatic check in config.php does not run again).
    try { ensure_admin($pdo); } catch (Throwable $x) {}
    $typed = trim($_POST['sid'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = my_ip();

    // find the account by Student ID or by email
    $s = $pdo->prepare('SELECT * FROM users WHERE student_id=? OR email=? LIMIT 1');
    $s->execute([$typed, $typed]);
    $user = $s->fetch();

    // wrong tries are counted for the account (or for the typed name if there is no such account)
    if ($user) {
        $who = 'u' . $user['id'];
    } else {
        $who = strtolower(substr($typed, 0, 90));
    }

    if (wrong_tries('login', $who) >= 5 || wrong_tries('ip', $ip) >= IP_LOGIN_FAILS) {
        $error = 'Too many wrong tries. Please wait 15 minutes, or press "Forgot password?" below.';
    } else {
        // we always check one password, so the page is equally slow when the account does not exist
        $fake_hash = '$2y$10$qDvDo8FpnWFFRgttZr0JSOUum8iWuHZTQ/RMzQjxQ6rtkDxjZi34e';
        if ($user) {
            $good = password_verify($password, $user['password_hash']);
        } else {
            password_verify($password, $fake_hash);
            $good = false;
        }

        $unfinished = false;
        if ($good && isset($user['email_verified']) && (int)$user['email_verified'] === 0 && $user['role'] !== 'admin') {
            // version 47: accounts are made only after the email code is typed. This one is an unfinished sign-up of an older
            // version (the code was never typed): it has no data, so it is removed and the person signs up again.
            $pdo->prepare('DELETE FROM users WHERE id=? AND email_verified=0')->execute([(int)$user['id']]);
            $user = null;
            $good = false;
            $unfinished = true;
        }

        $isAdmin = $user && $user['role'] === 'admin';
        if ($isAdmin && !admin_ip_ok()) { // ADMIN_ALLOWED_IPS: the admin may only log in from the school's own computers
            admin_log('blocked_computer');
            $good = false;
        }
        if ($good && $isAdmin && ADMIN_TWO_STEP) {
            // step 2 for the admin: a 6-digit code is sent to the admin email. The password alone does not open the admin area.
            clear_wrong_tries('login', $who);
            admin_log('password_ok');
            session_regenerate_id(true);
            $_SESSION['admin_wait'] = ['id' => (int)$user['id'], 'at' => time()];
            if (!admin_email_real($user['email'])) { // the admin email is still the example one: the code cannot be emailed, it is shown (test mode)
                $_SESSION['verify_test_code'] = make_verify_code($user['id']);
            } else {
                $sent = send_verify_code($user['id'], $user['email'], $user['full_name'], 'admin');
                if ($sent !== '') { flash($sent); }
            }
            header('Location: '.BASE.'Logging/admin_code.php');
            exit;
        }
        if ($isAdmin && !$good) { admin_log('wrong_password'); }
        if ($good && (int)($user['approved'] ?? 1) === 1) {
            if ($isAdmin) { admin_log('login'); }
            clear_wrong_tries('login', $who);
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'role' => $user['role'],
                'sid' => $user['student_id'],
                'pwv' => pwv($user['password_hash'])
            ];
            header('Location: '.BASE.($user['role'] === 'admin' ? 'Admin/admin.php' : 'Dashboard/dashboard.php'));
            exit;
        }

        if ($good) { // right password, but nobody approved the account yet
            clear_wrong_tries('login', $who);
            $error = $user['role'] === 'teacher'
                ? 'Your teacher account is waiting for the admin to approve it. Please try again later.'
                : 'Your account is waiting for approval. Your teacher or the admin will check it soon, then you can log in.';
        } elseif ($unfinished) {
            clear_wrong_tries('login', $who);
            $error = 'That sign-up was never finished (the email code was not typed), so there is no account. Please sign up again.';
        } else {
            add_wrong_try('login', $who);
            add_wrong_try('ip', $ip);
            $error = 'ID or password is incorrect.';
        }
    }
}


// ---------- the main page ----------
// The log in form is in a pop-up. It opens by itself when there is a message to show (wrong password, account created...)
// or when the address has ?open=1 (this is also what the "Log in" links on the other pages use).
$in = isset($_SESSION['user']);
$msg = isset($_GET['ok']) || isset($_GET['reset']) || isset($_GET['verified']) || isset($_GET['idle']);
$open = !$in && ($error !== '' || $msg || isset($_GET['open']));

// the title: every letter is its own <span> so it can fall and glow one after the other
function letters($text, $start) {
    $h = '<span class="word' . ($start > 0 ? ' ai' : '') . '" aria-hidden="true">';
    foreach (str_split($text) as $k => $c) {
        $h .= '<span class="ch" style="--i:' . ($start + $k) . '">' . e($c) . '</span>';
    }
    return $h . '</span>';
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><base href="<?= e(BASE) ?>"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CodeSmartAi-X | Learn. Practice. Code.</title>
<meta name="description" content="CodeSmartAi-X is a learning website for BSIT students and teachers: lessons, challenges, a code playground, an AI assistant, quizzes and code exams.">
<script>try{var d=document.documentElement;d.dataset.theme=localStorage.theme||"dark"}catch(e){}</script>
<link rel="stylesheet" href="assets/style.css?v=48.1"><link rel="stylesheet" href="assets/landing.css?v=48.1"></head>
<body class="landing">
<canvas id="rain" aria-hidden="true"></canvas>
<header><a class="brand" href="index.php"><img src="assets/img/logo.svg" alt=""><b>CodeSmart<span>Ai</span>-X</b></a>
<nav>
<button type="button" id="themebtn" class="themebtn" aria-label="Light or dark mode" title="Light or dark mode">☀️</button>
<?php if ($in): ?>
<a class="btn" href="Dashboard/dashboard.php">My dashboard</a>
<?php else: ?>
<a class="nolink" href="Logging/register.php">Sign up</a>
<a class="btn" href="Logging/login.php?open=1" data-open-login>Log in</a>
<?php endif; ?>
</nav></header>

<main class="hero">
<p class="eyebrow">Welcome to</p>
<h1 class="title" aria-label="CodeSmart AI-X"><?= letters('CODESMART', 0) ?><?= letters('AI-X', 9) ?></h1>
<div class="line"></div>
<p class="tag">Learn. Practice. Code.</p>
<p class="typed" aria-hidden="true"><span id="typed">PHP · C++ · C# · Java · HTML / CSS · JavaScript · Python</span><i></i></p>

<p class="intro">CodeSmartAi-X is a learning website for BSIT students and their teachers. Students study lessons and fix
buggy code in six programming languages, write their own code (Java too) in an editor like Visual Studio Code, ask the AI Assistant when they are stuck,
and take quizzes and timed coding exams. Teachers make the quizzes and exams, grade them, and follow every student's
progress in one place.</p>
<ul class="feats">
<li>📘 Lessons</li><li>🐞 Challenges</li><li>💻 Playground</li><li>🤖 AI Assistant</li><li>📝 Quizzes</li><li>⏱ Code exams</li>
</ul>
<div class="cta">
<?php if ($in): ?>
<a class="btn" href="Dashboard/dashboard.php">Go to my dashboard</a>
<?php else: ?>
<a class="btn" href="Logging/login.php?open=1" data-open-login>Log in</a>
<a class="btn alt" href="Logging/register.php">Create an account</a>
<?php endif; ?>
</div>
</main>

<?php if (!$in): ?>
<div class="lmodal<?= $open ? ' open' : '' ?>" id="lmodal" role="dialog" aria-modal="true" aria-labelledby="ltitle">
<div class="lbox">
<a class="lclose" href="Logging/login.php" aria-label="Close">×</a>
<img class="mlogo" src="assets/img/logo.svg" alt="">
<h2 id="ltitle">Log in</h2>
<?php
if ($error) { echo '<p class="err">' . e($error) . '</p>'; }
if (isset($_GET['ok'])) { echo '<p class="ok">Account created. Log in with your Student ID.</p>'; }
if (isset($_GET['reset'])) { echo '<p class="ok">Your password was changed. Log in with the new password.</p>'; }
if (isset($_GET['idle'])) { echo '<p class="err">The admin was logged out after ' . ADMIN_IDLE_MINUTES . ' minutes without activity. Please log in again.</p>'; }
if (isset($_GET['verified'])) { echo '<p class="ok">' . (isset($_GET['wait']) ? 'Your email is verified. Your account now waits for your teacher or the admin to approve it. You can log in once it is approved.' : 'Your email is verified. You can log in now.') . '</p>'; }
?>
<form method="post" action="Logging/login.php">
<input type="hidden" name="t" value="<?= csrf() ?>">
<label>Student ID / Teacher ID<input name="sid" required autocomplete="username"></label>
<?= pw('Password', 0) ?>
<button class="full">Log in</button>
<p class="links"><a href="Logging/reset.php">Forgot password?</a><a href="Logging/register.php">Create an account</a></p>
<p class="sub"><small>Accounts made before Student IDs existed can still log in with their email here.</small></p>
</form>
</div></div>
<?php endif; ?>
<script src="assets/settings.js?v=48.1"></script>
<script src="assets/landing.js?v=48.1"></script>
</body></html>
