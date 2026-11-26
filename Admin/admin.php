<?php
// Admin dashboard. There is only ONE admin account (made from ADMIN_ID in config.php).
// The admin sees the whole school: users, quizzes and exams of every teacher, the school year, and can send an announcement.
require __DIR__.'/../config.php';
require __DIR__.'/../Logging/mailer.php';
$u = need('admin');
$msgError = '';

// ---- send an announcement to the students (it shows in their bell) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $text = trim((string)($_POST['msg'] ?? ''));
    $to = (string)($_POST['to'] ?? 'all');
    if ($text === '') {
        $msgError = 'Write the announcement first.';
    } elseif (mb_strlen($text) > 200) {
        $msgError = 'Please keep the announcement under 200 letters.';
    } elseif (!in_array($to, ['all', '1', '2', '3', '4'], true)) {
        $msgError = 'Choose who receives it.';
    } else {
        $n = notify_students('announce', 'Announcement: ' . $text, 'Dashboard/dashboard.php', $to === 'all' ? '1234' : $to);
        flash('Announcement sent to ' . $n . ' student' . ($n == 1 ? '' : 's') . '.');
        header('Location: '.BASE.'Admin/admin.php');
        exit;
    }
}

function count_of($sql) {
    global $pdo;
    return (int)$pdo->query($sql)->fetchColumn();
}
$students = count_of("SELECT COUNT(*) FROM users WHERE role='student'");
$teachers = count_of("SELECT COUNT(*) FROM users WHERE role='teacher'");
$quizzes = count_of('SELECT COUNT(*) FROM quizzes');
$exams = count_of('SELECT COUNT(*) FROM coding_exams');
$qdone = count_of('SELECT COUNT(*) FROM attempts WHERE submitted_at IS NOT NULL');
$edone = count_of('SELECT COUNT(*) FROM exam_attempts WHERE submitted_at IS NOT NULL');
$flags = count_of('SELECT COUNT(*) FROM cheat_events WHERE event_type IN '.FLAG_SQL) + count_of('SELECT COUNT(*) FROM exam_events WHERE event_type IN '.FLAG_SQL);
$classes = count_of('SELECT COUNT(*) FROM classes WHERE archived=0');
$toGrade = count_of('SELECT COUNT(*) FROM exam_attempts WHERE submitted_at IS NOT NULL AND score IS NULL');
$noYear = count_of("SELECT COUNT(*) FROM users WHERE role='student' AND year_level IS NULL");
$unver = count_of('SELECT COUNT(*) FROM users WHERE email_verified=0');

// students per year level, for the bars
$per = [];
foreach ($pdo->query("SELECT year_level y, COUNT(*) c FROM users WHERE role='student' GROUP BY year_level") as $r) {
    $per[(int)$r['y']] = (int)$r['c'];
}

head('Admin dashboard');
flash();
$cur = school_year();
?>
<p>Welcome, <b><?= e($u['name']) ?></b>. You are the only admin. You can see everything in the school and fix what the teachers cannot.<br><small>School year: <b><?= e($cur ?: 'not set yet') ?></b> &nbsp; <a href="Grades/promote.php"><?= $cur ? 'Start a new school year' : 'Set the school year' ?></a></small></p>


<div class="stats">
<div class="card"><h3>Students</h3><div class="big"><?= $students ?></div><small><?= $classes ?> active class<?= $classes == 1 ? '' : 'es' ?></small></div>
<div class="card"><h3>Teachers</h3><div class="big"><?= $teachers ?></div></div>
<div class="card"><h3>Quizzes</h3><div class="big"><?= $quizzes ?></div><small><?= $qdone ?> submitted</small></div>
<div class="card"><h3>Code exams</h3><div class="big"><?= $exams ?></div><small><?= $edone ?> submitted, <?= $toGrade ?> to grade</small></div>
<div class="card"><h3>Integrity flags</h3><div class="big"><?= $flags ?></div><small>quizzes and exams together</small></div>
</div>

<h2>Students by year level</h2>
<div class="card">
<?php
$max = max(1, max($per ?: [0]));
foreach ([1, 2, 3, 4, 5, 0] as $y) {
    $c = $per[$y] ?? 0;
    echo '<div class="yrbar"><span>' . e(year_name($y)) . '</span><div class="pb"><i style="width:' . round($c / $max * 100) . '%"></i></div><b>' . $c . '</b></div>';
}
?>
<small>Students take quizzes and code exams through the classes they join, so an irregular student simply joins classes of more than one year level.</small>
</div>

