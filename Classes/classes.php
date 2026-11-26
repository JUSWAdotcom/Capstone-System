<?php
// CLASSES (version 41). A teacher makes one class for each subject and section and gives its JOIN CODE to the students.
// A student types the join code to join. Quizzes and code exams are made for one class, so only its students get them.
// An irregular student (1st year + some 2nd year subjects) simply joins the class of every subject he or she takes.
require __DIR__.'/../config.php';
require __DIR__.'/../Logging/security.php'; // wrong join codes are limited (5 per 15 minutes), like the other codes
$u = need('any');
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $do = (string)($_POST['do'] ?? '');
    if ($u['role'] === 'teacher' && $do === 'new') {
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 100);
        $sec = mb_substr(trim((string)($_POST['section'] ?? '')), 0, 40);
        $yl = (int)($_POST['year'] ?? 0);
        if ($name === '') {
            $err = 'Type the subject of the class.';
        } else {
            $code = new_join_code();
            $pdo->prepare('INSERT INTO classes(teacher_id,name,section,year_level,join_code,school_year) VALUES(?,?,?,?,?,?)')
                ->execute([$u['id'], $name, $sec, ($yl >= 1 && $yl <= 4) ? $yl : null, $code, school_year()]);
            $id = (int)$pdo->lastInsertId();
            flash('Class made. Give your students the join code ' . $code . '.');
            header('Location: ' . BASE . 'Classes/class.php?id=' . $id);
            exit;
        }
    } elseif ($u['role'] === 'student' && $do === 'leave') {
        $cid = (int)($_POST['id'] ?? 0);
        $s = $pdo->prepare('SELECT name,section FROM classes WHERE id=?');
        $s->execute([$cid]);
        if (($c = $s->fetch()) && in_class($cid, $u['id'])) {
            $pdo->prepare('DELETE FROM class_members WHERE class_id=? AND student_id=?')->execute([$cid, $u['id']]);
            flash('You left ' . class_name($c) . '. Your finished work stays in your grades. Joined the wrong class? Type the right class code above.');
        }
    } elseif ($u['role'] === 'student' && $do === 'join') {
        $ip = my_ip();
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($_POST['code'] ?? '')));
        // version 48: wrong class codes are counted for this student (5) and for the whole network (a class shares one internet connection)
        if (wrong_tries('join', 'u' . $u['id']) >= 5 || wrong_tries('join', $ip) >= IP_CODE_FAILS) {
            $err = 'Too many wrong codes. Please wait 15 minutes and try again.';
        } else {
            $s = $pdo->prepare('SELECT c.*,t.full_name teacher FROM classes c JOIN users t ON t.id=c.teacher_id WHERE c.join_code=? AND c.archived=0');
            $s->execute([$code]);
            $c = $s->fetch();
            if (!$c) {
                add_wrong_try('join', 'u' . $u['id']);
                add_wrong_try('join', $ip);
                $err = 'That class code is not right. Check it with your teacher (6 letters and numbers, for example K7Q2MX).';
            } elseif (in_class($c['id'], $u['id'])) {
                $err = 'You are already in ' . class_name($c) . '.';
            } else {
                $pdo->prepare('INSERT INTO class_members(class_id,student_id) VALUES(?,?)')->execute([$c['id'], $u['id']]);
                flash('You joined ' . class_name($c) . ' of ' . $c['teacher'] . '. Its quizzes and code exams are now on your list.');
                header('Location: ' . BASE . 'Classes/class.php?id=' . (int)$c['id']);
                exit;
            }
        }
    }
    if ($err === '') {
        header('Location: ' . BASE . 'Classes/classes.php');
        exit;
    }
}

head($u['role'] === 'teacher' ? 'My classes' : 'My classes');
flash();
if ($err) {
    echo '<p class="err">' . e($err) . '</p>';
}

// how many quizzes / exams each class has, and (students) how many are open and not done yet
function class_counts($cid, $sid = 0) {
    $w = work_rows($cid, $sid);
    $open = count(array_filter($w, fn($r) => in_array(work_state($r)[0], ['open', 'going'], true)));
    return [count($w), $open];
}

