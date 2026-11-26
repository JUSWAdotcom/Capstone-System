<?php require __DIR__.'/../config.php';$u=need('any');
function qrows($pdo,$sql,$p=[]){$s=$pdo->prepare($sql);$s->execute($p);return $s->fetchAll();}
if($_SERVER['REQUEST_METHOD']==='POST'){check();$sid=trim((string)($_POST['sid']??''));
if(isset($_POST['sid'])){if(!preg_match('/^[A-Za-z0-9._-]{3,30}$/',$sid))flash('An ID has 3 to 30 letters, numbers, dot, dash or underscore.',true);elseif(strcasecmp($sid,ADMIN_ID)===0)flash('That ID is already used by another account.',true);else{try{$pdo->prepare('UPDATE users SET student_id=? WHERE id=? AND student_id IS NULL')->execute([$sid,$u['id']]);flash('Your ID was saved. You can log in with it now.');}catch(PDOException $x){flash('That ID is already used by another account.',true);}}} // version 48: tell why an ID was not saved
if($u['role']==='student'&&isset($_POST['year'])){$y=(int)$_POST['year'];if($y>=1&&$y<=4)$pdo->prepare('UPDATE users SET year_level=? WHERE id=? AND year_level IS NULL')->execute([$y,$u['id']]);} // only when no year is set yet
header('Location: '.BASE.'Dashboard/dashboard.php');exit;}
$me=qrows($pdo,'SELECT full_name,student_id,year_level,email,created_at FROM users WHERE id=?',[$u['id']])[0];
if($u['role']==='student'){
// TO DO: the quizzes and code exams of my classes that are open, started or opening soon (the left menu is for moving around)
$w=work_rows(0,$u['id']);$st=fn($k)=>array_values(array_filter($w,fn($r)=>work_state($r)[0]===$k));
$todo=array_values(array_filter($w,fn($r)=>in_array(work_state($r)[0],['going','open','soon'],true)));$done=$st('done');$missed=$st('missed');
$qd=array_filter($done,fn($r)=>$r['kind']==='quiz');$avg=$qd?round(array_sum(array_map(fn($r)=>$r['score']/max($r['total'],1)*100,$qd))/count($qd)):null;
$cr=qrows($pdo,'SELECT c.language,COUNT(*) t,SUM(s.user_id IS NOT NULL) d FROM challenges c LEFT JOIN challenge_solves s ON s.challenge_id=c.id AND s.user_id=? GROUP BY c.language',[$u['id']]);$by=[];foreach($cr as $r)$by[$r['language']]=$r;
$T=array_sum(array_column($cr,'t'));$D=array_sum(array_column($cr,'d'));$cp=$T?round($D/$T*100):0;
head('My dashboard');flash();echo infopanel($me,'student');
echo '<h2>To do</h2>';
if(!my_classes($u))echo '<p class="card">You are not in a class yet. <a href="Classes/classes.php">Join your classes</a> with the class codes from your teachers. Their quizzes and code exams will show here with the deadline.</p>';
elseif($todo)echo work_list($todo,true);else echo '<p class="card">You are all caught up. New quizzes and code exams from your teachers will show here.</p>';
echo '<h2>My progress</h2><div class="stats"><div class="card"><h3>Finished</h3><div class="big" style="color:var(--ok)">'.count($done).'</div><small>quizzes and code exams submitted on time</small></div>';
echo '<div class="card"><h3>Missed</h3><div class="big" style="color:var(--bad)">'.count($missed).'</div><small>not submitted before the deadline</small></div>';
echo '<div class="card"><h3>Quiz average</h3><div class="big">'.($avg===null?'-':$avg.'%').'</div><small>'.count($qd).' quiz'.(count($qd)==1?'':'zes').' finished</small></div>';
echo '<div class="card"><h3>Challenges</h3><div class="big">'.$cp.'%</div><div class="pb"><i style="width:'.$cp.'%"></i></div><small>'.$D.' of '.$T.' complete</small></div></div>';
if($missed){echo '<details><summary><b class="err">Missed ('.count($missed).')</b></summary>'.work_list($missed,true).'</details>';}
echo '<h2>Challenge progress</h2><div class="grid">';
foreach(lang6() as $l){$t=(int)($by[$l]['t']??0);$d=(int)($by[$l]['d']??0);$p=$t?round($d/$t*100):0;
echo '<a class="card" href="Challenges/challenges.php?lang='.e($l).'"><h3 class="hl">'.lb($l).e(lname($l)).'<span class="pct">'.$p.'%</span></h3><div class="pb"><i style="width:'.$p.'%"></i></div><small>'.$d.' of '.$t.' complete</small></a>';}
echo '</div>';
}else{
// TEACHER: choose which class to look at, so the work of different year levels / sections is not mixed
$cs=my_classes($u);$cf=(int)($_GET['class']??0);if($cf&&!teacher_class($cf,$u['id']))$cf=0;
$ids=$cf?[$cf]:array_map(fn($c)=>(int)$c['id'],$cs);$in=$ids?implode(',',$ids):'0';
$c=fn($sql)=>(int)qrows($pdo,$sql,[$u['id']])[0]['c'];
$nq=$c("SELECT COUNT(*) c FROM quizzes WHERE teacher_id=? AND class_id IN ($in)");$ne=$c("SELECT COUNT(*) c FROM coding_exams WHERE teacher_id=? AND class_id IN ($in)");
$tg=$c("SELECT COUNT(*) c FROM exam_attempts a JOIN coding_exams x ON x.id=a.exam_id WHERE x.teacher_id=? AND x.class_id IN ($in) AND a.submitted_at IS NOT NULL AND a.score IS NULL");
$ns=(int)$pdo->query("SELECT COUNT(DISTINCT student_id) FROM class_members WHERE class_id IN ($in)")->fetchColumn();
head('Teacher dashboard');flash();echo infopanel($me,'teacher');
$pa=pending_count($u);if($pa)echo '<p class="card"><b>'.$pa.' new '.($pa==1?'student is':'students are').' waiting for your approval.</b> They cannot log in until a teacher approves them. <a href="Dashboard/approve.php">Check and approve</a></p>';
if(!$cs)echo '<p class="card"><b>Make your first class.</b> Every quiz and code exam belongs to a class, and students join with its join code. <a href="Classes/classes.php">Go to Classes</a></p>';
else{echo '<form method="get" action="Dashboard/dashboard.php" class="filter"><label>Viewing <select name="class" onchange="this.form.submit()"><option value="">All my classes</option>';foreach($cs as $k)echo '<option value="'.(int)$k['id'].'"'.($cf===(int)$k['id']?' selected':'').'>'.e(class_name($k)).($k['year_level']?' ('.e(year_name($k['year_level'])).')':'').'</option>';echo '</select></label><noscript><button>Show</button></noscript>'.($cf?'<a class="btn alt" href="Classes/class.php?id='.$cf.'">Open this class</a>':'').'</form>';}
echo '<div class="stats"><div class="card"><h3>Students</h3><div class="big">'.$ns.'</div><small>in '.($cf?'this class':count($cs).' class'.(count($cs)==1?'':'es')).'</small></div><div class="card"><h3>Quizzes</h3><div class="big">'.$nq.'</div></div><div class="card"><h3>Code exams</h3><div class="big">'.$ne.'</div></div><div class="card"><h3>To grade</h3><div class="big">'.$tg.'</div><small>code exam answers</small></div></div>';
// open now / opening soon in the chosen class(es), with how many finished
$open=[];foreach($ids as $k)foreach(work_rows($k) as $r)if(empty($r['due_at'])||time()<strtotime($r['due_at']))$open[]=$r;
usort($open,fn($a,$b)=>strtotime($a['due_at']??'2100-01-01')<=>strtotime($b['due_at']??'2100-01-01'));
if($open){$cn=[];foreach($cs as $k)$cn[$k['id']]=class_name($k);echo '<h2>Open and coming up</h2>';
table(['','Title','Class','Status','Finished'],array_map(function($r)use($pdo,$cn){$q=$r['kind']==='quiz';$t=$q?'attempts a WHERE a.quiz_id=?':'exam_attempts a WHERE a.exam_id=?';$s=$pdo->prepare("SELECT COUNT(*) FROM $t AND a.submitted_at IS NOT NULL");$s->execute([$r['id']]);$m=$pdo->prepare('SELECT COUNT(*) FROM class_members WHERE class_id=?');$m->execute([$r['class_id']]);
return [ico($q?'quiz':'exam'),'<a href="'.($q?'Quiz/results.php?id=':'Exam/exam_results.php?id=').(int)$r['id'].'">'.e($r['title']).'</a>',e($cn[$r['class_id']]??''),teacher_state($r),(int)$s->fetchColumn().' of '.(int)$m->fetchColumn()];},array_slice($open,0,10)));}
// TO GRADE: code exam answers that wait for this teacher
$tgr=qrows($pdo,"SELECT a.id aid,x.title,u.full_name,a.submitted_at FROM exam_attempts a JOIN coding_exams x ON x.id=a.exam_id JOIN users u ON u.id=a.student_id WHERE x.teacher_id=? AND x.class_id IN ($in) AND a.submitted_at IS NOT NULL AND a.score IS NULL ORDER BY a.submitted_at LIMIT 8",[$u['id']]);
echo '<h2>To grade</h2><div class="card todo2">';
if($tgr){foreach($tgr as $t)echo '<div class="trow"><span class="tk">'.ico('exam').'</span><div><small>'.e($t['full_name']).' &middot; '.e(when($t['submitted_at'])).'</small><b>'.e($t['title']).'</b></div><a class="btn" href="Exam/view.php?aid='.(int)$t['aid'].'">Grade</a></div>';}
else echo '<p class="empty">Nothing to grade right now. Quizzes are checked by the system; code exam answers show here when students submit them.</p>';
echo '</div>';
echo '<h2>Latest submissions</h2>';
$rows=qrows($pdo,"(SELECT u.full_name,q.title,'Quiz' kind,a.submitted_at done_at FROM attempts a JOIN quizzes q ON q.id=a.quiz_id JOIN users u ON u.id=a.student_id WHERE q.teacher_id=? AND q.class_id IN ($in) AND a.submitted_at IS NOT NULL) UNION ALL (SELECT u.full_name,x.title,'Code exam',a.submitted_at FROM exam_attempts a JOIN coding_exams x ON x.id=a.exam_id JOIN users u ON u.id=a.student_id WHERE x.teacher_id=? AND x.class_id IN ($in) AND a.submitted_at IS NOT NULL) ORDER BY done_at DESC LIMIT 10",[$u['id'],$u['id']]);
if($rows)table(['Student','Work','Type','Submitted'],array_map(fn($r)=>[e($r['full_name']),e($r['title']),e($r['kind']),e(when($r['done_at']))],$rows));else echo '<p><small>Nothing has been submitted yet.</small></p>';
echo '<p><small>School year: <b>'.e(school_year()?:'not set yet').'</b> &nbsp; <a href="Grades/promote.php">'.(school_year()?'Start a new school year':'Set the school year').'</a></small></p>';
echo '<p><small>AI Assistant for students: '.(!AI_ON?'<b>AI is OFF</b>: students use the built-in tutor. To switch it on, add an AI key in config.php (see README).':'<b class="ok">AI Assistant is ON</b> (up to '.AI_DAILY_LIMIT.' questions per student per day).').'</small></p>';
}
foot();
