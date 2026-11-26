<?php require __DIR__.'/../config.php';$u=need('any');
// QUIZZES. Teacher: every quiz with its class, start time, deadline and results. Student: the quizzes of the classes he or she joined.
if($u['role']==='teacher'&&$_SERVER['REQUEST_METHOD']==='POST'){check();$d=(int)($_POST['del']??0);
$s=$pdo->prepare('DELETE FROM quizzes WHERE id=? AND teacher_id=?');$s->execute([$d,$u['id']]);
if($s->rowCount()){$pdo->prepare("DELETE FROM notifications WHERE kind='quiz' AND link=?")->execute(['Quiz/quiz.php?id='.$d]);flash('Quiz deleted.');}
header('Location: '.BASE.'Quiz/quizzes.php');exit;}
head($u['role']==='teacher'?'Quizzes':'My quizzes');flash();
if($u['role']==='teacher'){$cs=my_classes($u,true);$cf=(int)($_GET['class']??0);
echo '<div class="filter"><a class="btn" href="Quiz/quiz_new.php'.($cf?'?class='.$cf:'').'">+ Create quiz</a>';
if($cs){echo '<form method="get" action="Quiz/quizzes.php"><label>Class <select name="class" onchange="this.form.submit()"><option value="">All my classes</option>';foreach($cs as $c)echo '<option value="'.(int)$c['id'].'"'.($cf===(int)$c['id']?' selected':'').'>'.e(class_name($c)).'</option>';echo '</select></label><noscript><button>Show</button></noscript></form>';}
echo '</div><p><small>Each quiz belongs to one class and has a start time and a deadline. Answers are checked against your answer key automatically.</small></p>';
$s=$pdo->prepare('SELECT q.id,q.title,q.class_id,q.opens_at,q.due_at,q.closed,c.name cname,c.section csec,(SELECT COUNT(*) FROM questions WHERE quiz_id=q.id) n,(SELECT COUNT(*) FROM attempts a WHERE a.quiz_id=q.id AND a.submitted_at IS NOT NULL) s,(SELECT COUNT(*) FROM class_members m WHERE m.class_id=q.class_id) members,(SELECT AVG(a.score/NULLIF(a.total,0)*100) FROM attempts a WHERE a.quiz_id=q.id AND a.submitted_at IS NOT NULL) av FROM quizzes q LEFT JOIN classes c ON c.id=q.class_id WHERE q.teacher_id=?'.($cf?' AND q.class_id='.$cf:'').' ORDER BY (q.due_at IS NULL),q.due_at DESC,q.id DESC');$s->execute([$u['id']]);$rows=$s->fetchAll();
if($rows)table(['Quiz','Class','Status','Questions','Finished','Average',''],array_map(fn($r)=>[e($r['title']),$r['class_id']?e(class_name(['name'=>$r['cname'],'section'=>$r['csec']])):'<span class="st st-missed">No class</span>',teacher_state($r),(int)$r['n'],(int)$r['s'].' of '.(int)$r['members'],pct($r['av']),'<a href="Quiz/results.php?id='.$r['id'].'">Results</a> <form method="post" class="inl" style="display:inline" onsubmit="return confirm(\'Delete this quiz and all of its results?\')"><input type="hidden" name="t" value="'.csrf().'"><input type="hidden" name="del" value="'.$r['id'].'"><button class="alt">Delete</button></form>'],$rows));
else echo '<p class="card">No quiz yet'.($cf?' in this class':'').'. Press <b>+ Create quiz</b>, paste your questions and pick the answer key.</p>';}
else{$w=array_values(array_filter(work_rows(0,$u['id']),fn($r)=>$r['kind']==='quiz'));
if(!my_classes($u))echo '<p class="card">You are not in a class yet. <a href="Classes/classes.php">Join your classes</a> with the class codes from your teachers, then their quizzes show here.</p>';
elseif(!$w)echo '<p class="card">No quiz in your classes yet. When a teacher posts one you get a notification and it shows here.</p>';
else echo work_list($w,true);}
foot();
