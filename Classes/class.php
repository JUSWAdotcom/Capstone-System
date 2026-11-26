<?php
// ONE CLASS. Teacher: join code, its quizzes and code exams with how many students finished, and the student list.
// Student: the quizzes and code exams of this class with Finished / Missed / Open, and Leave class.
require __DIR__.'/../config.php';
$u = need('any');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$s = $pdo->prepare('SELECT c.*,t.full_name teacher FROM classes c JOIN users t ON t.id=c.teacher_id WHERE c.id=?');
$s->execute([$id]);
$c = $s->fetch();
$mine = $c && $u['role'] === 'teacher' && (int)$c['teacher_id'] === (int)$u['id'];
if (!$c || ($u['role'] === 'teacher' && !$mine) || ($u['role'] === 'student' && !in_class($id, $u['id']))) {
    header('Location: ' . BASE . 'Classes/classes.php');
    exit;
}
$back = 'Classes/class.php?id=' . $id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $do = (string)($_POST['do'] ?? '');
    if ($mine) {
        if ($do === 'code') {
            $code = new_join_code();
            $pdo->prepare('UPDATE classes SET join_code=? WHERE id=?')->execute([$code, $id]);
            flash('New join code: ' . $code . '. The old code does not work any more. Students already in the class stay.');
        } elseif ($do === 'edit') {
            $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 100);
            $yl = (int)($_POST['year'] ?? 0);
            if ($name !== '') {
                $pdo->prepare('UPDATE classes SET name=?,section=?,year_level=? WHERE id=?')->execute([$name, mb_substr(trim((string)($_POST['section'] ?? '')), 0, 40), ($yl >= 1 && $yl <= 4) ? $yl : null, $id]);
                flash('Class saved.');
            }
        } elseif ($do === 'archive' || $do === 'unarchive') {
            $pdo->prepare('UPDATE classes SET archived=? WHERE id=?')->execute([$do === 'archive' ? 1 : 0, $id]);
            flash($do === 'archive' ? 'Class archived. Students cannot join it any more and its work is no longer on their lists. Results stay.' : 'Class is active again.');
        } elseif ($do === 'remove') {
            $pdo->prepare('DELETE FROM class_members WHERE class_id=? AND student_id=?')->execute([$id, (int)($_POST['uid'] ?? 0)]);
            flash('Student removed from the class. The work the student already did stays in Grades.');
        } elseif ($do === 'delete') {
            $n = $pdo->prepare('SELECT (SELECT COUNT(*) FROM quizzes WHERE class_id=?)+(SELECT COUNT(*) FROM coding_exams WHERE class_id=?)');
            $n->execute([$id, $id]);
            if ((int)$n->fetchColumn() > 0) {
                flash('This class has quizzes or code exams, so it cannot be deleted. Archive it instead.',true);
            } else {
                $pdo->prepare('DELETE FROM classes WHERE id=?')->execute([$id]);
                flash('Class deleted.');
                header('Location: ' . BASE . 'Classes/classes.php');
                exit;
            }
        }
    } elseif ($u['role'] === 'student' && $do === 'leave') {
        $pdo->prepare('DELETE FROM class_members WHERE class_id=? AND student_id=?')->execute([$id, $u['id']]);
        flash('You left ' . class_name($c) . '. Your finished work stays in your grades.');
        header('Location: ' . BASE . 'Classes/classes.php');
        exit;
    }
    header('Location: ' . BASE . $back);
    exit;
}

$T = '<input type="hidden" name="t" value="' . csrf() . '"><input type="hidden" name="id" value="' . $id . '">';
head(class_name($c));
flash();

if ($u['role'] === 'student') {
    echo '<form method="post" style="float:right;margin:0" onsubmit="return confirm(\'Leave this class? Use this if you joined the wrong class. You can join again with the class code.\')">' . $T . '<input type="hidden" name="do" value="leave"><button class="alt">Leave class</button></form>';
    echo '<p><small>Teacher: <b>' . e($c['teacher']) . '</b>' . ($c['year_level'] ? ' &middot; ' . e(year_name($c['year_level'])) . ' subject' : '') . ($c['archived'] ? ' &middot; <span class="st st-soon">Archived</span>' : '') . '</small></p>';
    $w = work_rows($id, $u['id']);
    echo '<h2>Quizzes and code exams</h2>' . ($w ? work_list($w) : '<p class="card">Your teacher has not posted a quiz or code exam in this class yet. You get a notification when one is posted.</p>');
    echo '<form method="post" onsubmit="return confirm(\'Leave this class? You can join again with the class code.\')">' . $T . '<input type="hidden" name="do" value="leave"><button class="alt">Leave class</button></form>';
    foot();
    exit;
}

// ---------- teacher ----------
$members = $pdo->prepare("SELECT u.id,u.full_name,u.student_id,u.year_level,m.joined_at FROM class_members m JOIN users u ON u.id=m.student_id WHERE m.class_id=? ORDER BY u.full_name");
$members->execute([$id]);
$members = $members->fetchAll();
$work = work_rows($id);

