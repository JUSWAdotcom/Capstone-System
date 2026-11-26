<?php require __DIR__.'/../config.php';$u=need('teacher');$err='';$old=[];$cid=(int)($_POST['class_id']??$_GET['class']??0);$opens=date('Y-m-d H:i:00');$due=date('Y-m-d 23:59:00',strtotime('+7 days'));
if($_SERVER['REQUEST_METHOD']==='POST'){check();
$title=mb_substr(trim((string)($_POST['title']??'')),0,150);$cls=teacher_class($cid,$u['id']);[$o2,$d2]=window_post($werr);if($o2){$opens=$o2;$due=$d2;}else{$opens=dt_in($_POST['opens_at']??'')?:$opens;$due=dt_in($_POST['due_at']??'')?:$due;}$in=is_array($_POST['qs']??null)?array_values($_POST['qs']):[];$qs=[];$bad=[];$n=0;
foreach($in as $r){if(!is_array($r))continue;$row=['q'=>trim((string)($r['q']??'')),'k'=>strtoupper(trim((string)($r['k']??'')))];
foreach(['a','b','c','d'] as $l)$row[$l]=mb_substr(trim((string)($r[$l]??'')),0,255);$old[]=$row;
if($row['q']===''&&$row['a'].$row['b'].$row['c'].$row['d']==='')continue;$n++;
if($row['q']===''||in_array('',[$row['a'],$row['b'],$row['c'],$row['d']],true)||!preg_match('/^[A-D]$/',$row['k'])){$bad[]=$n;continue;}
$qs[]=$row;}
if($title==='')$err='Give the quiz a title.';
elseif(!$cls)$err='Choose the class that takes this quiz.';
elseif(!empty($werr))$err=$werr;
elseif($bad)$err='Question '.implode(', ',$bad).' needs the question, all four answers (A to D) and an answer key.';
elseif(!$qs)$err='Add at least one question.';
elseif(count($qs)>200)$err='A quiz can have up to 200 questions.';
else{$pdo->beginTransaction();
$pdo->prepare('INSERT INTO quizzes(teacher_id,title,show_answers,school_year,class_id,opens_at,due_at) VALUES(?,?,?,?,?,?,?)')->execute([$u['id'],$title,isset($_POST['show'])?1:0,school_year(),$cid,$opens,$due]);$id=$pdo->lastInsertId();
$i=$pdo->prepare('INSERT INTO questions(quiz_id,question,opt_a,opt_b,opt_c,opt_d,correct) VALUES(?,?,?,?,?,?,?)');
foreach($qs as $p)$i->execute([$id,$p['q'],$p['a'],$p['b'],$p['c'],$p['d'],$p['k']]);
$pdo->commit();$sent=notify_class($cid,'quiz','New quiz in '.class_name($cls).': '.$title.' (due '.when($due).')','Quiz/quiz.php?id='.$id);
flash('Quiz saved with '.count($qs).' question'.(count($qs)==1?'':'s').' for '.class_name($cls).'. '.$sent.' student'.($sent==1?' was':'s were').' notified. It opens '.when($opens).' and the deadline is '.when($due).'. Answers are checked and scored automatically.');header('Location: '.BASE.'Classes/class.php?id='.$cid);exit;}}
head('Create quiz');if($err)echo '<p class="err">'.e($err).'</p>';?>
<form method="post" id="qform" class="card" style="max-width:none"><input type="hidden" name="t" value="<?=csrf()?>">
<?=class_select($u,$cid)?>
<label>Quiz title<input name="title" required maxlength="150" value="<?=e($_POST['title']??'')?>"></label>
<?=window_fields($opens,$due)?>
<details class="paste" open><summary><b>Copy and paste your questions</b> (optional shortcut)</summary>
<p><small>Paste questions from Word, a PDF or a website, then press <b>Add these questions</b>. Every question has answers A to D. Write the correct answer on its own line (<code>Answer: B</code>) or put a star after it (<code>B. Four *</code>) and the answer key is filled in for you. You can change the answer key beside each question below at any time.</small></p>
<textarea id="paste" rows="9" placeholder="1. What does HTML stand for?&#10;A. Hyper Text Markup Language&#10;B. High Tech Modern Language&#10;C. Home Tool Markup Language&#10;D. Hyperlinks and Text Marking Language&#10;Answer: A&#10;&#10;2. Which tag makes a link?&#10;A. &lt;img&gt;&#10;B. &lt;a&gt;&#10;C. &lt;p&gt;&#10;D. &lt;br&gt;&#10;Answer: B"></textarea>
<div class="inl"><button type="button" id="addpaste" class="alt">Add these questions</button><button type="button" id="addq" class="alt">+ Add one blank question</button></div>
<p><small id="pmsg"></small></p>
<label>Answer key list (optional). If the key is at the end of your document, paste it like <code>1-B, 2-A, 3-D</code><textarea id="keys" rows="2"></textarea></label>
<button type="button" id="applykeys" class="alt">Apply answer key</button>
</details>
<h2>Questions and answer key</h2>
<div id="qlist"></div><p><small id="count"></small></p>
<label class="opt"><input type="checkbox" name="show" value="1" checked> Show the correct answers to students after they submit</label>
<p><button>Save quiz and notify the class</button></p></form>
<script>const OLD=<?=json_encode($old)?>;</script><script src="assets/quizbuilder.js?v=48.1"></script><?php foot();
