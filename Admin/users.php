<?php
// Admin: all students and teachers. Fix a year level, mark an IRREGULAR student, give a temporary password, unlock a login, delete an account.
require __DIR__.'/../config.php';
require __DIR__.'/../Logging/security.php';
$u = need('admin');

// the filters of the list (kept after every button)
$role = (string)($_GET['role'] ?? $_POST['role'] ?? '');
if ($role !== 'student' && $role !== 'teacher') { $role = ''; }
$q = trim((string)($_GET['q'] ?? $_POST['q'] ?? ''));
$q = mb_substr(preg_replace('/\s+/', ' ', $q), 0, 60);
// the search looks at the name and the Student ID. When the search has an @ in it, it looks at the email instead.
$inUse = strpos($q, '@') !== false ? 'email' : 'name';
$keep = array_filter(['role' => $role, 'q' => $q], fn($v) => $v !== '');
$here = 'Admin/users.php' . ($keep ? '?' . http_build_query($keep) : '');

// a temporary password: 8 letters and numbers that are easy to read (no 0, O, 1, l)
function temp_password() {
    $chars = 'abcdefghjkmnpqrstuvwxyz23456789';
    $t = '';
    for ($i = 0; $i < 8; $i++) { $t .= $chars[random_int(0, strlen($chars) - 1)]; }
    return $t;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $act = (string)($_POST['act'] ?? '');
    $id = (int)($_POST['uid'] ?? 0);
    $g = $pdo->prepare("SELECT id,full_name,student_id,role FROM users WHERE id=? AND role<>'admin'"); // the admin account can never be changed here
    $g->execute([$id]);
    $t = $g->fetch();
    if (!$t) {
        flash('That account was not found.',true);
    } elseif ($act === 'years' && $t['role'] === 'student') {
        $y = (int)($_POST['year'] ?? 0);
        if ($y < 1 || $y > 5) {
            flash('Choose a year level first.',true);
        } else {
            $pdo->prepare("UPDATE users SET year_level=? WHERE id=? AND role='student'")->execute([$y, $id]);
            flash('Saved: ' . $t['full_name'] . ' is now ' . year_name($y) . '.');
        }
    } elseif ($act === 'reset') {
        $tmp = temp_password();
        save_new_password($t['id'], $tmp);
        notify_user($t['id'], 'password', 'The admin gave you a temporary password. Please change it on the Change password page.', 'Logging/password.php');
        flash('Temporary password for ' . $t['full_name'] . ' (' . $t['student_id'] . '): ' . $tmp . '   Tell it to the person. It is shown only now, and it works until they change it.');
    } elseif ($act === 'verify') {
        mark_verified($t['id']);
        flash('The email of ' . $t['full_name'] . ' is marked as verified. The account can log in now.');
    } elseif ($act === 'unlock') {
        clear_wrong_tries('login', 'u' . $t['id']);
        clear_wrong_tries('pw', (string)$t['id']);
        flash($t['full_name'] . ' can try to log in again.');
    } elseif ($act === 'delete') {
        $own = 0;
        if ($t['role'] === 'teacher') {
            $s = $pdo->prepare('SELECT (SELECT COUNT(*) FROM quizzes WHERE teacher_id=?)+(SELECT COUNT(*) FROM coding_exams WHERE teacher_id=?)');
            $s->execute([$id, $id]);
            $own = (int)$s->fetchColumn();
        }
        if ($own > 0) {
            flash('This teacher still owns ' . $own . ($own == 1 ? ' quiz or exam' : ' quizzes and exams') . ', and deleting the account would delete them with all student results. Delete or close them first (Quizzes and exams page).', true);
        } else {
            $pdo->prepare("DELETE FROM users WHERE id=? AND role<>'admin'")->execute([$id]);
            flash('Account of ' . $t['full_name'] . ' deleted.');
        }
    }
    header('Location: '.BASE.$here);
    exit;
}

// ---- the list ----
$w = ["role<>'admin'"];
$p = [];
if ($role !== '') { $w[] = 'role=?'; $p[] = $role; }
// every typed word must be found ("joshua legaspi" or "legaspi joshua"); % and _ are searched as normal letters
$words = $q === '' ? [] : array_slice(array_values(array_filter(explode(' ', $q), 'strlen')), 0, 5);
$cols = ['name' => ['full_name', 'student_id'], 'email' => ['email'], 'all' => ['full_name', 'student_id', 'email']][$inUse];
foreach ($words as $wd) {
    $l = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $wd) . '%';
    $w[] = '(' . implode(' OR ', array_map(fn($c) => "$c LIKE ? ESCAPE '!'", $cols)) . ')';
    foreach ($cols as $c) { $p[] = $l; }
}
$s = $pdo->prepare('SELECT id,full_name,student_id,email,role,year_level,email_verified,approved,created_at FROM users WHERE ' . implode(' AND ', $w) . ' ORDER BY role DESC, full_name LIMIT 301');
$s->execute($p);
$rows = $s->fetchAll();
$more = count($rows) > 300;
if ($more) { array_pop($rows); }
// best matches first: the name starts with the search, then a word of the name starts with it, then the rest
if ($words) {
    $rank = function ($r) use ($q, $words) { $n = mb_strtolower($r['full_name']); $s = mb_strtolower((string)$r['student_id']); $t = mb_strtolower($q);
        if (strpos($n, $t) === 0 || $s === $t) return 0; if (preg_match('/(^|\s)' . preg_quote(mb_strtolower($words[0]), '/') . '/u', $n)) return 1; return 2; };
    usort($rows, fn($a, $b) => [$rank($a), $a['full_name']] <=> [$rank($b), $b['full_name']]);
}
// shows the found letters in a colour, so it is clear why each person is in the list
function mark_words($text, $words) { if (!$words) return e($text); $parts = preg_split('/(' . implode('|', array_map(fn($x) => preg_quote($x, '/'), $words)) . ')/iu', (string)$text, -1, PREG_SPLIT_DELIM_CAPTURE); $o = '';
    foreach ($parts as $i => $pt) { $o .= $i % 2 ? '<mark>' . e($pt) . '</mark>' : e($pt); } return $o; }

