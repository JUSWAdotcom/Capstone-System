<?php require __DIR__.'/../config.php';$u=need('student');$id=(int)($_GET['id']??$_POST['id']??0);
// MY SUBMITTED CODE EXAM: the student sees the code that was submitted, runs it again, reads the teacher's score and feedback.
// After the deadline (so classmates who are still taking the exam cannot get answers) the student can also
// ask the AI for feedback, tips and tricks, and copy the work into the Playground to keep improving it.
$s=$pdo->prepare('SELECT * FROM coding_exams WHERE id=?');$s->execute([$id]);$x=$s->fetch();
$s=$pdo->prepare('SELECT * FROM exam_attempts WHERE exam_id=? AND student_id=? AND submitted_at IS NOT NULL');$s->execute([$id,$u['id']]);$a=$s->fetch();
if(!$x||!$a){header('Location: '.BASE.'Exam/exams.php');exit;}
$ended=!$x['due_at']||time()>=strtotime($x['due_at']);$files=exam_files($x['language'],$a['code']);
if($_SERVER['REQUEST_METHOD']==='POST'){check();
if(!$ended||!$files){flash('You can open your work in the Playground after the deadline.',true);header('Location: '.BASE.'Exam/review.php?id='.$id);exit;}
// copy the files into the Playground as new files: "fizzbuzz-main.py", "it-club-index.html"... (links between the files are updated)
$slug=substr(trim(preg_replace('/[^a-z0-9]+/','-',strtolower($x['title'])),'-')?:'exam',0,24);$slug=rtrim($slug,'-');
$have=$pdo->prepare('SELECT name FROM saved_files WHERE user_id=?');$have->execute([$u['id']]);$have=$have->fetchAll(PDO::FETCH_COLUMN);
if(count($have)+count($files)>60){flash('Your Playground is full (60 files). Delete some files there first.',true);header('Location: '.BASE.'Exam/review.php?id='.$id);exit;}
$map=[];foreach($files as $n=>$c){if(!preg_match('/\.(py|js|html|css|php|cpp|cs|java)$/',$n))continue;$b=$slug.'-'.pathinfo($n,PATHINFO_FILENAME);$e=pathinfo($n,PATHINFO_EXTENSION);$nn=$b.'.'.$e;for($i=2;in_array($nn,$have,true)||in_array($nn,$map,true);$i++)$nn=$b.'-'.$i.'.'.$e;$map[$n]=$nn;}
$ins=$pdo->prepare('INSERT INTO saved_files(user_id,name,language,code) VALUES(?,?,?,?)');$L=['py'=>'python','js'=>'javascript','html'=>'html','css'=>'css','php'=>'php','cpp'=>'cpp','cs'=>'csharp','java'=>'java'];
foreach($map as $old=>$new){$c=(string)$files[$old];if(count($map)>1)foreach($map as $o2=>$n2)$c=str_replace(['"'.$o2.'"',"'".$o2."'"],['"'.$n2.'"',"'".$n2."'"],$c);try{$ins->execute([$u['id'],$new,$L[pathinfo($new,PATHINFO_EXTENSION)]??'text',$c]);}catch(PDOException $e){}}
$first=current(array_filter($map,fn($n)=>preg_match('/index\.(html|php)$/',$n)))?:current($map);
flash('Your exam work was copied into the Playground as '.implode(', ',$map).'. Changes there do not change your submitted exam.');
header('Location: '.BASE.'Playground/playground.php?open='.rawurlencode((string)$first));exit;}
$lock=ai_exam_open($u['id']);$on=AI_ON;
head('My work: '.$x['title']);flash();
$web=in_array($x['language'],['html','web'],true);
echo '<div class="card chead" style="max-width:none"><div><span class="st st-done">Finished</span> <small class="when">Submitted '.e(when($a['submitted_at'])).'</small><h2 style="margin:.4rem 0 .2rem">'.($a['score']===null?'Waiting for your teacher to grade':'Score: '.(int)$a['score'].' / '.(int)$x['max_points'].' <small>('.round($a['score']/max($x['max_points'],1)*100).'%)</small>').'</h2>'
.($web?'':'<small>Your output '.($a['passed']?'<b class="ok">matched</b>':'<b>did not match</b>').' the expected output.</small>').'</div>'
.($ended?'<form method="post" style="margin:0"><input type="hidden" name="t" value="'.csrf().'"><input type="hidden" name="id" value="'.$id.'"><button>Open in Playground</button></form>':'<small>After the deadline ('.e(when($x['due_at'])).') you can open your work in the Playground and ask the AI for tips.</small>').'</div>';
echo '<div class="card" style="max-width:none"><b>Teacher feedback</b><p style="margin:.3rem 0 0">'.($a['feedback']!==null&&trim($a['feedback'])!==''?nl2br(e($a['feedback'])):'<small>No feedback yet. You get a notification when your teacher grades it.</small>').'</p></div>';
$prob='<p>'.nl2br(e($x['prompt'])).'</p>'.($x['expected_output']!==''?'<p><b>Expected output</b></p><pre style="margin:0;padding:.5rem;background:#111;border-radius:6px;white-space:pre-wrap;font-size:.8rem">'.e($x['expected_output']).'</pre>':'').picture_html($x);
echo ide_html('My submitted work: '.$x['title'],$prob,'<button type="button" class="ide-run" data-ide-run>'.ico('play').'Run</button>',false,'PROBLEM');
echo '<script src="assets/ide.js?v=48.1"></script><script>IDE.mount('.json_encode(['lang'=>$x['language'],'files'=>$files?:new stdClass(),'readOnly'=>true,'T'=>csrf(),'autorun'=>$web]+($web?[]:['output'=>"Your output when you submitted:\n\n".((string)$a['output']!==''?$a['output']:'(nothing)')."\n\nPress Run to run it again."])).');</script>';
echo '<h2>AI feedback, tips and tricks</h2>';
if(!$ended)echo '<p class="card">The AI can look at your work after the deadline ('.e(when($x['due_at'])).'), when every classmate has finished.</p>';
elseif($lock)echo '<p class="card">🔒 AI help is off while another code exam of yours is open. Submit it first.</p>';
else{echo '<p><small>'.($on?'The AI Assistant reads your submitted code and gives feedback.':'The built-in tutor answers here.').' Press a button below or ask your own question.</small></p>'.chat_box('rv',['Give me feedback on my submitted code','Tips and tricks to make my code better','Explain what I did wrong and how to fix it'],true);
$code='';foreach($files as $n=>$c)$code.=(count($files)>1?"// ---- $n ----\n":'').$c."\n";
echo '<script>const T='.json_encode(csrf()).';</script><script src="assets/tutor.js?v=48.1"></script><script src="assets/assistant.js?v=48.1"></script><script>mkChat("rv",{on:'.($on?'true':'false').',name:'.json_encode(AI_SHOWN).',left:'.(int)ai_left($u).',ctx:()=>({lang:'.json_encode(['web'=>'html','java'=>'java'][$x['language']]??$x['language']).',code:'.json_encode("This is my code exam \"".$x['title']."\" that I already submitted (the deadline has passed). The task was: ".mb_substr($x['prompt'],0,600)."\n\n".$code).',output:IDE.output(),include:!!(document.getElementById("rvcode")&&document.getElementById("rvcode").checked)})});</script>';}
foot();
