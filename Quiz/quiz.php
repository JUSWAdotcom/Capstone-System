<?php require __DIR__.'/../config.php';$u=need('student');$id=(int)($_GET['id']??$_POST['id']??0);
$q=$pdo->prepare('SELECT * FROM quizzes WHERE id=?');$q->execute([$id]);$z=$q->fetch();if(!$z)die('Quiz not found');
$a=$pdo->prepare('SELECT * FROM attempts WHERE quiz_id=? AND student_id=?');$a->execute([$id,$u['id']]);$at=$a->fetch();
if($at&&$at['submitted_at']){header('Location: '.BASE.'Quiz/quiz_result.php?id='.$id);exit;}
$s=$pdo->prepare('SELECT * FROM questions WHERE quiz_id=? ORDER BY id');$s->execute([$id]);$qs=$s->fetchAll();
if(!$qs){head($z['title']);echo '<p>This quiz has no questions yet.</p>';foot();exit;}
// only the students of the quiz's class, only between the start time and the deadline
$due=$z['due_at']?strtotime($z['due_at']):0;
if(!$at&&(!$z['class_id']||!in_class($z['class_id'],$u['id'])))blocked_page('Not your class','This quiz is for the students of another class. Join the class first on the <a href="Classes/classes.php">Classes</a> page.','Quiz/quizzes.php');
if($_SERVER['REQUEST_METHOD']==='POST'){check();
if(!$at||($due&&time()>$due+GRACE))blocked_page('Missed','The deadline of this quiz was '.e(when($z['due_at'])).', so your answers were not accepted.','Quiz/quizzes.php');
$sc=0;$ans=[];$pa=is_array($_POST['a']??null)?$_POST['a']:[];
foreach($qs as $r){$c=strtoupper((string)($pa[$r['id']]??''));if(!preg_match('/^[A-D]$/',$c))$c='';if($c!=='')$ans[$r['id']]=$c;if($c!==''&&$c===strtoupper($r['correct']))$sc++;}
$pdo->beginTransaction();try{ // version 48: the score and all answers are saved together (all or nothing), with one write to the disk
$up=$pdo->prepare('UPDATE attempts SET score=?,total=?,submitted_at=NOW() WHERE id=? AND submitted_at IS NULL');$up->execute([$sc,count($qs),$at['id']]);
if($up->rowCount()){$ia=$pdo->prepare('INSERT INTO quiz_answers(attempt_id,question_id,choice) VALUES(?,?,?)');foreach($ans as $qid=>$c)$ia->execute([$at['id'],$qid,$c]);}
$pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
header('Location: '.BASE.'Quiz/quiz_result.php?id='.$id);exit;}
$st=work_state($z+['aid'=>$at?$at['id']:null]);
if($st[0]==='soon')blocked_page('Not open yet','This quiz opens on <b>'.e(when($z['opens_at'])).'</b> (<span class="cd" data-t="'.strtotime($z['opens_at']).'" data-pre="in "></span>). The page opens it by itself when the time comes.','Quiz/quizzes.php');
if($st[0]==='missed')blocked_page('Missed','The deadline of this quiz was <b>'.e(when($z['due_at'])).'</b>. It can no longer be taken.','Quiz/quizzes.php');
if(!$at){$pdo->prepare('INSERT INTO attempts(quiz_id,student_id,year_level) VALUES(?,?,?)')->execute([$id,$u['id'],$u['year_level']]);$at=['id'=>$pdo->lastInsertId()];}
head($z['title']);
echo '<div class="card" id="intro">'.($due?'<p><b>Deadline: '.e(when($z['due_at'])).'</b> &middot; <span class="cd" data-t="'.$due.'" data-post=" left" data-noreload></span>. When the deadline comes, the quiz is submitted by itself.</p>':'').'<p>Choose one answer (A, B, C or D) for every question. Your score is checked and calculated automatically when you submit.</p><p>Copying, pasting, right-click and shortcuts are turned off. Leaving this tab or full screen is shown to your teacher, and leaving 3 times submits the quiz. When you press Submit quiz you will be asked to confirm. After you submit, the lock is released.</p><button id="start" type="button">Start quiz in full screen</button></div><form method="post" id="quiz" hidden data-what="quiz" data-confirm="Are you sure you want to submit your quiz? You cannot change your answers after you submit." data-attempt="'.$at['id'].'" data-t="'.csrf().'"><input type="hidden" name="t" value="'.csrf().'"><input type="hidden" name="id" value="'.$id.'">';
if($due)echo '<p class="card"><b>Deadline '.e(when($z['due_at'])).'</b> &middot; <span class="cd" data-t="'.$due.'" data-post=" left" data-noreload></span></p>';
foreach($qs as $i=>$r){echo '<fieldset><legend>'.($i+1).'. '.e($r['question']).'</legend>';foreach(['A','B','C','D'] as $L)echo '<label class="opt"><input type="radio" name="a['.$r['id'].']" value="'.$L.'" required> '.$L.'. '.e($r['opt_'.strtolower($L)]).'</label>';echo '</fieldset>';}
echo '<button>Submit quiz</button></form><div id="warn" hidden></div><script src="assets/anticheat.js?v=48.1"></script><script>(function(){var DUE='.($due*1000).',SK='.(time()*1000).'-Date.now(),go=false;
document.getElementById("start").onclick=function(){AC.start();go=true;document.getElementById("quiz").hidden=false;document.getElementById("intro").hidden=true};
if(DUE)setInterval(function(){if(Date.now()+SK>=DUE){if(go)AC.submitNow();else location.reload()}},1000)})()</script>';foot();