if ($u['role'] === 'teacher') {
    echo '<details class="card" ' . ($err || !my_classes($u, true) ? 'open' : '') . '><summary><b>+ Make a new class</b></summary>
<form method="post" style="margin-top:.6rem"><input type="hidden" name="t" value="' . csrf() . '"><input type="hidden" name="do" value="new">
<div class="two"><label>Subject<input name="name" required maxlength="100" placeholder="IT 102 Computer Programming 2" value="' . e($_POST['name'] ?? '') . '"></label>
<label>Section (optional)<input name="section" maxlength="40" placeholder="BSIT 1-A" value="' . e($_POST['section'] ?? '') . '"></label></div>
<label>Year level of the subject (optional, only shown as information)<select name="year"><option value="">Mixed / not set</option>';
    foreach ([1, 2, 3, 4] as $y) echo '<option value="' . $y . '">' . year_name($y) . '</option>';
    echo '</select></label><button>Make class</button> <small>You get a join code to give to your students.</small></form></details>';

    $cs = my_classes($u, true);
    if (!$cs) {
        echo '<p class="card">You have no class yet. Make one for every subject and section you teach. Then give the join code to the students: on the board, in your group chat, or on the first day of class.</p>';
    }
    $act = array_filter($cs, fn($c) => !$c['archived']);
    $old = array_filter($cs, fn($c) => $c['archived']);
    $card = function ($c) {
        [$n] = class_counts($c['id']);
        return '<div class="card ccard"><small>' . e($c['year_level'] ? year_name($c['year_level']) : 'Mixed year levels') . ($c['school_year'] ? ' &middot; ' . e($c['school_year']) : '') . '</small><h3>' . e($c['name']) . '</h3>'
            . ($c['section'] !== '' ? '<b>' . e($c['section']) . '</b>' : '')
            . '<small>' . (int)$c['members'] . ' student' . ($c['members'] == 1 ? '' : 's') . ' &middot; ' . $n . ' quiz' . ($n == 1 ? '' : 'zes') . ' and code exam' . ($n == 1 ? '' : 's') . '</small>'
            . '<div class="row"><span class="code6" title="Join code">' . e($c['join_code']) . '</span><a class="btn" href="Classes/class.php?id=' . (int)$c['id'] . '">Open</a></div></div>';
    };
    if ($act) {
        echo '<div class="cgrid">' . implode('', array_map($card, $act)) . '</div>';
    }
    if ($old) {
        echo '<details><summary>Archived classes (' . count($old) . ')</summary><div class="cgrid" style="margin-top:.8rem">' . implode('', array_map($card, $old)) . '</div></details>';
    }
} else {
    echo '<form method="post" class="card" style="max-width:none"><input type="hidden" name="t" value="' . csrf() . '"><input type="hidden" name="do" value="join">
<h2 style="margin-top:0">Join a class</h2><p><small>Type the class code your teacher gave you. Join every class of the subjects you take: an irregular student with 1st and 2nd year subjects joins all of those classes.</small></p>
<div class="inl"><input name="code" required maxlength="10" placeholder="Class code, for example K7Q2MX" autocomplete="off" style="max-width:20rem;text-transform:uppercase"><button>Join</button></div></form>';
    $cs = my_classes($u);
    if (!$cs) {
        echo '<p class="card">You are not in a class yet. When you join one, its quizzes and code exams show up here, on your dashboard and on the Quiz and Code Exam pages.</p>';
    } else {
        echo '<div class="cgrid">';
        foreach ($cs as $c) {
            [$n, $open] = class_counts($c['id'], $u['id']);
            echo '<div class="card ccard"><small>' . e($c['teacher']) . '</small><h3>' . e($c['name']) . '</h3>' . ($c['section'] !== '' ? '<b>' . e($c['section']) . '</b>' : '')
                . '<small>' . $n . ' quiz' . ($n == 1 ? '' : 'zes') . ' and code exam' . ($n == 1 ? '' : 's') . '</small>'
                . '<div class="row">' . ($open ? '<span class="st st-open">' . $open . ' open now</span>' : '<span class="st st-soon">Nothing open now</span>') . '<span class="inl" style="margin:0"><form method="post" style="margin:0" onsubmit="return confirm(\'Leave ' . e(addslashes(class_name($c))) . '? Use this if you joined the wrong class. You can join again with the class code.\')"><input type="hidden" name="t" value="' . csrf() . '"><input type="hidden" name="do" value="leave"><input type="hidden" name="id" value="' . (int)$c['id'] . '"><button class="alt">Leave</button></form><a class="btn" href="Classes/class.php?id=' . (int)$c['id'] . '">Open</a></span></div></div>';
        }
        echo '</div>';
    }
}
foot();
