<?php
// Admin: every quiz and code exam of every teacher, with its class, start time and deadline. The admin can end one now or delete it.
require __DIR__.'/../config.php';
$u = need('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $kind = (string)($_POST['kind'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $act = (string)($_POST['act'] ?? '');
    $t = $kind === 'quiz' ? 'quizzes' : ($kind === 'exam' ? 'coding_exams' : '');
    if ($t !== '' && $act === 'end') {
        $pdo->prepare("UPDATE $t SET due_at=? WHERE id=?")->execute([date('Y-m-d H:i:s'), $id]);
        flash('Ended now. Students who did not submit are Missed.');
    } elseif ($kind === 'exam' && $act === 'nopic') {
        $g = $pdo->prepare('SELECT attachment FROM coding_exams WHERE id=?');
        $g->execute([$id]);
        drop_exam_picture((string)$g->fetchColumn());
        $pdo->prepare('UPDATE coding_exams SET attachment=NULL,attachment_check=NULL WHERE id=?')->execute([$id]);
        flash('Picture removed.');
    } elseif ($t !== '' && $act === 'delete') {
        if ($kind === 'exam') { $g = $pdo->prepare('SELECT attachment FROM coding_exams WHERE id=?'); $g->execute([$id]); drop_exam_picture((string)$g->fetchColumn()); }
        $s = $pdo->prepare("DELETE FROM $t WHERE id=?");
        $s->execute([$id]);
        if ($s->rowCount()) {
            if ($kind === 'quiz') {
                $pdo->prepare("DELETE FROM notifications WHERE kind='quiz' AND link=?")->execute(['Quiz/quiz.php?id=' . $id]);
            }
            flash('Deleted with all its results.');
        }
    }
    header('Location: '.BASE.'Admin/content.php');
    exit;
}

function content_form($kind, $id, $act, $label, $confirm = '') {
    return '<form method="post" class="inl" style="display:inline;margin:0"' . ($confirm ? ' onsubmit="return confirm(\'' . $confirm . '\')"' : '') . '><input type="hidden" name="t" value="' . csrf() . '"><input type="hidden" name="kind" value="' . $kind . '"><input type="hidden" name="id" value="' . (int)$id . '"><input type="hidden" name="act" value="' . $act . '"><button class="alt">' . $label . '</button></form> ';
}

head('Quizzes and code exams');
flash();
echo '<p>Every quiz and code exam of every teacher. <b>End now</b> sets the deadline to now (students who did not submit become Missed). <b>Delete removes the results of the students too.</b></p>';
$cn = [];
foreach ($pdo->query('SELECT id,name,section FROM classes') as $c) { $cn[$c['id']] = class_name($c); }
$open = fn($r) => empty($r['due_at']) || time() < strtotime($r['due_at']);

$rows = $pdo->query("SELECT q.id,q.title,q.class_id,q.opens_at,q.due_at,q.closed,t.full_name teacher,(SELECT COUNT(*) FROM questions WHERE quiz_id=q.id) n,(SELECT COUNT(*) FROM attempts a WHERE a.quiz_id=q.id AND a.submitted_at IS NOT NULL) s FROM quizzes q JOIN users t ON t.id=q.teacher_id ORDER BY q.id DESC")->fetchAll();
echo '<h2>Quizzes <small>(' . count($rows) . ')</small></h2>';
if ($rows) {
    table(['Quiz', 'Teacher', 'Class', 'Status', 'Questions', 'Finished', ''], array_map(fn($r) => [
        e($r['title']), e($r['teacher']), e($cn[$r['class_id']] ?? '-'), teacher_state($r), (int)$r['n'], (int)$r['s'],
        ($open($r) ? content_form('quiz', $r['id'], 'end', 'End now', 'End this quiz now?') : '')
        . content_form('quiz', $r['id'], 'delete', 'Delete', 'Delete this quiz and every student result in it? This cannot be undone.')
    ], $rows));
} else {
    echo '<p><small>No quiz yet.</small></p>';
}

$rows = $pdo->query("SELECT x.id,x.title,x.class_id,x.opens_at,x.due_at,x.closed,x.language,x.attachment,x.attachment_check,t.full_name teacher,(SELECT COUNT(*) FROM exam_attempts a WHERE a.exam_id=x.id AND a.submitted_at IS NOT NULL) n,(SELECT COUNT(*) FROM exam_attempts a WHERE a.exam_id=x.id AND a.submitted_at IS NOT NULL AND a.score IS NULL) g FROM coding_exams x JOIN users t ON t.id=x.teacher_id ORDER BY x.id DESC")->fetchAll();
echo '<h2>Code exams <small>(' . count($rows) . ')</small></h2>';
if ($rows) {
    table(['Exam', 'Teacher', 'Class', 'Status', 'Language', 'Finished', 'To grade', ''], array_map(fn($r) => [
        e($r['title']) . ($r['attachment'] ? '<br><a href="Exam/attachment.php?id=' . (int)$r['id'] . '" target="_blank" rel="noopener"><img class="thumb" src="Exam/attachment.php?id=' . (int)$r['id'] . '" alt="Picture"></a><small>' . ($r['attachment_check'] === 'ai_ok' ? 'Checked by the AI' : 'Not checked by an AI: look at it') . '</small>' : ''), e($r['teacher']), e($cn[$r['class_id']] ?? '-'), teacher_state($r), e(lname($r['language'])), (int)$r['n'], (int)$r['g'],
        ($open($r) ? content_form('exam', $r['id'], 'end', 'End now', 'End this code exam now?') : '') . ($r['attachment'] ? content_form('exam', $r['id'], 'nopic', 'Remove picture', 'Remove the picture of this exam?') : '')
        . content_form('exam', $r['id'], 'delete', 'Delete', 'Delete this exam and every student code and score in it? This cannot be undone.')
    ], $rows));
} else {
    echo '<p><small>No code exam yet.</small></p>';
}
foot();