<h2>Needs attention</h2>
<div class="card">
<ul class="todo">
<?php
$any = false;
if ($noYear) { $any = true; echo '<li><b>' . $noYear . '</b> student' . ($noYear == 1 ? ' has' : 's have') . ' no year level yet. <a href="Admin/users.php?role=student">Open Users</a> (their year level shows Not set)</li>'; }
$bad = count_of("SELECT COUNT(*) FROM admin_log WHERE event IN ('wrong_password','wrong_code','blocked_computer') AND at > '" . date('Y-m-d H:i:s', time() - 86400) . "'");
if ($bad) { $any = true; echo '<li><b class="err">' . $bad . ' wrong admin login' . ($bad == 1 ? '' : 's') . ' in the last 24 hours.</b> <a href="Admin/admin_security.php">Look at the sign-in log</a></li>'; }
if (strcasecmp((string)($_SESSION['user']['sid'] ?? ''), 'admin') === 0) { $any = true; echo '<li>The admin login ID is still "admin", which anybody can guess. <a href="Admin/admin_security.php">Change it</a></li>'; }
if (pending_count($u)) { $any = true; echo '<li>' . pending_count($u) . ' new account' . (pending_count($u) == 1 ? ' is' : 's are') . ' waiting for approval. <a href="Dashboard/approve.php">Approve or decline</a></li>'; }
if (!APPROVE_NEW_ACCOUNTS) { $any = true; echo '<li>Approval of new accounts is off, so anyone who finds the site can sign up and log in. Set APPROVE_NEW_ACCOUNTS=true in config.php</li>'; }
$merr = '';
try { $merr = (string)$pdo->query("SELECT v FROM settings WHERE k='mail_error'")->fetchColumn(); } catch (Throwable $x) {}
if (mail_ready() && $merr !== '') { $any = true; [$mk, $mt] = array_pad(explode('|', $merr, 2), 2, '');
    echo '<li class="err"><b>Email is set up but does NOT work</b> (last try ' . e($mt) . '): '
       . e($mk === 'login' ? 'Gmail did not accept the Gmail address and App password. Make a new App password in that Gmail and save it again.' : ($mk === 'connect' ? 'this computer could not reach Gmail (internet).' : 'the mail server refused the email.'))
       . ' Students cannot get their codes until this is fixed. <a href="Admin/email.php">Fix it in Email</a></li>'; }
if (!mail_ready()) { $any = true; echo '<li>Email is not set up, so new students cannot get their sign-up code and nobody can reset or change a password. <a href="Admin/email.php">Set it up (Gmail)</a></li>'; }
if ($unver) { $any = true; echo '<li><b>' . $unver . '</b> account' . ($unver == 1 ? ' has' : 's have') . ' not verified the email yet (they cannot log in). <a href="Admin/users.php">Open Users</a> (they show Not verified beside the email)</li>'; }
if ($toGrade) { $any = true; echo '<li><b>' . $toGrade . '</b> code exam submission' . ($toGrade == 1 ? ' is' : 's are') . ' waiting for a teacher to grade.</li>'; }
if (!$cur) { $any = true; echo '<li>The school year is not set yet. <a href="Grades/promote.php">Set it</a> so new quizzes and exams are marked with it.</li>'; }
if (!$any) { echo '<li>Nothing needs attention right now.</li>'; }
?>
</ul></div>

<h2>Send an announcement</h2>
<?php if ($msgError) { echo '<p class="err">' . e($msgError) . '</p>'; } ?>
<form method="post" class="card" style="max-width:none">
<input type="hidden" name="t" value="<?= csrf() ?>">
<label>Message (up to 200 letters)<input name="msg" maxlength="200" required value="<?= e($_POST['msg'] ?? '') ?>"></label>
<label>Send to<select name="to"><option value="all">All students</option><option value="1">1st year students</option><option value="2">2nd year students</option><option value="3">3rd year students</option><option value="4">4th year students</option></select></label>
<button>Send announcement</button> <small>Students get a pop-up and it stays in their bell. Graduated students are not told.</small>
</form>

<h2>Newest accounts</h2>
<?php
$rows = $pdo->query("SELECT full_name, student_id, role, year_level, created_at FROM users WHERE role<>'admin' ORDER BY id DESC LIMIT 8")->fetchAll();
if ($rows) {
    table(['Name', 'ID', 'Role', 'Year level', 'Joined'], array_map(fn($r) => [e($r['full_name']), e($r['student_id'] ?: '-'), e(ucfirst($r['role'])), $r['role'] === 'student' ? e(year_name($r['year_level'])) : '-', e(substr($r['created_at'], 0, 10))], $rows));
} else {
    echo '<p><small>Nobody has signed up yet.</small></p>';
}
foot();