$locked = [];
foreach ($pdo->query("SELECT who FROM login_attempts WHERE kind='login' AND tried_at > NOW() - INTERVAL 15 MINUTE GROUP BY who HAVING COUNT(*)>=5") as $r) { $locked[$r['who']] = 1; }

$hid = '<input type="hidden" name="t" value="' . csrf() . '"><input type="hidden" name="role" value="' . e($role) . '"><input type="hidden" name="q" value="' . e($q) . '">';
function small_form($hid, $id, $act, $label, $confirm = '') {
    return '<form method="post" class="inl" style="margin:.2rem 0"' . ($confirm ? ' onsubmit="return confirm(\'' . $confirm . '\')"' : '') . '>' . $hid . '<input type="hidden" name="uid" value="' . (int)$id . '"><input type="hidden" name="act" value="' . $act . '"><button class="alt">' . $label . '</button></form>';
}

head('Users');
flash();
?>
<p>Every student and teacher. You see the Student ID and email here. The year level is information only: students get quizzes and code exams from the classes they join (an irregular student joins every class of the subjects he or she takes).</p>
<form method="get" action="Admin/users.php" class="inl" style="flex-wrap:wrap;align-items:end">
<label>Search<input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, Student ID, or email with @" autofocus></label>
<label>Role<select name="role"><option value="">Everyone</option><option value="student"<?= $role === 'student' ? ' selected' : '' ?>>Students</option><option value="teacher"<?= $role === 'teacher' ? ' selected' : '' ?>>Teachers</option></select></label>
<button class="alt">Show</button>
<?php if ($keep) { echo '<a class="btn alt" href="Admin/users.php">Clear</a>'; } ?>
</form>
<?php if ($q !== '') { echo '<p><small><b>' . count($rows) . ($more ? '+' : '') . '</b> ' . (count($rows) == 1 ? 'person' : 'people') . ' found for &ldquo;' . e($q) . '&rdquo;, searching ' . ($inUse === 'email' ? 'emails' : 'names and Student IDs') . '.' . ($inUse === 'name' ? ' To find someone by email, type the whole email or the part with the @.' : '') . '</small></p>'; } ?>
<?php
if (!$rows) {
    echo '<p class="card">No account matches.</p>';
} else {
    table(['Person', 'Role', 'Year level', 'Joined', 'Account'], array_map(function ($r) use ($hid, $locked, $words, $inUse) {
        $mw = $inUse === 'email' ? [] : $words;
        $who = mark_words($r['full_name'], $mw) . ((isset($locked['u' . $r['id']])) ? ' <span class="pill" style="background:var(--bad);color:#fff">Locked</span>' : '') . ((int)($r['approved'] ?? 1) === 0 ? ' <a class="pill" href="Dashboard/approve.php">Waiting for approval</a>' : '') . '<br><small>' . ($r['student_id'] ? mark_words($r['student_id'], $mw) : 'no ID') . ' &middot; ' . mark_words($r['email'], $inUse === 'name' ? [] : $words) . ((int)$r['email_verified'] === 1 ? ' <b class="vok">✔ Verified</b>' : ' <b class="vno">Not verified</b>') . '</small>';
        if ($r['role'] === 'student') {
            $yc = '<form method="post">' . $hid . '<input type="hidden" name="uid" value="' . (int)$r['id'] . '"><input type="hidden" name="act" value="years"><div class="inl" style="margin:0"><select name="year">'
                . ($r['year_level'] ? '' : '<option value="">Not set</option>')
                . implode('', array_map(fn($y) => '<option value="' . $y . '"' . ((int)$r['year_level'] === $y ? ' selected' : '') . '>' . year_name($y) . '</option>', [1, 2, 3, 4, 5]))
                . '</select><button class="alt">Save</button></div>' . '</form>';
        } else {
            $yc = '-';
        }
        $acc = ((int)$r['email_verified'] === 0 ? small_form($hid, $r['id'], 'verify', 'Mark email as verified', 'Mark this email as verified without a code? Only do this when you know the email belongs to this person.') : '')
            . small_form($hid, $r['id'], 'reset', 'Reset password', 'Make a new temporary password for this account?')
            . (isset($locked['u' . $r['id']]) ? small_form($hid, $r['id'], 'unlock', 'Unlock login') : '')
            . small_form($hid, $r['id'], 'delete', 'Delete', 'Delete this account and everything it did (results, saved files)? This cannot be undone.');
        return [$who, e(ucfirst($r['role'])), $yc, e(substr($r['created_at'], 0, 10)), $acc];
    }, $rows));
    if ($more) { echo '<p><small>Only the first 300 accounts are shown. Use the search to find the others.</small></p>'; }
}
foot();
