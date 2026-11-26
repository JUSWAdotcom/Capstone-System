<?php require __DIR__.'/../config.php';$u=need('teacher');$id=(int)($_GET['id']??0);
$q=$pdo->prepare('SELECT * FROM quizzes WHERE id=? AND teacher_id=?');$q->execute([$id,$u['id']]);$z=$q->fetch();if(!$z)die('Quiz not found');
// the teacher changes the class, the start time or the deadline (or ends it now)
if($_SERVER['REQUEST_METHOD']==='POST'){check();
if(isset($_POST['end'])){$pdo->prepare('UPDATE quizzes SET due_at=? WHERE id=?')->execute([date('Y-m-d H:i:s'),$id]);flash('The quiz ended now. Students who did not submit are Missed.');}
else{$err='';[$o,$d]=window_post($err);$cls=teacher_class((int)($_POST['class_id']??0),$u['id']);
if(!$cls)flash('Choose a class.',true);elseif($err)flash($err,true);
else{$pdo->prepare('UPDATE quizzes SET class_id=?,opens_at=?,due_at=? WHERE id=?')->execute([$cls['id'],$o,$d,$id]);
if((int)$cls['id']!==(int)$z['class_id'])$n=notify_class($cls['id'],'quiz','New quiz in '.class_name($cls).': '.$z['title'].' (due '.when($d).')','Quiz/quiz.php?id='.$id);
elseif($d!==$z['due_at'])notify_class($cls['id'],'quiz','Deadline changed: '.$z['title'].' is now due '.when($d),'Quiz/quiz.php?id='.$id);
flash('Saved. '.class_name($cls).', opens '.when($o).', deadline '.when($d).'.');}}
header('Location: '.BASE.'Quiz/results.php?id='.$id);exit;}
head('Results: '.$z['title']);flash();
echo '<div class="card" style="max-width:none"><div class="chead"><div>'.teacher_state($z).'</div><form method="post" class="inl" style="margin:0" onsubmit="return confirm(\'End this quiz now? Students who did not submit become Missed.\')"><input type="hidden" name="t" value="'.csrf().'"><input type="hidden" name="end" value="1"><button class="alt">End now</button></form></div>
<details'.($z['class_id']?'':' open').'><summary>Change the class, start time or deadline</summary><form method="post"><input type="hidden" name="t" value="'.csrf().'">'.class_select($u,$z['class_id']).window_fields($z['opens_at'],$z['due_at']).'<button class="alt">Save</button> <small>A new class is notified. A new deadline is told to the class.</small></form></details></div>';
$s=$pdo->prepare('SELECT * FROM questions WHERE quiz_id=? ORDER BY id');$s->execute([$id]);$qs=$s->fetchAll();
$s=$pdo->prepare('SELECT qa.attempt_id,qa.question_id,qa.choice FROM quiz_answers qa JOIN attempts a ON a.id=qa.attempt_id WHERE a.quiz_id=?');$s->execute([$id]);$m=[];foreach($s as $r)$m[$r['attempt_id']][$r['question_id']]=$r['choice'];
$s=$pdo->prepare('SELECT a.id aid,u.full_name,u.student_id sid,COALESCE(a.year_level,u.year_level) yl,a.score,a.total,a.submitted_at,(SELECT COUNT(*) FROM cheat_events c WHERE c.attempt_id=a.id AND c.event_type IN '.FLAG_SQL.') fl,(SELECT GROUP_CONCAT(DISTINCT c.event_type) FROM cheat_events c WHERE c.attempt_id=a.id AND c.event_type IN '.FLAG_SQL.') ty FROM attempts a JOIN users u ON u.id=a.student_id WHERE a.quiz_id=? ORDER BY a.submitted_at DESC');$s->execute([$id]);$rows=$s->fetchAll();
$done=array_values(array_filter($rows,fn($r)=>$r['submitted_at']));$avg=$done?array_sum(array_map(fn($r)=>$r['score']/max($r['total'],1)*100,$done))/count($done):null;
echo '<div class="stats"><div class="card"><h3>Submitted</h3><div class="big">'.count($done).'</div></div><div class="card"><h3>Average score</h3><div class="big">'.pct($avg).'</div><small>calculated automatically</small></div><div class="card"><h3>Questions</h3><div class="big">'.count($qs).'</div></div></div>';
if($done&&$qs){echo '<h2>Question by question</h2>';$ids=array_column($done,'aid');
table(['#','Question','Answer key','Students correct'],array_map(function($r,$i)use($m,$ids,$done){$c=0;foreach($ids as $a)if(($m[$a][$r['id']]??'')===strtoupper($r['correct']))$c++;return [$i+1,e(mb_substr($r['question'],0,90)),e($r['correct']),$c.' of '.count($done).' ('.round($c/max(count($done),1)*100).'%)'];},$qs,array_keys($qs)));}
// every student of the class, also the ones who did not start (Missed after the deadline), plus anybody else who started it
$ms=[];if($z['class_id']){$mq=$pdo->prepare("SELECT u.id,u.full_name,u.student_id sid,u.year_level yl FROM class_members c JOIN users u ON u.id=c.student_id WHERE c.class_id=? ORDER BY u.full_name");$mq->execute([$z['class_id']]);$ms=$mq->fetchAll();}
$have=array_flip(array_map(fn($r)=>$r['sid'].'|'.$r['full_name'],$rows));
foreach($ms as $mm)if(!isset($have[$mm['sid'].'|'.$mm['full_name']]))$rows[]=['aid'=>null,'full_name'=>$mm['full_name'],'sid'=>$mm['sid'],'yl'=>$mm['yl'],'score'=>null,'total'=>null,'submitted_at'=>null,'fl'=>0,'ty'=>''];
echo '<h2>Students</h2>';
table(['Student','Status','Score','Percent','Left the page','What happened','Answers'],array_map(function($r)use($qs,$m,$z){$d=$r['submitted_at'];$det='-';
if($d){$det='<details><summary>View answers</summary>';foreach($qs as $i=>$x){$c=$m[$r['aid']][$x['id']]??'';$ok=$c!==''&&$c===strtoupper($x['correct']);$det.='<div>'.($ok?'<span class="ok">✔</span>':'<span class="err">✘</span>').' '.($i+1).'. chose <b>'.e($c?:'none').'</b>, key <b>'.e($x['correct']).'</b></div>';}$det.='</details>';}
[$k,$t]=work_state($z+['aid'=>$r['aid'],'submitted_at'=>$d]);
return [e($r['full_name']).'<br><small>'.e($r['sid']).'</small>','<span class="st st-'.$k.'">'.($k==='open'?'Not started':$t).'</span>'.($d?'<br><small>'.e(when($d)).'</small>':''),$d?$r['score'].'/'.$r['total']:'-',$d?pct($r['score']/max($r['total'],1)*100):'-',$r['fl'],e(flag_words($r['ty'])),$det];},$rows));
foot();
