<?php require __DIR__.'/../config.php';$u=need('student');$id=(int)($_GET['id']??0);
$s=$pdo->prepare('SELECT a.id,a.score,a.total,a.submitted_at,q.title,q.show_answers FROM attempts a JOIN quizzes q ON q.id=a.quiz_id WHERE a.quiz_id=? AND a.student_id=? AND a.submitted_at IS NOT NULL');$s->execute([$id,$u['id']]);$a=$s->fetch();
if(!$a){header('Location: '.BASE.'Quiz/quizzes.php');exit;}
head('Result: '.$a['title']);$p=round($a['score']/max($a['total'],1)*100);
echo '<div class="card"><small>Your score (checked automatically)</small><div class="big">'.(int)$a['score'].' / '.(int)$a['total'].'</div><div class="pb"><i style="width:'.$p.'%"></i></div><b>'.$p.'%</b> <small>submitted '.e($a['submitted_at']).'</small></div>';
$s=$pdo->prepare('SELECT q.id,q.question,q.opt_a,q.opt_b,q.opt_c,q.opt_d,q.correct,qa.choice FROM questions q LEFT JOIN quiz_answers qa ON qa.question_id=q.id AND qa.attempt_id=? WHERE q.quiz_id=? ORDER BY q.id');$s->execute([$a['id'],$id]);
echo '<h2>Your answers</h2>';
foreach($s->fetchAll() as $i=>$r){$ok=$r['choice']!==null&&strtoupper($r['choice'])===strtoupper($r['correct']);
echo '<div class="card"><b>'.($i+1).'. '.e($r['question']).'</b> '.($ok?'<span class="ok">✔ Correct</span>':'<span class="err">✘ '.($r['choice']===null?'No answer':'Wrong').'</span>');
foreach(['A','B','C','D'] as $L){$cls='';$tag='';if($r['choice']===$L){$cls=$ok?' good':' wrong';$tag=' <small>(your answer)</small>';}
if($a['show_answers']&&$L===strtoupper($r['correct'])){$cls=' good';$tag.=' <small>(correct answer)</small>';}
echo '<div class="ans'.$cls.'">'.$L.'. '.e($r['opt_'.strtolower($L)]).$tag.'</div>';}
echo '</div>';}
echo '<p><a class="btn alt" href="Quiz/quizzes.php">Back to quizzes</a></p>';foot();
