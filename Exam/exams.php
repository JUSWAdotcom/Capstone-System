<?php require __DIR__.'/../config.php';$u=need('any');
// CODE EXAMS. Teacher: every exam with its class, start time, deadline and how many are left to grade. Student: the exams of the classes he or she joined.
if($u['role']==='teacher'&&$_SERVER['REQUEST_METHOD']==='POST'){check();$d=(int)($_POST['del']??0);
$s=$pdo->prepare('DELETE FROM coding_exams WHERE id=? AND teacher_id=?');$s->execute([$d,$u['id']]);if($s->rowCount())flash('Code exam deleted.');
header('Location: '.BASE.'Exam/exams.php');exit;}
head($u['role']==='teacher'?'Code exams':'My code exams');flash();
if($u['role']==='teacher'){$cs=my_classes($u,true);$cf=(int)($_GET['class']??0);
echo '<div class="filter"><a class="btn" href="Exam/exam_new.php'.($cf?'?class='.$cf:'').'">+ Create code exam</a>';
if($cs){echo '<form method="get" action="Exam/exams.php"><label>Class <select name="class" onchange="this.form.submit()"><option value="">All my classes</option>';foreach($cs as $c)echo '<option value="'.(int)$c['id'].'"'.($cf===(int)$c['id']?' selected':'').'>'.e(class_name($c)).'</option>';echo '</select></label><noscript><button>Show</button></noscript></form>';}
echo '</div><p><small>Each code exam belongs to one class and has a start time and a deadline. Open one to see every student, look at the output in full screen and grade.</small></p>';
$s=$pdo->prepare('SELECT x.*,c.name cname,c.section csec,(SELECT COUNT(*) FROM exam_attempts a WHERE a.exam_id=x.id AND a.submitted_at IS NOT NULL) n,(SELECT COUNT(*) FROM exam_attempts a WHERE a.exam_id=x.id AND a.submitted_at IS NOT NULL AND a.score IS NULL) g,(SELECT COUNT(*) FROM class_members m WHERE m.class_id=x.class_id) members FROM coding_exams x LEFT JOIN classes c ON c.id=x.class_id WHERE x.teacher_id=?'.($cf?' AND x.class_id='.$cf:'').' ORDER BY (x.due_at IS NULL),x.due_at DESC,x.id DESC');$s->execute([$u['id']]);$rows=$s->fetchAll();
if($rows)table(['Exam','Class','Status','Language','Finished','To grade',''],array_map(fn($r)=>[e($r['title']).'<br><small>'.(int)$r['minutes'].' min, '.(int)$r['max_points'].' points</small>',$r['class_id']?e(class_name(['name'=>$r['cname'],'section'=>$r['csec']])):'<span class="st st-missed">No class</span>',teacher_state($r),e(lname($r['language'])),(int)$r['n'].' of '.(int)$r['members'],$r['g']?'<span class="st st-going">'.(int)$r['g'].'</span>':'0','<a href="Exam/exam_results.php?id='.$r['id'].'">Open and grade</a> <form method="post" class="inl" style="display:inline" onsubmit="return confirm(\'Delete this code exam and every student code and score in it?\')"><input type="hidden" name="t" value="'.csrf().'"><input type="hidden" name="del" value="'.$r['id'].'"><button class="alt">Delete</button></form>'],$rows));
else echo '<p class="card">No code exam yet'.($cf?' in this class':'').'. Press <b>+ Create code exam</b>.</p>';}
else{$w=array_values(array_filter(work_rows(0,$u['id']),fn($r)=>$r['kind']==='exam'));
if(!my_classes($u))echo '<p class="card">You are not in a class yet. <a href="Classes/classes.php">Join your classes</a> with the class codes from your teachers, then their code exams show here.</p>';
elseif(!$w)echo '<p class="card">No code exam in your classes yet. When a teacher posts one you get a notification and it shows here.</p>';
else{echo work_list($w,true);
$fb=$pdo->prepare('SELECT x.title,a.feedback FROM exam_attempts a JOIN coding_exams x ON x.id=a.exam_id WHERE a.student_id=? AND a.feedback IS NOT NULL AND a.feedback<>\'\' ORDER BY a.graded_at DESC LIMIT 5');$fb->execute([$u['id']]);$fb=$fb->fetchAll();
if($fb){echo '<h2>Teacher feedback</h2>';table(['Exam','Feedback'],array_map(fn($r)=>[e($r['title']),e($r['feedback'])],$fb));}}}
foot();
