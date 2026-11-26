<?php
// Teacher page: start a new school year. Moves the students up one year level (1st -> 2nd -> 3rd -> 4th -> Graduated).
require __DIR__.'/../config.php';
$u = need('staff'); // a teacher or the admin
$error = '';
$cur = school_year();   // the school year that is running now, for example 2026-2027 (null = never set)

// the quizzes and exams of last year are marked closed, and students keep their old results
function id_list($text) {
    $ids = [];
    foreach (explode(',', (string)$text) as $n) {
        if ((int)$n > 0) {
            $ids[] = (int)$n;
        }
    }
    return $ids;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $act = $_POST['act'] ?? '';

    // ---------- 1. the school year was never set: set it once ----------
    if ($act === 'set' && !$cur) {
        $label = trim($_POST['sy'] ?? '');
        if (!valid_sy($label)) {
            $error = 'Type the school year like 2026-2027 (the second year is one more than the first).';
        } else {
            try {
                $pdo->prepare("INSERT INTO settings(k,v) VALUES('school_year',?)")->execute([$label]);
                // the quizzes and exams made so far belong to this school year
                $pdo->prepare('UPDATE quizzes SET school_year=? WHERE school_year IS NULL')->execute([$label]);
                $pdo->prepare('UPDATE coding_exams SET school_year=? WHERE school_year IS NULL')->execute([$label]);
                flash('The school year is now '.$label.'.');
            } catch (PDOException $x) {
                // pressed twice: it is already saved
            }
            header('Location: '.BASE.'Grades/promote.php');
            exit;
        }
    }

    // ---------- 2. move the students up ----------
    elseif ($act === 'promote' && $cur) {
        $new = trim($_POST['sy'] ?? '');
        $ids = array_unique(array_map('intval', (array)($_POST['up'] ?? [])));
        $grad = isset($_POST['grad']) ? 1 : 0;
        $close = isset($_POST['close']);
        if (!valid_sy($new) || (int)substr($new, 0, 4) <= (int)substr($cur, 0, 4)) {
            $error = 'The new school year must come after '.$cur.' (for example '.(((int)substr($cur, 0, 4)) + 1).'-'.(((int)substr($cur, 0, 4)) + 2).').';
        } elseif (!isset($_POST['sure'])) {
            $error = 'Tick the box "I checked the list" before you start the new school year.';
        } else {
            $pdo->beginTransaction();
            try {
                // 0. change the school year first. It only works if it is still the old one, so a second click (or a second teacher) cannot move everybody twice.
                $first = $pdo->prepare("UPDATE settings SET v=? WHERE k='school_year' AND v=?");
                $first->execute([$new, $cur]);
                if (!$first->rowCount()) {
                    throw new RuntimeException('again');
                }
                // 1. remember in which year level each student did the old work
                $pdo->exec('UPDATE attempts SET year_level=(SELECT year_level FROM users WHERE users.id=attempts.student_id) WHERE year_level IS NULL');
                $pdo->exec('UPDATE exam_attempts SET year_level=(SELECT year_level FROM users WHERE users.id=exam_attempts.student_id) WHERE year_level IS NULL');

                // 2. close the quizzes and exams that are still open
                $cq = [];
                $ce = [];
                $moved = 0;
                if ($close) {
                    $cq = $pdo->query('SELECT id FROM quizzes WHERE closed=0')->fetchAll(PDO::FETCH_COLUMN);
                    $ce = $pdo->query('SELECT id FROM coding_exams WHERE closed=0')->fetchAll(PDO::FETCH_COLUMN);
                    if ($cq) {
                        $pdo->exec('UPDATE quizzes SET closed=2 WHERE id IN ('.implode(',', array_map('intval', $cq)).')');   // 2 = the school year ended
                    }
                    if ($ce) {
                        $pdo->exec('UPDATE coding_exams SET closed=2 WHERE id IN ('.implode(',', array_map('intval', $ce)).')');
                    }
                }
                $pdo->prepare('INSERT INTO promotions(from_year,to_year,graduate,closed_quizzes,closed_exams) VALUES(?,?,?,?,?)')
                    ->execute([$cur, $new, $grad, implode(',', $cq), implode(',', $ce)]);
                $pid = (int)$pdo->lastInsertId();

                // 3. move the ticked students
                $get = $pdo->prepare("SELECT year_level FROM users WHERE id=? AND role='student'");
                $set = $pdo->prepare('UPDATE users SET year_level=? WHERE id=? AND year_level=?');
                $log = $pdo->prepare('INSERT INTO year_log(promotion_id,user_id,old_year,new_year) VALUES(?,?,?,?)');
                foreach ($ids as $id) {
                    $get->execute([$id]);
                    $old = (int)$get->fetchColumn();
                    if ($old < 1 || $old > 4) {
                        continue;
                    }
                    if ($old < 4) {
                        $to = $old + 1;
                    } elseif ($grad) {
                        $to = 5;
                    } else {
                        continue;   // a 4th year student who stays in 4th year
                    }
                    $set->execute([$to, $id, $old]);
                    if (!$set->rowCount()) {
                        continue;
                    }
                    $log->execute([$pid, $id, $old, $to]);
                    $text = $to == 5 ? 'Congratulations! School year '.$new.' started and you are now marked as Graduated.'
                                     : 'School year '.$new.' started. You are now in '.year_name($to).'.';
                    notify_user($id, 'year', $text, 'Dashboard/dashboard.php');
                    $moved++;
                }

                $pdo->commit();
            } catch (Throwable $x) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($x->getMessage() !== 'again') {
                    throw $x;
                }
                $moved = -1;
            }
            if ($moved < 0) {
                flash('The school year was already changed (the button was pressed twice?). Nothing was moved a second time.',true);
                header('Location: '.BASE.'Grades/promote.php');
                exit;
            }
            flash('School year '.$new.' started. '.$moved.' student'.($moved == 1 ? ' was' : 's were').' moved up'.($close ? ', '.count($cq).' quiz'.(count($cq) == 1 ? '' : 'zes').' and '.count($ce).' code exam'.(count($ce) == 1 ? '' : 's').' of the last school year were closed' : '').'. Old results are kept.');
            header('Location: '.BASE.'Grades/promote.php');
            exit;
        }
    }

    // ---------- 3. undo the last time ----------
    elseif ($act === 'undo') {
        $p = $pdo->query('SELECT * FROM promotions WHERE undone=0 ORDER BY id DESC LIMIT 1')->fetch();
        if (!$p || $p['to_year'] !== $cur) {
            $error = 'There is nothing to undo.';
        } else {
            $pdo->beginTransaction();
            try {
                $once = $pdo->prepare('UPDATE promotions SET undone=1 WHERE id=? AND undone=0');   // a second click does nothing
                $once->execute([$p['id']]);
                if (!$once->rowCount()) {
                    throw new RuntimeException('again');
                }
                $back = $pdo->prepare('UPDATE users SET year_level=? WHERE id=? AND year_level=?');   // only when nobody changed the year since
                $gone = $pdo->prepare("DELETE FROM notifications WHERE user_id=? AND kind='year' AND read_at IS NULL AND message LIKE ?");
                $rows = $pdo->prepare('SELECT user_id,old_year,new_year FROM year_log WHERE promotion_id=?');
                $rows->execute([$p['id']]);
                $n = 0;
                foreach ($rows->fetchAll() as $r) {
                    $back->execute([$r['old_year'], $r['user_id'], $r['new_year']]);
                    if ($back->rowCount()) {
                        $n++;
                        $gone->execute([$r['user_id'], '%'.$p['to_year'].'%']);
                        if (!$gone->rowCount()) {   // the student already read the notice: tell about the correction
                            notify_user($r['user_id'], 'year', 'Your year level was put back to '.year_name($r['old_year']).'. Your teacher corrected a mistake.', 'Dashboard/dashboard.php');
                        }
                    }
                }
                $q = id_list($p['closed_quizzes']);
                $x = id_list($p['closed_exams']);
                if ($q) {
                    $pdo->exec('UPDATE quizzes SET closed=0 WHERE id IN ('.implode(',', $q).')');
                }
                if ($x) {
                    $pdo->exec('UPDATE coding_exams SET closed=0 WHERE id IN ('.implode(',', $x).')');
                }
                $pdo->prepare('UPDATE quizzes SET school_year=? WHERE school_year=?')->execute([$p['from_year'], $p['to_year']]);
                $pdo->prepare('UPDATE coding_exams SET school_year=? WHERE school_year=?')->execute([$p['from_year'], $p['to_year']]);
                $pdo->prepare("UPDATE settings SET v=? WHERE k='school_year'")->execute([$p['from_year']]);
                $pdo->commit();
            } catch (Throwable $x) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($x->getMessage() !== 'again') {
                    throw $x;
                }
                header('Location: '.BASE.'Grades/promote.php');
                exit;
            }
            flash('Undone. The school year is '.$p['from_year'].' again and '.$n.' student'.($n == 1 ? ' is' : 's are').' back in the old year level.');
            header('Location: '.BASE.'Grades/promote.php');
            exit;
        }
    }
}

