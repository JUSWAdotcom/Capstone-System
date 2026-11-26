<?php require __DIR__.'/../config.php';$u=need('any');
// GRADES. Student: every quiz and code exam of his or her classes with Finished (green) or Missed (red) and the score.
// Teacher: choose a class and see its students with their quiz and code exam results in that class.
head($u['role']==='teacher'?'Grades':'My grades');flash();
$cn=[];foreach($pdo->query('SELECT id,name,section FROM classes') as $c)$cn[$c['id']]=class_name($c);
if($u['role']==='student'){
$w=array_values(array_filter(work_rows(0,$u['id']),fn($r)=>in_array(work_state($r)[0],['done','missed'],true)));
// work of classes the student left (still graded) is shown too
$s=$pdo->prepare("SELECT 'quiz' kind,q.id,q.title,q.class_id,q.opens_at,q.due_at,q.closed,a.id aid,a.submitted_at,a.score,a.total FROM attempts a JOIN quizzes q ON q.id=a.quiz_id WHERE a.student_id=? AND a.submitted_at IS NOT NULL UNION ALL SELECT 'exam',x.id,x.title,x.class_id,x.opens_at,x.due_at,x.closed,a.id,a.submitted_at,a.score,x.max_points FROM exam_attempts a JOIN coding_exams x ON x.id=a.exam_id WHERE a.student_id=? AND a.submitted_at IS NOT NULL");$s->execute([$u['id'],$u['id']]);
$seen=[];foreach($w as $r)$seen[$r['kind'].$r['id']]=1;foreach($s->fetchAll() as $r)if(!isset($seen[$r['kind'].$r['id']]))$w[]=$r;
if(!$w)echo '<p class="card">No finished or missed work yet. Your results show here after you submit a quiz or a code exam.</p>';
foreach(['quiz'=>'Quizzes','exam'=>'Code exams'] as $k=>$h){$rs=array_values(array_filter($w,fn($r)=>$r['kind']===$k));if(!$rs)continue;echo '<h2>'.$h.'</h2>';
table([$k==='quiz'?'Quiz':'Exam','Class','Status','Score','Percent'],array_map(function($r)use($cn,$k){[$st,$t]=work_state($r);$d=$st==='done';$sc=$d&&$r['score']!==null;
return [$k==='exam'&&$d?'<a href="Exam/review.php?id='.(int)$r['id'].'">'.e($r['title']).'</a>':e($r['title']),e($cn[$r['class_id']]??'-'),'<span class="st st-'.$st.'">'.$t.'</span>'.($d?'<br><small>'.e(when($r['submitted_at'])).'</small>':'<br><small>Deadline '.e(when($r['due_at'])).'</small>'),$d?($sc?'<b>'.(int)$r['score'].'/'.(int)$r['total'].'</b>':'Waiting for your teacher'):'<span class="err">0</span>',$sc?pct($r['score']/max($r['total'],1)*100):'-'];},$rs));}
$fb=$pdo->prepare('SELECT x.title,a.feedback FROM exam_attempts a JOIN coding_exams x ON x.id=a.exam_id WHERE a.student_id=? AND a.feedback IS NOT NULL AND a.feedback<>\'\' ORDER BY a.graded_at DESC');$fb->execute([$u['id']]);$fb=$fb->fetchAll();
if($fb){echo '<h2>Teacher feedback</h2>';table(['Exam','Feedback'],array_map(fn($r)=>[e($r['title']),e($r['feedback'])],$fb));}
}else{
// TEACHER GRADEBOOK: one class, one row per student, one column per quiz / code exam with the score (title on top).
// Filters: quizzes and/or code exams, and only the students who missed something. The last column lists what each student missed.
$cs=my_classes($u,true);$cf=(int)($_GET['class']??0);if($cf&&!teacher_class($cf,$u['id']))$cf=0;if(!$cf&&$cs)$cf=(int)$cs[0]['id'];
$show=in_array($_GET['show']??'',['quiz','exam'],true)?$_GET['show']:'all';$only=($_GET['only']??'')==='missed';
if(!$cs){echo '<p class="card">Make a class first on the <a href="Classes/classes.php">Classes</a> page. Grades are shown class by class.</p>';foot();exit;}
$sel=fn($n,$v,$t,$cur)=>'<option value="'.e($v).'"'.((string)$cur===(string)$v?' selected':'').'>'.e($t).'</option>';
echo '<form method="get" action="Grades/grades.php" class="filter"><label>Class <select name="class" onchange="this.form.submit()">';foreach($cs as $c)echo $sel('class',(int)$c['id'],class_name($c).($c['archived']?' (archived)':''),$cf);echo '</select></label>';
echo '<label>Show <select name="show" onchange="this.form.submit()">'.$sel('show','all','Quizzes and code exams',$show).$sel('show','quiz','Quizzes only',$show).$sel('show','exam','Code exams only',$show).'</select></label>';
echo '<label>Students <select name="only" onchange="this.form.submit()">'.$sel('only','','All students',$only?'missed':'').$sel('only','missed','Only students who missed something',$only?'missed':'').'</select></label><noscript><button>Show</button></noscript><a class="btn alt" href="Classes/class.php?id='.$cf.'">Open class</a></form>';
$work=array_values(array_filter(work_rows($cf),fn($r)=>$show==='all'||$r['kind']===$show));
usort($work,fn($a,$b)=>strtotime($a['due_at']??'2100-01-01')<=>strtotime($b['due_at']??'2100-01-01'));
$m=$pdo->prepare('SELECT u.id,u.full_name,u.student_id FROM class_members m JOIN users u ON u.id=m.student_id WHERE m.class_id=? ORDER BY u.full_name');$m->execute([$cf]);$ms=$m->fetchAll();
if(!$ms){echo '<p class="card">No student in this class yet.</p>';foot();exit;}
if(!$work){echo '<p class="card">No '.($show==='quiz'?'quiz':($show==='exam'?'code exam':'quiz or code exam')).' in this class yet.</p>';foot();exit;}
$rows=[];$cnt=['missed'=>0];
foreach($ms as $st){$mine=[];foreach(work_rows($cf,$st['id']) as $r)$mine[$r['kind'].$r['id']]=$r;$cells=[];$missed=[];$sum=0;$n=0;
foreach($work as $w){$r=$mine[$w['kind'].$w['id']]??$w;[$k]=work_state($r);
if($k==='done'){if($r['score']===null)$cells[]='<span class="st st-going">Not graded</span>';else{$p=$r['score']/max($r['total'],1)*100;$sum+=$p;$n++;$cells[]='<b class="sc">'.(int)$r['score'].'/'.(int)$r['total'].'</b><small>'.round($p).'%</small>';}}
elseif($k==='missed'){$missed[]=$w['title'];$cells[]='<span class="st st-missed">Missed</span>';}
elseif($k==='going')$cells[]='<span class="st st-going">In progress</span>';
elseif($k==='soon')$cells[]='<small>Not open yet</small>';
else $cells[]='<span class="st st-soon">Not yet</span>';}
if($only&&!$missed)continue;if($missed)$cnt['missed']++;
$rows[]=array_merge(['<b>'.e($st['full_name']).'</b><br><small>'.e($st['student_id']).'</small>'],$cells,[$n?'<b>'.round($sum/$n).'%</b>':'-',$missed?'<span class="st st-missed">'.count($missed).'</span><div class="ml">'.implode('',array_map(fn($t)=>'<small>'.e($t).'</small>',$missed)).'</div>':'<span class="st st-done">None</span>']);}
echo '<p><small>'.count($work).' '.($show==='quiz'?'quiz'.(count($work)==1?'':'zes'):($show==='exam'?'code exam'.(count($work)==1?'':'s'):'quizzes and code exams')).' &middot; '.count($ms).' student'.(count($ms)==1?'':'s').' &middot; <span class="err">'.$cnt['missed'].' missed something</span>. Click a title to see the answers'.($show!=='quiz'?' or to grade':'').'.</small></p>';
if(!$rows){echo '<p class="card ok">Nobody missed anything here.</p>';foot();exit;}
$head=array_merge(['Student'],array_map(fn($w)=>'<a href="'.($w['kind']==='quiz'?'Quiz/results.php?id=':'Exam/exam_results.php?id=').(int)$w['id'].'">'.e($w['title']).'</a><small>'.($w['kind']==='quiz'?'Quiz':'Code exam').($w['due_at']?' &middot; due '.e(date('M j',strtotime($w['due_at']))):'').'</small>',$work),['Average','Missed']);
echo '<div class="gb">';table($head,$rows);echo '</div>';}
foot();
