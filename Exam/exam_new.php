<?php require __DIR__.'/../config.php';$u=need('teacher');$err='';
// CREATE A CODE EXAM for one class, with a start time and a deadline.
// Language "Web project" = several files (HTML, CSS, JavaScript) and, if you want, a PHP back end (forms that send data to a .php file).
$L=['python'=>'Python','javascript'=>'JavaScript','cpp'=>'C++','csharp'=>'C#','java'=>'Java','php'=>'PHP','html'=>'HTML / CSS (one file)','web'=>'Web project: HTML + CSS + JavaScript files (+ PHP back end)'];
$cid=(int)($_POST['class_id']??$_GET['class']??0);$opens=date('Y-m-d H:i:00');$due=date('Y-m-d 23:59:00',strtotime('+7 days'));
if($_SERVER['REQUEST_METHOD']==='POST'){check();$l=isset($L[$_POST['language']??''])?$_POST['language']:'python';$m=max(1,min(600,(int)$_POST['minutes']));$pts=max(1,min(1000,(int)$_POST['points']));
$cls=teacher_class($cid,$u['id']);$werr='';[$o2,$d2]=window_post($werr);if($o2){$opens=$o2;$due=$d2;}
$noexp=in_array($l,['html','web'],true);$pic=[null,''];if($cls&&!$werr&&trim($_POST['title']??'')!==''&&trim($_POST['prompt']??'')!==''&&($noexp||trim($_POST['exp']??'')!==''))$pic=save_exam_picture($_FILES['pic']??null); // only when the rest of the form is fine
if(!$cls)$err='Choose the class that takes this exam.';
elseif($pic[0]===null&&$pic[1]!=='')$err=$pic[1];
elseif($werr)$err=$werr;
elseif(!trim($_POST['title'])||!trim($_POST['prompt'])||(!$noexp&&!trim($_POST['exp'])))$err='Fill in the title, the problem and the expected output (HTML and web project exams do not need one).';
else{$starter=null;
if($l==='web'){$f=['index.html'=>"<!DOCTYPE html>\n<html>\n<head>\n  <meta charset=\"utf-8\">\n  <title>My page</title>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <h1>Hello!</h1>\n".(isset($_POST['backend'])?"\n  <form action=\"process.php\" method=\"post\">\n    <label>Your name <input name=\"name\"></label>\n    <button>Send</button>\n  </form>\n":'')."\n  <script src=\"script.js\"></script>\n</body>\n</html>\n",'style.css'=>"body {\n  font-family: Arial, sans-serif;\n  margin: 2rem;\n}\n",'script.js'=>"// JavaScript for the page\n"];
if(isset($_POST['backend']))$f['process.php']="<?php\n// The form in index.html sends its data here (method=\"post\")\n\$name = htmlspecialchars(\$_POST['name'] ?? '');\n?>\n<!DOCTYPE html>\n<html>\n<head>\n  <meta charset=\"utf-8\">\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <h1>Hello, <?= \$name ?>!</h1>\n  <a href=\"index.html\">Back</a>\n</body>\n</html>\n";
$starter=json_encode(['files'=>$f]);}
$pdo->prepare('INSERT INTO coding_exams(teacher_id,title,language,prompt,expected_output,minutes,max_points,school_year,class_id,opens_at,due_at,starter,attachment,attachment_check) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$u['id'],mb_substr(trim($_POST['title']),0,150),$l,trim($_POST['prompt']),$noexp?'':mb_substr(trim($_POST['exp']),0,255),$m,$pts,school_year(),$cid,$opens,$due,$starter,$pic[0],$pic[0]?$pic[1]:null]);
$t=mb_substr(trim($_POST['title']),0,150);$sent=notify_class($cid,'exam','New code exam in '.class_name($cls).': '.$t.' ('.$m.' min, '.lname($l).', due '.when($due).')','Exam/exams.php');
flash('Code exam saved for '.class_name($cls).'. '.$sent.' student'.($sent==1?' was':'s were').' notified. It opens '.when($opens).' and the deadline is '.when($due).'.');header('Location: '.BASE.'Classes/class.php?id='.$cid);exit;}}
head('Create code exam');if($err)echo '<p class="err">'.e($err).'</p>';?>
<form method="post" class="card" style="max-width:none" enctype="multipart/form-data"><input type="hidden" name="t" value="<?=csrf()?>">
<?=class_select($u,$cid)?>
<label>Exam title<input name="title" required maxlength="150" value="<?=e($_POST['title']??'')?>"></label>
<?=window_fields($opens,$due)?>
<div class="two"><label>Language<select name="language" id="lang"><?php foreach($L as $k=>$v)echo '<option value="'.$k.'"'.(($_POST['language']??'')===$k?' selected':'').'>'.$v.'</option>';?></select></label>
<label>Time limit in minutes (counted from when the student opens it)<input type="number" name="minutes" value="<?=(int)($_POST['minutes']??60)?>" min="1" max="600" required></label></div>
<label class="opt" id="be" hidden><input type="checkbox" name="backend" value="1" checked> Add a PHP back end (process.php): a form in index.html sends its data to process.php</label>
<label>Total points<input type="number" name="points" value="<?=(int)($_POST['points']??100)?>" min="1" max="1000" required></label>
<label>Problem statement (students read this beside the code)<textarea name="prompt" rows="5" required><?=e($_POST['prompt']??'')?></textarea></label>
<label id="expl">Expected output (exactly what a correct program prints). Not needed for HTML and web project exams: you grade those by looking at the page and the code.<textarea name="exp" rows="3"><?=e($_POST['exp']??'')?></textarea></label>
<label>Picture of the expected output (optional: a screenshot of the program output or of the finished web page)<input type="file" name="pic" accept="image/png,image/jpeg,image/gif,image/webp"></label>
<p><small>Only pictures (PNG, JPG, GIF, WEBP, up to 5 MB). Every picture is checked and saved again as a clean picture; <?=AI_ON?'the AI also blocks pictures that are not appropriate for school.':'with an AI key in config.php the AI also blocks pictures that are not appropriate for school.'?> Other files are blocked.</small></p>
<p><small>Students write their code in an editor like Visual Studio Code: files on the left, Run, Output and Preview. Java needs the Java JDK on the computer that runs the system (see README).</small></p>
<button>Create exam and notify the class</button></form>
<script>(function(){var l=document.getElementById('lang'),b=document.getElementById('be'),x=document.getElementById('expl');var f=function(){b.hidden=l.value!=='web';x.hidden=l.value==='web'||l.value==='html'};l.onchange=f;f()})()</script>
<?php foot();