head('New school year');
flash();
if ($error) {
    echo '<p class="err">'.e($error).'</p>';
}
$T = '<input type="hidden" name="t" value="'.csrf().'">';

if (!$cur) {
    // the school year was never set
    echo '<form method="post" class="card" style="max-width:480px">'.$T.'<input type="hidden" name="act" value="set">
<p>Before you can move students up, tell the system which school year is running now. Every quiz and code exam you made so far is marked with it,
and so are the ones you make next. Later you use this page to start the next school year.</p>
<label>Current school year (example 2026-2027)<input name="sy" required pattern="\d{4}-\d{4}" maxlength="9" placeholder="2026-2027" value="'.e($_POST['sy'] ?? '').'"></label>
<button>Save school year</button></form>';
    foot();
    exit;
}

$first = (int)substr($cur, 0, 4);
echo '<p>School year now: <b>'.e($cur).'</b>. At the end of the school year use this page. Every student that you leave ticked moves up one year level
(1st to 2nd, 2nd to 3rd, 3rd to 4th). Untick the students who repeat the year. Quiz and exam results stay in the system for every student.</p>
<p><small>Tip: archive the classes of the last semester on the Classes page and make new classes for the new school year.</small></p>';

// right after a promotion the list is folded (so nobody starts another school year by mistake)
$last = $pdo->query('SELECT * FROM promotions ORDER BY id DESC LIMIT 5')->fetchAll();
$fold = $last && !$last[0]['undone'] && strtotime($last[0]['created_at']) > time() - 60 * 86400;
// the students who can move
$st = $pdo->query("SELECT id,full_name,student_id,year_level FROM users WHERE role='student' AND year_level BETWEEN 1 AND 4 ORDER BY year_level,full_name")->fetchAll();
$other = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student' AND (year_level IS NULL OR year_level=5)")->fetchColumn();
if (!$st) {
    echo '<p class="card">No student has a year level between 1st and 4th year yet, so there is nobody to move.</p>';
} else {
    echo '<details'.($fold ? '' : ' open').'><summary><b>'.($fold ? 'Start another school year (open this at the end of the next school year)' : 'Move the students up').'</b></summary>';
    echo '<form method="post">'.$T.'<input type="hidden" name="act" value="promote">';
    echo '<p><button type="button" class="alt" onclick="document.querySelectorAll(\'input[name=&quot;up[]&quot;]\').forEach(function(c){c.checked=true})">Tick all</button> ';
    echo '<button type="button" class="alt" onclick="document.querySelectorAll(\'input[name=&quot;up[]&quot;]\').forEach(function(c){c.checked=false})">Untick all</button></p>';
    table(['Student','Student ID','Now','After','Move up?'], array_map(function ($r) {
        $y = (int)$r['year_level'];
        return [e($r['full_name']), e($r['student_id']), e(year_name($y)), $y < 4 ? e(year_name($y + 1)) : 'Graduated <small>(or 4th year, see below)</small>',
                '<label class="opt"><input type="checkbox" name="up[]" value="'.(int)$r['id'].'" checked> Yes</label>'];
    }, $st));
    if ($other) {
        echo '<p><small>'.$other.' other student'.($other == 1 ? ' has' : 's have').' no year level yet or already graduated. They are not changed.</small></p>';
    }
    echo '<div class="card" style="max-width:none">
<label>New school year (example '.($first + 1).'-'.($first + 2).')<input name="sy" required pattern="\d{4}-\d{4}" maxlength="9" value="'.e($_POST['sy'] ?? ($first + 1).'-'.($first + 2)).'"></label>
<label class="opt"><input type="checkbox" name="grad" value="1" checked> <span>4th year students become <b>Graduated</b> (they keep their account and results, but get no new quizzes or exams). Untick to keep them in 4th year.</span></label>
<label class="opt"><input type="checkbox" name="close" value="1" checked> <span>Close the quizzes and code exams of the last school year. Students cannot start them or go on with them any more, and new students do not get old exams. Finished results stay. You can open any of them again.</span></label>
<label class="opt"><input type="checkbox" name="sure" value="1"> I checked the list</label>
<p><button>Start the new school year</button> <small>You can undo it right after, on this page.</small></p></div></form></details>';
}

// the last time + undo
if ($last) {
    echo '<h2>Last school year changes</h2>';
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM year_log WHERE promotion_id=?');
    $latest = (int)$last[0]['id'];
    table(['Date', 'School year', 'Students moved', 'Closed', ''], array_map(function ($r) use ($cnt, $cur, $T, $latest) {
        $cnt->execute([$r['id']]);
        $m = (int)$cnt->fetchColumn();
        $nq = count(id_list($r['closed_quizzes']));
        $ne = count(id_list($r['closed_exams']));
        $closed = $nq.($nq == 1 ? ' quiz, ' : ' quizzes, ').$ne.($ne == 1 ? ' exam' : ' exams');
        $undo = '';
        if ($r['undone']) {
            $undo = '<small>Undone</small>';
        } elseif ($r['to_year'] === $cur && (int)$r['id'] === $latest) {
            $undo = '<form method="post" class="inl" onsubmit="return confirm(\'Undo this change? The students go back to their old year level.\')">'.$T.'<input type="hidden" name="act" value="undo"><button class="alt">Undo</button></form>';
        }
        return [e(substr($r['created_at'], 0, 10)), e($r['from_year']).' to '.e($r['to_year']), $m, e($closed), $undo];
    }, $last));
}
foot();
