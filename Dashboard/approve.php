<?php
// APPROVALS: a new account cannot log in until somebody who knows the person approves it (APPROVE_NEW_ACCOUNTS in config.php).
// Teachers approve students. The admin approves students and teachers.
// Declining deletes the waiting account, so the real owner of that Student ID or email can still sign up.
// Only accounts that already verified their email are shown (the others cannot log in anyway and are replaced by a new sign up).
require __DIR__.'/../config.php';
require_once __DIR__.'/../Logging/mailer.php';
$u = need('staff');
$admin = $u['role'] === 'admin';
$roles = $admin ? "x.role IN ('student','teacher')" : "x.role='student'"; // a teacher cannot approve another teacher

// tell the person by email that the account is ready (only when email is set up; nothing happens otherwise)
function approved_mail($r) {
    if (!mail_ready()) return;
    $m = mail_approved_message($r['full_name'], $r['role'], $r['student_id']);
    $err = '';
    send_mail($r['email'], $m['subject'], $m['text'], $err, $m['html']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $act = '';
    $ids = [];
    if (preg_match('/^(approve|decline)-(\d+)$/', (string)($_POST['one'] ?? ''), $m)) { // the button of one row
        $act = $m[1];
        $ids = [(int)$m[2]];
    } elseif (isset($_POST['all'])) { // "Approve checked"
        $act = 'approve';
        $ids = array_map('intval', (array)($_POST['ids'] ?? []));
    }
    $g = $pdo->prepare("SELECT x.id,x.full_name,x.student_id,x.email,x.role FROM users x WHERE x.id=? AND x.approved=0 AND x.email_verified=1 AND $roles");
    $done = [];
    foreach (array_unique($ids) as $id) {
        $g->execute([$id]);
        $r = $g->fetch();
        if (!$r) continue; // not waiting any more, or not yours to approve
        if ($act === 'approve') {
            $pdo->prepare('UPDATE users SET approved=1, approved_by=?, approved_at=NOW() WHERE id=? AND approved=0')->execute([$u['id'], $id]);
            try { approved_mail($r); } catch (Throwable $x) { }
        } else {
            $pdo->prepare("DELETE FROM users WHERE id=? AND approved=0 AND role<>'admin'")->execute([$id]);
        }
        $done[] = $r['full_name'];
    }
    if ($done) {
        flash(($act === 'approve' ? 'Approved: ' : 'Declined and deleted: ') . implode(', ', $done) . '.');
    } elseif (isset($_POST['all'])) {
        flash('Check at least one account first.',true);
    }
    header('Location: ' . BASE . 'Dashboard/approve.php');
    exit;
}

$s = $pdo->query("SELECT x.id,x.full_name,x.student_id,x.email,x.role,x.year_level,x.created_at FROM users x WHERE x.approved=0 AND x.email_verified=1 AND $roles ORDER BY x.created_at");
$rows = $s->fetchAll();

head('Approve new accounts');
flash();
if (!APPROVE_NEW_ACCOUNTS) {
    echo '<p class="card">Approval is turned off (APPROVE_NEW_ACCOUNTS=false in config.php), so new accounts can log in right after they verify their email.</p>';
}
echo '<p><small>' . ($admin ? 'New students and teachers' : 'New students') . ' wait here after they verify their email. They cannot log in until they are approved. '
    . 'Approve only the people you know are your students. Decline anyone you do not know: the account is deleted, so the real owner of that Student ID can still sign up.'
    . ($admin ? '' : ' New teacher accounts are approved by the admin.') . '</small></p>';

if (!$rows) {
    echo '<p class="card ok">Nobody is waiting for approval.</p>';
} else {
    echo '<form method="post"><input type="hidden" name="t" value="' . csrf() . '">';
    $all = '<input type="checkbox" aria-label="Choose all" onclick="for(const c of this.form.querySelectorAll(\'input[name^=ids]\'))c.checked=this.checked">';
    table([$all, 'Name', 'ID', 'Year level', 'Email', 'Signed up', ''], array_map(function ($r) {
        $id = (int)$r['id'];
        return [
            '<input type="checkbox" name="ids[]" value="' . $id . '" aria-label="Choose ' . e($r['full_name']) . '">',
            e($r['full_name']) . ($r['role'] === 'teacher' ? ' <span class="pill">Teacher</span>' : ''),
            e($r['student_id']),
            $r['role'] === 'teacher' ? '-' : e(year_name($r['year_level'])),
            e($r['email']),
            e(substr($r['created_at'], 0, 16)),
            '<button name="one" value="approve-' . $id . '">Approve</button> '
            . '<button class="alt" name="one" value="decline-' . $id . '" onclick="return confirm(\'Decline and delete this account? Do this only for someone who is not your student.\')">Decline</button>',
        ];
    }, $rows));
    echo '<button name="all" value="1">Approve all checked</button></form>';
}

// the last approvals, so everybody can see who approved whom
$s = $pdo->query("SELECT x.full_name,x.student_id,x.role,x.approved_at,a.full_name by_name FROM users x LEFT JOIN users a ON a.id=x.approved_by WHERE x.approved_at IS NOT NULL AND $roles ORDER BY x.approved_at DESC LIMIT 15");
$last = $s->fetchAll();
if ($last) {
    echo '<h2>Recently approved</h2>';
    table(['Name', 'ID', 'Role', 'Approved', 'By'], array_map(fn($r) => [e($r['full_name']), e($r['student_id']), e(ucfirst($r['role'])), e(substr($r['approved_at'], 0, 16)), e($r['by_name'] ?? '-')], $last));
}
foot();