echo '<div class="card chead"><div><small>' . e($c['year_level'] ? year_name($c['year_level']) : 'Mixed year levels') . ($c['school_year'] ? ' &middot; School year ' . e($c['school_year']) : '') . ($c['archived'] ? ' &middot; <b>Archived</b>' : '') . '</small>
<h2>Join code <span class="code6">' . e($c['join_code']) . '</span></h2><small>Students open Classes, type this code and press Join.</small></div>
<div class="inl" style="margin:0;flex-wrap:wrap"><a class="btn" href="Quiz/quiz_new.php?class=' . $id . '">+ New quiz</a><a class="btn" href="Exam/exam_new.php?class=' . $id . '">+ New code exam</a></div></div>';

// the work of this class: for each one, how many students finished, are still busy, missed
$cnt = function ($r) use ($pdo, $id) {
    $t = $r['kind'] === 'quiz' ? 'attempts a WHERE a.quiz_id=?' : 'exam_attempts a WHERE a.exam_id=?';
    $s = $pdo->prepare("SELECT COUNT(*) FROM $t AND a.submitted_at IS NOT NULL AND a.student_id IN (SELECT student_id FROM class_members WHERE class_id=?)");
    $s->execute([$r['id'], $id]);
    return (int)$s->fetchColumn();
};
echo '<h2>Quizzes and code exams (' . count($work) . ')</h2>';
if (!$work) {
    echo '<p class="card">Nothing posted yet. Press <b>+ New quiz</b> or <b>+ New code exam</b>: only the students of this class get it.</p>';
} else {
    $nm = count($members);
    table(['', 'Title', 'Status', 'Finished', ''], array_map(function ($r) use ($cnt, $nm) {
        $q = $r['kind'] === 'quiz';
        $f = $cnt($r);
        $ended = !empty($r['due_at']) && time() >= strtotime($r['due_at']);
        return [ico($q ? 'quiz' : 'exam'), e($r['title']) . '<br><small>' . ($q ? 'Quiz, ' . (int)$r['n'] . ' questions' : 'Code exam, ' . (int)$r['n'] . ' min, ' . e(lname($r['language']))) . '</small>',
            teacher_state($r), $f . ' of ' . $nm . ($ended && $nm - $f > 0 ? '<br><span class="st st-missed">' . ($nm - $f) . ' missed</span>' : ''),
            '<a href="' . ($q ? 'Quiz/results.php?id=' : 'Exam/exam_results.php?id=') . (int)$r['id'] . '">' . ($q ? 'Results' : 'Open and grade') . '</a>'];
    }, $work));
}

echo '<h2>Students (' . count($members) . ')</h2>';
if (!$members) {
    echo '<p class="card">No student has joined yet. Give them the join code <b>' . e($c['join_code']) . '</b>.</p>';
} else {
    // finished and missed work of each student in this class
    $stat = [];
    foreach ($members as $m) {
        $wr = work_rows($id, $m['id']);
        $d = count(array_filter($wr, fn($r) => work_state($r)[0] === 'done'));
        $mi = count(array_filter($wr, fn($r) => work_state($r)[0] === 'missed'));
        $stat[$m['id']] = [$d, $mi];
    }
    table(['Student', 'Year level', 'Joined', 'Finished', 'Missed', ''], array_map(fn($m) => [
        e($m['full_name']) . '<br><small>' . e($m['student_id']) . '</small>', e(year_name($m['year_level'])), e(substr($m['joined_at'], 0, 10)),
        '<span class="st st-done">' . $stat[$m['id']][0] . '</span>', $stat[$m['id']][1] ? '<span class="st st-missed">' . $stat[$m['id']][1] . '</span>' : '0',
        '<form method="post" class="inl" style="margin:0" onsubmit="return confirm(\'Remove this student from the class?\')">' . $T . '<input type="hidden" name="do" value="remove"><input type="hidden" name="uid" value="' . (int)$m['id'] . '"><button class="alt">Remove</button></form>'
    ], $members));
}

echo '<h2>Class settings</h2><div class="card" style="max-width:none"><form method="post">' . $T . '<input type="hidden" name="do" value="edit"><div class="two"><label>Subject<input name="name" required maxlength="100" value="' . e($c['name']) . '"></label><label>Section<input name="section" maxlength="40" value="' . e($c['section']) . '"></label></div>
<label>Year level of the subject<select name="year"><option value="">Mixed / not set</option>';
foreach ([1, 2, 3, 4] as $y) echo '<option value="' . $y . '"' . ((int)$c['year_level'] === $y ? ' selected' : '') . '>' . year_name($y) . '</option>';
echo '</select></label><button class="alt">Save</button></form><hr style="border:0;border-top:1px solid var(--line);margin:1rem 0">
<div class="inl" style="flex-wrap:wrap">
<form method="post" class="inl" style="margin:0" onsubmit="return confirm(\'Make a new join code? The old one stops working.\')">' . $T . '<input type="hidden" name="do" value="code"><button class="alt">New join code</button></form>
<form method="post" class="inl" style="margin:0">' . $T . '<input type="hidden" name="do" value="' . ($c['archived'] ? 'unarchive' : 'archive') . '"><button class="alt">' . ($c['archived'] ? 'Make active again' : 'Archive class') . '</button></form>
<form method="post" class="inl" style="margin:0" onsubmit="return confirm(\'Delete this class? Only a class without quizzes and code exams can be deleted.\')">' . $T . '<input type="hidden" name="do" value="delete"><button class="alt">Delete class</button></form></div>
<small>Archive a class at the end of the semester: students can no longer join it and its work leaves their lists, but every result stays.</small></div>';
foot();
