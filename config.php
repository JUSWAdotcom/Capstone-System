<?php
// ================= SETTINGS YOU MAY CHANGE =================
const DB_HOST='localhost';      // database server
const DB_NAME='capstone_db';    // database name
const DB_USER='root';           // database user (XAMPP: root)
const DB_PASS='';               // database password (XAMPP: empty). On a real server use a strong password!
const PUBLIC_SITE=false;        // set true when the site is on the internet: hides error details, forces https and turns off running code on the server
const TEACHER_CODE='Wcc-teacher1'; // teachers must enter this to sign up. CHANGE IT before you publish the site!
const ADMIN_ID='admin';          // the ONE admin account: log in with this ID (in the Student ID box) and ADMIN_START_PASSWORD
const ADMIN_START_PASSWORD='Admin-Start-2026'; // the admin's first password. The site makes you change it at the first admin login. CHANGE IT before you publish the site!
const TIMEZONE='Asia/Manila'; // the time zone of the school: start times and deadlines use it
date_default_timezone_set(TIMEZONE);
const JAVA='java'; // Java (JDK 11 or newer). Leave 'java': the system finds the Java that Launcher\\Install languages.bat put in C:\\xampp\\langs. Or write the full path, for example 'C:\\Program Files\\Java\\jdk-21\\bin\\java.exe'
const ADMIN_EMAIL='codesmartaix@gmail.com'; // the admin email: the admin's step-2 login code is sent here (Admin > Security can change it)
// ---- EMAIL: new accounts must type a 6-digit code that is emailed to them before they can log in ----
// Leave MAIL_HOST empty = email OFF here: set up the Gmail in Admin > Email instead (saved in the database). Codes are never shown on the page (version 45), except SHOW_CODES_ON_SCREEN for testing.
// Gmail: MAIL_HOST 'smtp.gmail.com', MAIL_PORT 587, MAIL_SECURE 'tls', MAIL_USER your Gmail address, MAIL_PASS a Google "App password"
// (Google Account > Security > turn on 2-Step Verification > App passwords). Do not use your normal Gmail password.
const MAIL_HOST='';              // the mail server, for example 'smtp.gmail.com'. Empty = test mode
const MAIL_PORT=587;             // 587 with MAIL_SECURE 'tls', or 465 with 'ssl'
const MAIL_SECURE='tls';         // 'tls' (port 587), 'ssl' (port 465), or '' (no encryption: only for a mail server on your own computer)
const MAIL_USER='';              // the email account that sends the mail, for example 'yourschool@gmail.com'
const MAIL_PASS='';              // the App password of that account (spaces are ignored)
const MAIL_FROM='';              // the sender shown in the email. Empty = same as MAIL_USER
const MAIL_FROM_NAME='CodeSmartAi-X'; // the sender name shown in the email
const SHOW_CODES_ON_SCREEN=false; // version 45: sign-up and forgot-password codes are ONLY emailed. Set true only to test on your own computer before email is set up.
const FORCE_HTTPS=true;         // on a public site, http:// is sent to https://. Set false ONLY if the page keeps reloading in a loop (then ask your host to turn on https)
const ADMIN_TWO_STEP=true;        // true = after the admin password, a 6-digit code is sent to the admin email (a leaked password alone is not enough)
const ADMIN_IDLE_MINUTES=30;      // the admin is logged out after this many minutes without using the site
const ADMIN_ALLOWED_IPS='';       // optional: the admin can only log in from these computers / networks, for example '192.168.1.10,192.168.1.11'. Empty = anywhere
const APPROVE_NEW_ACCOUNTS=true; // true = a new account cannot log in until it is approved: a teacher or the admin approves students, the admin approves teachers (Approvals page)
const ALLOWED_EMAIL_DOMAINS='';  // optional: only emails of these domains can sign up, for example 'worldciti.edu.ph' (comma between several). Empty = any email
const STUDENT_CODE='';           // optional class code. If you fill it in, students must type it to sign up (keeps strangers out). Empty = anyone can sign up.
const INSTALL_KEY='';           // only for web hosting: lets you open Setup/install.php remotely with Setup/install.php?key=THIS_TEXT. Leave empty on your own computer.
const AI_KEY=''; // OPTION 1 (paid): paste your Anthropic API key here (console.anthropic.com) to use Claude as the AI Assistant
const GEMINI_KEY=''; // OPTION 2: paste your Google Gemini API key here (aistudio.google.com, free tier or paid with billing on). If this is filled in, Gemini is used.
const OPENAI_KEY=''; // OPTION 3 (paid, cheap): paste your OpenAI API key here (platform.openai.com > API keys). If filled in, GPT is used first and Gemini is the backup.
const OPENAI_MODEL='gpt-6-luna'; // main GPT model
const OPENAI_BACKUP_MODELS='gpt-5-nano'; // tried when the main GPT model is busy or not available
const GEMINI_MODEL='gemini-3.5-flash-lite'; // main Gemini model (the one Google recommends for new keys)
const GEMINI_BACKUP_MODELS='gemini-flash-latest,gemini-flash-lite-latest,gemini-3.5-flash'; // tried in order when the main model is busy
const AI_MODEL='claude-haiku-5-5'; // fast and cheap. For smarter answers use 'claude-sonnet-5-5'
const AI_DAILY_LIMIT=30; // AI questions each student can ask per day (keeps your API bill small)
const AI_URL='https://api.anthropic.com/v1/messages';
define('AI_OPENAI',OPENAI_KEY!==''); define('AI_GEMINI',GEMINI_KEY!==''); define('AI_ON',AI_OPENAI||AI_GEMINI||AI_KEY!==''); define('AI_NAME',AI_OPENAI?'GPT':(AI_GEMINI?'Gemini':'Claude')); define('AI_SHOWN','AI Assistant'); // AI_NAME stays internal; pages only ever show AI_SHOWN define('AI_MODEL_NOW',AI_OPENAI?OPENAI_MODEL:(AI_GEMINI?GEMINI_MODEL:AI_MODEL));
const ALLOW_RUN_ON_PUBLIC=false; // leave false. When PUBLIC_SITE is true, students cannot run PHP, C++ or C# on your server (they could run anything on it).
const ALLOW_NATIVE_RUN=true; // C++ and C# run compiled programs on THIS computer. Keep true only on your own localhost or a trusted classroom PC.
const PHP_CLI='C:\\xampp\\php\\php.exe'; // on Linux or Mac use 'php'
const GPP='g++'; const DOTNET='dotnet'; const DOTNET_TFM='auto'; // leave 'g++', 'dotnet' and 'auto': the system finds C++ and C# in C:\\xampp\\langs (Launcher\\Install languages.bat) by itself
// ============================================================
// A public site must not use the default settings. If it does, it refuses to open.
if(PUBLIC_SITE){
    $bad=[];
    if(TEACHER_CODE==='Wcc-teacher1')$bad[]='change TEACHER_CODE (anyone who knows the default code can sign up as a teacher)';
    if(ADMIN_START_PASSWORD==='Admin-Start-2026')$bad[]='change ADMIN_START_PASSWORD (the default one is written in the README)';
    if(MAIL_HOST==='')$bad[]='fill in the MAIL_ settings (on a public site the verification code must be emailed. Without them the code is shown on the page and anybody could skip the check)';
    if(DB_PASS==='')$bad[]='give the database user a password and put it in DB_PASS';
    if(DB_USER==='root')$bad[]='do not use the root database user (make a new user that can only use this database)';
    if($bad){
        http_response_code(500);
        die('<p style="font:16px/1.6 system-ui;max-width:720px;margin:2rem auto"><b>Not safe to go public yet.</b> Open config.php and: '.implode('; ',$bad).'.</p>');
    }
}
// is the visitor using https?
$https=false;
if(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')$https=true;
if(($_SERVER['SERVER_PORT']??'')==='443')$https=true;
if(strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']??'')==='https')$https=true;
if(strtolower($_SERVER['HTTP_X_FORWARDED_SSL']??'')==='on')$https=true;
if(strpos($_SERVER['HTTP_CF_VISITOR']??'','"https"')!==false)$https=true;
if(PUBLIC_SITE){
    ini_set('display_errors','0'); // do not show error details to visitors
    // send http:// visitors to https://
    if(FORCE_HTTPS&&!$https&&PHP_SAPI!=='cli'&&preg_match('/^[A-Za-z0-9.:-]+$/',$_SERVER['HTTP_HOST']??'')){
        header('Location: https://'.$_SERVER['HTTP_HOST'].($_SERVER['REQUEST_URI']??'/'),true,301);
        exit;
    }
}
// safer session cookie, then start the session
session_set_cookie_params(['lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Lax','secure'=>$https]);
ini_set('session.use_strict_mode','1');
ini_set('session.gc_maxlifetime','28800');
session_start();
// small safety headers
if(PUBLIC_SITE&&$https)header('Strict-Transport-Security: max-age=31536000');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
// ---------- where the site starts in the browser ----------
// The pages are in folders (Logging, Quiz, Exam...), so links are built from the site folder.
// BASE is that folder as seen by the browser, for example /capstone-system/
$site_dir=str_replace('\\','/',__DIR__);
$page_file=str_replace('\\','/',(string)realpath($_SERVER['SCRIPT_FILENAME']??''));
if($page_file!==''&&strncasecmp($page_file,$site_dir.'/',strlen($site_dir)+1)===0){
    $depth=substr_count(substr($page_file,strlen($site_dir)+1),'/'); // 0 = index.php, 1 = a page inside a folder
}else{
    $depth=basename($_SERVER['SCRIPT_NAME']??'')==='index.php'?0:1;
}
$site_url=str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/'));
for($i=0;$i<$depth;$i++)$site_url=dirname($site_url);
define('BASE',rtrim($site_url,'/').'/');
set_exception_handler(function($e){if(!headers_sent())http_response_code(500);echo '<div style="font:16px/1.6 system-ui;max-width:720px;margin:2rem auto;padding:1rem 1.2rem;background:#171b2e;color:#e6e9f5;border:1px solid #fb7185;border-radius:10px"><h2 style="margin-top:0">Something went wrong</h2><p>'.(PUBLIC_SITE?'Please try again in a moment. If it keeps happening, tell your teacher.':htmlspecialchars($e->getMessage()).'</p><p>If this mentions a missing column or table, open <b>Setup/install.php</b> once to update the database, then reload this page.').'</p></div>';error_log('CodeSmartAi-X: '.$e->getMessage());});
$pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
try{$pdo->exec("SET time_zone='".date('P')."'");}catch(Throwable $x){} // NOW() in MySQL = the school's time
require_once __DIR__.'/database/schema.php';
// version 48: the database check runs ONCE for the whole system (when SCHEMA_V changes), not once for every new visitor.
// Before, 40 students who opened the site at the same moment each ran it and waited about 5 seconds for the first page.
if(($_SESSION['schema_v']??0)!==SCHEMA_V){try{$sv=(string)$pdo->query("SELECT v FROM settings WHERE k='schema_v'")->fetchColumn();}catch(Throwable $e){$sv='';}
if($sv===(string)SCHEMA_V)$_SESSION['schema_v']=SCHEMA_V;else{try{ensure_schema($pdo);try{$pdo->prepare("DELETE FROM settings WHERE k='schema_v'")->execute();$pdo->prepare("INSERT INTO settings(k,v) VALUES('schema_v',?)")->execute([(string)SCHEMA_V]);}catch(Throwable $e){}$_SESSION['schema_v']=SCHEMA_V;}catch(Throwable $e){}}}
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function pending_count($u){global $pdo;if(!$u||($u['role']!=='teacher'&&$u['role']!=='admin'))return 0;try{return (int)$pdo->query('SELECT COUNT(*) FROM users WHERE approved=0 AND email_verified=1 AND '.($u['role']==='admin'?"role IN ('student','teacher')":"role='student'"))->fetchColumn();}catch(Throwable $x){return 0;}}
function email_domain_ok($email){if(trim(ALLOWED_EMAIL_DOMAINS)==='')return true;$d=strtolower(substr(strrchr($email,'@')?:'',1));foreach(explode(',',strtolower(ALLOWED_EMAIL_DOMAINS)) as $ok){$ok=trim($ok);if($ok!==''&&$d===$ok)return true;}return false;}
function csrf(){return $_SESSION['t']??=bin2hex(random_bytes(16));}
function check(){if(!hash_equals($_SESSION['t']??'',$_POST['t']??''))die('Invalid request');}
// announcements: every student, or only the students of the year levels in $years ('1234' = all). Graduated students (5) are not told.
function notify_students($kind,$msg,$link,$years='1234'){global $pdo;$p=[$kind,mb_substr($msg,0,255),$link];$w=' AND (year_level IS NULL OR year_level<5)';
$d=preg_replace('/[^1-4]/','',(string)$years);if($d!==''&&$d!=='1234'){$w=' AND year_level IS NOT NULL AND INSTR(?,year_level)>0';$p[]=$d;}
$s=$pdo->prepare("INSERT INTO notifications(user_id,kind,message,link) SELECT id,?,?,? FROM users WHERE role='student'".$w);$s->execute($p);return $s->rowCount();}
function year_name($y){return [1=>'1st year',2=>'2nd year',3=>'3rd year',4=>'4th year',5=>'Graduated'][(int)$y]??'Not set';}
// "1st year: 4, 2nd year: 2, ..." for the teacher pages
function year_counts(){global $pdo;$n=[];foreach($pdo->query("SELECT year_level y,COUNT(*) c FROM users WHERE role='student' GROUP BY year_level") as $r)$n[(int)$r['y']]=(int)$r['c'];
$a=[];foreach([1,2,3,4,5,0] as $y)if(!empty($n[$y])||($y>=1&&$y<=4))$a[]=year_name($y).': <b>'.($n[$y]??0).'</b>';return implode(' &nbsp; ',$a);}
// ---- CLASSES (version 41) ----
// A class is one subject of one teacher, for example "IT 102 Computer Programming 2 - BSIT 1-A". Students join it with its join code.
// Every quiz and code exam belongs to one class, so only the students of that class get it. An IRREGULAR student simply joins
// the classes of all the subjects he or she takes (1st year and 2nd year subjects alike). The year level is only information now.
function class_name($c){return $c['name'].((string)($c['section']??'')!==''?' - '.$c['section']:'');}
function new_join_code(){global $pdo;$a='ABCDEFGHJKLMNPQRSTUVWXYZ23456789';for($t=0;$t<30;$t++){$c='';for($i=0;$i<6;$i++)$c.=$a[random_int(0,strlen($a)-1)];$s=$pdo->prepare('SELECT 1 FROM classes WHERE join_code=?');$s->execute([$c]);if(!$s->fetchColumn())return $c;}return strtoupper(bin2hex(random_bytes(4)));}
// the classes of a teacher (own) or of a student (joined). $all=true also gives the archived ones
function my_classes($u,$all=false){global $pdo;try{if($u['role']==='teacher')$s=$pdo->prepare('SELECT c.*,(SELECT COUNT(*) FROM class_members m WHERE m.class_id=c.id) members FROM classes c WHERE c.teacher_id=?'.($all?'':' AND c.archived=0').' ORDER BY c.archived,c.name,c.section');
else $s=$pdo->prepare('SELECT c.*,t.full_name teacher,(SELECT COUNT(*) FROM class_members m WHERE m.class_id=c.id) members FROM classes c JOIN class_members j ON j.class_id=c.id AND j.student_id=? JOIN users t ON t.id=c.teacher_id'.($all?'':' WHERE c.archived=0').' ORDER BY c.archived,c.name,c.section');
$s->execute([$u['id']]);return $s->fetchAll();}catch(Throwable $x){return [];}}
function in_class($cid,$uid){global $pdo;$s=$pdo->prepare('SELECT 1 FROM class_members WHERE class_id=? AND student_id=?');$s->execute([(int)$cid,(int)$uid]);return (bool)$s->fetchColumn();}
function teacher_class($cid,$tid){global $pdo;$s=$pdo->prepare('SELECT * FROM classes WHERE id=? AND teacher_id=?');$s->execute([(int)$cid,(int)$tid]);return $s->fetch();} // the teacher's own class, or false
function notify_class($cid,$kind,$msg,$link){global $pdo;$s=$pdo->prepare('INSERT INTO notifications(user_id,kind,message,link) SELECT student_id,?,?,? FROM class_members WHERE class_id=?');$s->execute([$kind,mb_substr($msg,0,255),$link,(int)$cid]);return $s->rowCount();}
// the class drop-down of the quiz / exam forms
function class_select($u,$sel){$cs=my_classes($u);if(!$cs)return '<p class="card err">Make a class first on the <a href="Classes/classes.php">Classes</a> page. Every quiz and code exam belongs to a class.</p>';
$h='<label>Class (only the students of this class get it)<select name="class_id" required><option value="">Choose a class</option>';foreach($cs as $c)$h.='<option value="'.(int)$c['id'].'"'.((int)$sel===(int)$c['id']?' selected':'').'>'.e(class_name($c)).' ('.(int)$c['members'].' student'.($c['members']==1?'':'s').')</option>';return $h.'</select></label>';}
// ---- start time and deadline (version 41) ----
const GRACE=120; // seconds: work started before the deadline may still be submitted this long after it (slow internet)
function dt_in($s){$s=trim((string)$s);if($s==='')return null;$t=strtotime($s);return $t?date('Y-m-d H:i:00',$t):null;} // from <input type="datetime-local">
function dt_val($s){return $s?date('Y-m-d\TH:i',strtotime($s)):'';}
function when($s){return $s?date('D, M j, g:i A',strtotime($s)):'-';}
function window_fields($open,$due){return '<div class="two"><label>Opens (students can start from this time)<input type="datetime-local" name="opens_at" required value="'.e(dt_val($open)).'"></label><label>Deadline (not submitted by then = Missed)<input type="datetime-local" name="due_at" required value="'.e(dt_val($due)).'"></label></div>';}
function window_post(&$err){$o=dt_in($_POST['opens_at']??'');$d=dt_in($_POST['due_at']??'');if(!$o||!$d){$err='Set when it opens and the deadline.';return [null,null];}if(strtotime($d)<=strtotime($o)){$err='The deadline must be after the time it opens.';return [null,null];}return [$o,$d];}
// where a quiz / exam stands for a student. $r has opens_at, due_at, closed and, when the student has one, aid + submitted_at.
// [key, text]: done = Finished (green), missed = Missed (red), soon = not open yet, open = can start, going = started, not submitted yet
function work_state($r){$now=time();if(!empty($r['submitted_at']))return ['done','Finished'];if((int)($r['closed']??0)===2)return ['missed',empty($r['aid'])?'Closed':'Missed'];
$o=!empty($r['opens_at'])?strtotime($r['opens_at']):0;$d=!empty($r['due_at'])?strtotime($r['due_at']):0;
if($o&&$now<$o)return ['soon','Not open yet'];if($d&&$now>=$d)return ['missed','Missed'];return !empty($r['aid'])?['going','In progress']:['open','Open'];}
function can_start($r){return in_array(work_state($r)[0],['open','going'],true);}
// the coloured status and the live countdown (assets/live.js keeps the countdown moving and reloads the page when it opens or ends)
function state_html($r){[$k,$t]=work_state($r);$h='<span class="st st-'.$k.'">'.$t.'</span>';
if($k==='soon')$h.=' <small class="when">Opens '.e(when($r['opens_at'])).' &middot; <span class="cd" data-t="'.strtotime($r['opens_at']).'" data-pre="in "></span></small>';
elseif(($k==='open'||$k==='going')&&!empty($r['due_at']))$h.=' <small class="when">Due '.e(when($r['due_at'])).' &middot; <span class="cd" data-t="'.strtotime($r['due_at']).'" data-post=" left"></span></small>';
elseif($k==='done'&&!empty($r['submitted_at']))$h.=' <small class="when">'.e(when($r['submitted_at'])).'</small>';
elseif(!empty($r['due_at']))$h.=' <small class="when">Deadline was '.e(when($r['due_at'])).'</small>';
return $h;}
// every quiz and code exam of one class ($cid), or of all the (not archived) classes of student $sid ($cid=0), with that student's attempt
function work_rows($cid,$sid=0){global $pdo;$sid=(int)$sid;
$w=$cid?'%.class_id='.(int)$cid:'%.class_id IN (SELECT m.class_id FROM class_members m JOIN classes c ON c.id=m.class_id AND c.archived=0 WHERE m.student_id='.$sid.')';
$r=$pdo->query("SELECT 'quiz' kind,q.id,q.title,q.class_id,q.opens_at,q.due_at,q.closed,(SELECT COUNT(*) FROM questions WHERE quiz_id=q.id) n,'' language,a.id aid,a.submitted_at,a.score,a.total FROM quizzes q LEFT JOIN attempts a ON a.quiz_id=q.id AND a.student_id=$sid WHERE ".str_replace('%','q',$w))->fetchAll();
$x=$pdo->query("SELECT 'exam' kind,x.id,x.title,x.class_id,x.opens_at,x.due_at,x.closed,x.minutes n,x.language,a.id aid,a.submitted_at,a.score,x.max_points total FROM coding_exams x LEFT JOIN exam_attempts a ON a.exam_id=x.id AND a.student_id=$sid WHERE ".str_replace('%','x',$w))->fetchAll();
$all=array_merge($r,$x);$rank=['going'=>0,'open'=>1,'soon'=>2,'missed'=>3,'done'=>3];
usort($all,function($a,$b)use($rank){$ka=$rank[work_state($a)[0]];$kb=$rank[work_state($b)[0]];if($ka!==$kb)return $ka<=>$kb;$da=strtotime($a['due_at']??'2100-01-01');$db=strtotime($b['due_at']??'2100-01-01');return $ka<3?$da<=>$db:$db<=>$da;});return $all;}
// the list of quizzes / code exams of a student, with the coloured status, the countdown and the right button
function work_list($rows,$showClass=false){global $pdo;if(!$rows)return '';$cn=[];
if($showClass){$ids=array_unique(array_map(fn($r)=>(int)$r['class_id'],$rows));if($ids)foreach($pdo->query('SELECT id,name,section FROM classes WHERE id IN ('.implode(',',$ids).')') as $c)$cn[$c['id']]=class_name($c);}
$h='<div class="card todo2 wl">';foreach($rows as $r){[$k]=work_state($r);$q=$r['kind']==='quiz';$url=$q?'Quiz/quiz.php?id='.(int)$r['id']:'Exam/exam.php?id='.(int)$r['id'];
$sub=$q?(int)$r['n'].' question'.($r['n']==1?'':'s'):(int)$r['n'].' min, '.lname($r['language']);
if($k==='done')$btn=$q?'<a class="btn alt" href="Quiz/quiz_result.php?id='.(int)$r['id'].'">'.(int)$r['score'].'/'.(int)$r['total'].' &middot; Review</a>':'<a class="btn alt" href="Exam/review.php?id='.(int)$r['id'].'">'.($r['score']===null?'Not graded yet':(int)$r['score'].'/'.(int)$r['total']).' &middot; View</a>';
elseif($k==='open')$btn='<a class="btn" href="'.$url.'">'.($q?'Take quiz':'Open exam').'</a>';
elseif($k==='going')$btn='<a class="btn" href="'.$url.'">Resume</a>';
elseif($k==='soon')$btn='<span class="btn off" aria-disabled="true">'.($q?'Take quiz':'Open exam').'</span>';else $btn='';
$h.='<div class="trow"><span class="tk">'.ico($q?'quiz':'exam').'</span><div><small>'.($q?'Quiz':'Code exam').' &middot; '.e($sub).(isset($cn[$r['class_id']])?' &middot; '.e($cn[$r['class_id']]):'').'</small><b>'.e($r['title']).'</b>'.state_html($r).'</div>'.$btn.'</div>';}
return $h.'</div>';}
// for the teacher: has this quiz / exam opened, is it open, or has the deadline passed?
function teacher_state($r){$now=time();if((int)($r['closed']??0)===2)return '<span class="st st-missed">Closed</span>';if(empty($r['class_id']))return '<span class="st st-missed">No class yet</span> <small class="when">Open it and choose a class</small>';
if(!empty($r['opens_at'])&&$now<strtotime($r['opens_at']))return '<span class="st st-soon">Not open yet</span> <small class="when">Opens '.e(when($r['opens_at'])).' &middot; <span class="cd" data-t="'.strtotime($r['opens_at']).'" data-pre="in "></span></small>';
if(!empty($r['due_at'])&&$now>=strtotime($r['due_at']))return '<span class="st st-soon">Ended</span> <small class="when">Deadline was '.e(when($r['due_at'])).'</small>';
return '<span class="st st-open">Open</span>'.(!empty($r['due_at'])?' <small class="when">Due '.e(when($r['due_at'])).' &middot; <span class="cd" data-t="'.strtotime($r['due_at']).'" data-post=" left"></span></small>':'');}
// ---- the code editor in the style of VS Code (assets/ide.js) ----
// $tools = buttons on the right of the title bar. $problem = the task text (HTML, already escaped)
function ide_html($title,$problem,$tools,$add=false,$side='PROBLEM'){return '<div class="ide" id="ide"><div class="ide-top">'.ico('code').'<b class="ide-title">'.e($title).'</b><span class="ide-tools">'.$tools.'</span></div>
<div class="ide-body"><aside class="ide-side"><div class="ide-sec"><div class="ide-h">'.e($side).'</div><div class="ide-prob">'.$problem.'</div></div>
<div class="ide-sec ide-ex"><div class="ide-h">EXPLORER<button type="button" class="ide-ic" id="ide-add" title="New file" aria-label="New file"'.($add?'':' hidden').'>'.ico('plus').'</button></div><ul class="ide-files" id="ide-files"></ul></div></aside>
<section class="ide-main"><div class="ide-tabs" id="ide-tabs"></div><div class="ide-ed"><pre class="ide-ln" id="ide-ln" aria-hidden="true">1</pre><div class="ide-wrap"><pre class="ide-hl" id="ide-hl" aria-hidden="true"></pre><textarea id="code" class="ide-code" spellcheck="false" wrap="off" autocapitalize="off" autocomplete="off" aria-label="Code editor"></textarea></div></div></section>
<section class="ide-panel"><div class="ide-ptabs"><button type="button" data-p="out" class="on">OUTPUT</button><button type="button" data-p="web">PREVIEW</button><span></span><button type="button" class="ide-ic" id="ide-full" title="Output / preview in full screen" aria-label="Output or preview in full screen">'.ico('full').'</button></div><pre class="ide-out" id="ide-out"></pre><iframe class="ide-web" id="ide-web" title="Preview" sandbox="allow-scripts allow-forms allow-modals" hidden></iframe></section></div>
<div class="ide-status"><span id="ide-lang"></span><span id="ide-pos">Ln 1, Col 1</span><span id="ide-note"></span></div></div>';}
// the files of a submitted / starter exam: one file, or a web project {"files":{...}}
function exam_files($lang,$code){$main=['python'=>'main.py','javascript'=>'main.js','cpp'=>'main.cpp','csharp'=>'Program.cs','php'=>'index.php','java'=>'Main.java','html'=>'index.html'][$lang]??'main.txt';
if($lang==='web'){$j=json_decode((string)$code,true);return is_array($j['files']??null)?$j['files']:[];}return (string)$code===''?[]:[$main=>(string)$code];}
// the teacher grades one submitted code exam; the student is notified
function exam_grade($x,$aid,$score,$fb){global $pdo;$sc=max(0,min((int)$x['max_points'],(int)$score));$up=$pdo->prepare('UPDATE exam_attempts SET score=?,feedback=?,graded_at=NOW() WHERE id=? AND exam_id=? AND submitted_at IS NOT NULL');$up->execute([$sc,mb_substr(trim((string)$fb),0,500),(int)$aid,$x['id']]);
if($up->rowCount()){$g=$pdo->prepare('SELECT student_id FROM exam_attempts WHERE id=? AND exam_id=?');$g->execute([(int)$aid,$x['id']]);if($sid=$g->fetchColumn())notify_user($sid,'grade','Your code exam "'.$x['title'].'" was graded: '.$sc.'/'.(int)$x['max_points'],'Grades/grades.php');return true;}return false;}
// ---- admin protection (version 43) ----
function admin_log($event){global $pdo;try{$pdo->prepare('INSERT INTO admin_log(event,ip,agent) VALUES(?,?,?)')->execute([$event,(string)($_SERVER['REMOTE_ADDR']??''),mb_substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,160)]);}catch(Throwable $x){}}
// Integrity flags (version 45): only leaving the tab or window and leaving full screen are recorded. Blocked actions (copy, paste...) are not.
const FLAG_SQL="('tab_switch','fullscreen_exit')";
function flag_words($ty){$w=['tab_switch'=>'left the tab','fullscreen_exit'=>'left full screen'];$o=[];foreach(explode(',',(string)$ty) as $t)if(isset($w[$t]))$o[]=$w[$t];return implode(', ',$o);}
function admin_email_real($e){return mail_ready()&&filter_var($e,FILTER_VALIDATE_EMAIL)&&!preg_match('/\.(local|test|invalid|localhost)$/i',(string)$e);} // can the step-2 code really be emailed?
function admin_ip_ok(){$l=trim(ADMIN_ALLOWED_IPS);if($l==='')return true;$ip=(string)($_SERVER['REMOTE_ADDR']??'');foreach(explode(',',$l) as $a){$a=trim($a);if($a!==''&&($ip===$a||(substr($a,-1)==='.'&&strpos($ip,$a)===0)))return true;}return false;}
// ---- the picture of the expected output on a code exam (version 43) ----
// Only real pictures (PNG, JPG, GIF, WEBP, up to 5 MB). The picture is opened and saved again as a new PNG/JPG file, which removes
// hidden data and anything that is not a picture. If an AI key is set, the AI also looks at it and BLOCKS pictures that are not
// right for school (nudity, sexual content, violence, drugs...). The admin can see and remove every picture (Admin > Quizzes & exams).
const UPLOAD_DIR=__DIR__.'/uploads'; // not reachable from the browser (.htaccess); pictures are shown through Exam/attachment.php
function ai_image_check($bytes,$mime){ // true = looks fine, false = blocked, null = could not check
$q='You check pictures that a teacher uploads to a school coding website for students aged 16 to 25. The picture should show code, a program output, a web page or a diagram. Answer with ONE word: SAFE if it is appropriate for school, or UNSAFE if it shows nudity, sexual content, pornography, violence or gore, drugs, weapons, hate symbols, or anything else not appropriate for students.';
$ans=null;
if(AI_GEMINI){foreach(array_values(array_unique(array_filter(array_map('trim',array_merge([GEMINI_MODEL],explode(',',GEMINI_BACKUP_MODELS)))))) as $model){
$ch=curl_init('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent');$o=[CURLOPT_POST=>1,CURLOPT_RETURNTRANSFER=>1,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>40,CURLOPT_POSTFIELDS=>json_encode(['contents'=>[['role'=>'user','parts'=>[['inline_data'=>['mime_type'=>$mime,'data'=>base64_encode($bytes)]],['text'=>$q]]]],'generationConfig'=>['maxOutputTokens'=>256]]),CURLOPT_HTTPHEADER=>['content-type: application/json','x-goog-api-key: '.GEMINI_KEY]];ai_cainfo($o);curl_setopt_array($ch,$o);
$r=json_decode((string)curl_exec($ch),true);$fr=(string)($r['candidates'][0]['finishReason']??'');$pf=(string)($r['promptFeedback']['blockReason']??'');
if($pf!==''||in_array($fr,['SAFETY','PROHIBITED_CONTENT','BLOCKLIST','IMAGE_SAFETY'],true)){$ans=false;break;} // Google itself refused to look at it
$t='';foreach(($r['candidates'][0]['content']['parts']??[]) as $p)if(isset($p['text'])&&empty($p['thought']))$t.=$p['text'];
if(preg_match('/\bUNSAFE\b/i',$t)){$ans=false;break;}if(preg_match('/\bSAFE\b/i',$t)){$ans=true;break;}}}
if($ans===null&&AI_OPENAI){$ch=curl_init('https://api.openai.com/v1/chat/completions');$o=[CURLOPT_POST=>1,CURLOPT_RETURNTRANSFER=>1,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>40,CURLOPT_POSTFIELDS=>json_encode(['model'=>OPENAI_MODEL,'max_completion_tokens'=>200,'messages'=>[['role'=>'user','content'=>[['type'=>'text','text'=>$q],['type'=>'image_url','image_url'=>['url'=>'data:'.$mime.';base64,'.base64_encode($bytes)]]]]]]),CURLOPT_HTTPHEADER=>['content-type: application/json','authorization: Bearer '.OPENAI_KEY]];ai_cainfo($o);curl_setopt_array($ch,$o);
$r=json_decode((string)curl_exec($ch),true);$t=(string)($r['choices'][0]['message']['content']??'');if(preg_match('/\bUNSAFE\b/i',$t))$ans=false;elseif(preg_match('/\bSAFE\b/i',$t))$ans=true;}
return $ans;}
// checks and saves an uploaded picture. Gives [file name, check result 'ai_ok' / 'not_checked'] or [null, error text]
function save_exam_picture($f){if(!is_array($f)||($f['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return [null,''];
if($f['error']!==UPLOAD_ERR_OK)return [null,'The picture could not be uploaded (it may be bigger than the server allows).'];
if($f['size']>5*1024*1024)return [null,'The picture is too big. Use one under 5 MB.'];
$b=(string)@file_get_contents($f['tmp_name']);$info=@getimagesizefromstring($b);
if(!$info||!in_array($info[2],[IMAGETYPE_PNG,IMAGETYPE_JPEG,IMAGETYPE_GIF,IMAGETYPE_WEBP],true))return [null,'Only pictures can be uploaded: PNG, JPG, GIF or WEBP. Other files (documents, programs, videos) are blocked.'];
if($info[0]>8000||$info[1]>8000)return [null,'The picture is too large (more than 8000 pixels).'];
$mime=$info['mime'];$ext=$info[2]===IMAGETYPE_JPEG?'jpg':'png';
if(function_exists('imagecreatefromstring')){$im=@imagecreatefromstring($b);if(!$im)return [null,'That file is not a real picture.'];
$w=imagesx($im);$h=imagesy($im);$k=min(1,1800/max($w,$h));if($k<1){$n=imagecreatetruecolor((int)($w*$k),(int)($h*$k));imagealphablending($n,false);imagesavealpha($n,true);imagecopyresampled($n,$im,0,0,0,0,(int)($w*$k),(int)($h*$k),$w,$h);imagedestroy($im);$im=$n;}
ob_start();if($ext==='jpg')imagejpeg($im,null,88);else{imagesavealpha($im,true);imagepng($im,null,6);}$b=ob_get_clean();imagedestroy($im);$mime=$ext==='jpg'?'image/jpeg':'image/png';}
elseif($info[2]===IMAGETYPE_GIF||$info[2]===IMAGETYPE_WEBP){$ext=$info[2]===IMAGETYPE_GIF?'gif':'webp';}
$ok=AI_ON?ai_image_check($b,$mime):null;
if($ok===false)return [null,'This picture was BLOCKED: it does not look appropriate for a school website. Upload a picture of the code, the output or the web page.'];
if(!is_dir(UPLOAD_DIR))@mkdir(UPLOAD_DIR,0755,true);$name=bin2hex(random_bytes(12)).'.'.$ext;
if(!@file_put_contents(UPLOAD_DIR.'/'.$name,$b))return [null,'The picture could not be saved on the server (check that the uploads folder can be written).'];
return [$name,$ok===true?'ai_ok':'not_checked'];}
function drop_exam_picture($name){if($name&&preg_match('/^[a-f0-9]{24}\.(png|jpg|gif|webp)$/',$name))@unlink(UPLOAD_DIR.'/'.$name);}
function picture_html($x){return !empty($x['attachment'])?'<p><b>Expected output (picture)</b></p><a href="Exam/attachment.php?id='.(int)$x['id'].'" target="_blank" rel="noopener"><img class="xpic" src="Exam/attachment.php?id='.(int)$x['id'].'" alt="Picture of the expected output"></a>':'';}
// the page a student sees instead of the quiz / exam when it cannot be opened (not in the class, not open yet, missed)
function blocked_page($title,$why,$back){http_response_code(403);head($title);echo '<div class="card"><p>'.$why.'</p><a class="btn" href="'.$back.'">Back</a></div>';foot();exit;}
// ---- school years: "2026-2027" ----
function valid_sy($s){return preg_match('/^(\d{4})-(\d{4})$/',(string)$s,$m)&&(int)$m[2]===(int)$m[1]+1;}
function school_year(){global $pdo;try{$v=$pdo->query("SELECT v FROM settings WHERE k='school_year'")->fetchColumn();}catch(Throwable $x){$v=false;}return $v?:null;}
function notify_user($uid,$kind,$msg,$link){global $pdo;$pdo->prepare('INSERT INTO notifications(user_id,kind,message,link) VALUES(?,?,?,?)')->execute([$uid,$kind,mb_substr($msg,0,255),$link]);}
function flash($m=null,$bad=false){if($m!==null){$_SESSION['flash']=$m;$_SESSION['flash_bad']=$bad;return;}if(!empty($_SESSION['flash'])){echo '<p class="'.(empty($_SESSION['flash_bad'])?'card ok':'err').'">'.e($_SESSION['flash']).'</p>';unset($_SESSION['flash'],$_SESSION['flash_bad']);}} // version 48: $bad=true shows the message in red (an error)
function quiet(){return in_array(basename($_SERVER['SCRIPT_NAME']),['quiz.php','exam.php']);}
function bell($u){global $pdo;if(!$u||$u['role']!=='student'||quiet())return '';$n=0;try{$s=$pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL');$s->execute([$u['id']]);$n=(int)$s->fetchColumn();}catch(Throwable $x){}
return '<a class="bell" href="Dashboard/notifications.php" title="Notifications" aria-label="Notifications">'.ico('bell').($n?'<span class="dot">'.($n>9?'9+':$n).'</span>':'').'</a>';}
function toast($u){global $pdo;if(!$u||$u['role']!=='student'||quiet()||basename($_SERVER['SCRIPT_NAME'])==='notifications.php')return '';
try{$s=$pdo->prepare('SELECT id,kind,message FROM notifications WHERE user_id=? AND read_at IS NULL AND shown=0 ORDER BY id DESC LIMIT 3');$s->execute([$u['id']]);$r=$s->fetchAll();if(!$r)return '';
$pdo->prepare('UPDATE notifications SET shown=1 WHERE user_id=? AND shown=0')->execute([$u['id']]);}catch(Throwable $x){return '';}
$ic=['quiz'=>'📝','exam'=>'🧪','grade'=>'📊','year'=>'🎓','announce'=>'📢'];$h='<div class="toasts" role="status">';foreach($r as $n)$h.='<a class="toast" href="Dashboard/notifications.php?go='.(int)$n['id'].'"><b>'.($ic[$n['kind']]??'🔔').' '.e($n['message']).'</b><small>Tap to open</small></a>';
return $h.'<button type="button" class="alt toastx" onclick="this.parentNode.remove()">Dismiss</button></div><script>setTimeout(function(){var t=document.querySelector(".toasts");if(t)t.remove()},15000)</script>';}
function pct($v){return $v===null?'-':round($v).'%';}
function pwv($hash){return substr(hash('sha256',$hash),0,16);}
// need('student'), need('teacher'), need('admin'): only that role.  need('any'): a student or a teacher.
// need('staff'): a teacher or the admin.  need('all'): anybody who is logged in (change password).
// A page for another role sends the visitor away: the admin to the admin area, everybody else to the main page.
function need($role){global $pdo;$u=$_SESSION['user']??null;$ok=false;
if($u&&$u['role']==='admin'){if(isset($_SESSION['seen'])&&time()-$_SESSION['seen']>ADMIN_IDLE_MINUTES*60){admin_log('idle_logout');$_SESSION=[];header('Location: '.BASE.'Logging/login.php?open=1&idle=1');exit;}$_SESSION['seen']=time();} // the admin is logged out after ADMIN_IDLE_MINUTES without activity
if($u){$r0=$u['role'];if($role==='any')$ok=$r0!=='admin';elseif($role==='all')$ok=true;elseif($role==='staff')$ok=$r0==='teacher'||$r0==='admin';else $ok=$r0===$role;}
if(!$ok){header('Location: '.BASE.($u&&$u['role']==='admin'?'Admin/admin.php':'Logging/login.php'));exit;}
$s=$pdo->prepare('SELECT password_hash,year_level,email_verified,approved FROM users WHERE id=?');$s->execute([$u['id']]);$r=$s->fetch(); // password changed, account deleted, email not verified or not approved: log this session out
if(!$r||(int)($r['email_verified']??1)===0||(int)($r['approved']??1)===0||(isset($u['pwv'])&&pwv($r['password_hash'])!==$u['pwv'])){$_SESSION=[];header('Location: '.BASE.'Logging/login.php');exit;}
if($u['role']==='admin'&&$role!=='all'&&password_verify(ADMIN_START_PASSWORD,$r['password_hash'])){flash('Please choose your own admin password first. The first password is written in config.php and in the README, so everybody who has the files knows it.');header('Location: '.BASE.'Logging/password.php');exit;}
$u['year_level']=$r['year_level']===null?null:(int)$r['year_level'];$_SESSION['user']['year_level']=$u['year_level']; // the teacher can change a student's year, so read it again on every page

return $u;}
function head($t){$u=$_SESSION['user']??null;$GLOBALS['app_wrap']=(bool)$u;echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><base href="'.e(BASE).'"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($t).' | CodeSmartAi-X</title><script>try{var d=document.documentElement;d.dataset.theme=localStorage.theme||"dark"}catch(e){}</script><link rel="stylesheet" href="assets/style.css?v=48.1">'.($u?'':'<link rel="stylesheet" href="assets/landing.css?v=48.1">').'</head><body class="'.($u?'app':'entry').'">'.($u?'':'<canvas id="rain" aria-hidden="true"></canvas>').($u?'<header class="top"><button type="button" class="menubtn" id="menubtn" aria-label="Menu" aria-controls="side" aria-expanded="false">'.ico('menu').'</button><a class="brand" href="index.php"><img src="assets/img/logo.svg" alt=""><b>CodeSmart<span>Ai</span>-X</b></a><div class="topr">'.bell($u).settings().'</div></header>'.sidebar($u).'<main><div class="wrap">':'<header><a class="brand" href="index.php"><img src="assets/img/logo.svg" alt=""><b>CodeSmart<span>Ai</span>-X</b></a><nav><button type="button" id="themebtn" class="themebtn" aria-label="Light or dark mode" title="Light or dark mode">☀️</button>'.navlinks($u).'</nav></header><main>').toast($u).($u?'':'<div class="authlogo"><img src="assets/img/logo.svg" alt=""><b>CodeSmart<span>Ai</span>-X</b><small>Learn. Practice. Code.</small></div>').'<h1>'.e($t).'</h1>';}
function foot(){echo (!empty($GLOBALS['app_wrap'])?'</div>':'').'</main><script src="assets/settings.js?v=48.1"></script>'.(isset($_SESSION['user'])?'<script>window.NOW_MS='.(time()*1000).'</script><script src="assets/live.js?v=48.1"></script>':'').(isset($_SESSION['user'])?'':'<script src="assets/landing.js?v=48.1"></script>').'</body></html>';}
function table($h,$rows){echo '<div class="scroll"><table><tr><th>'.implode('</th><th>',$h).'</th></tr>';foreach($rows as $r)echo '<tr><td>'.implode('</td><td>',$r).'</td></tr>';echo '</table></div>';}
// ---- version 47: Java, C++ and C# are found by themselves ----
// Launcher\Install languages.bat puts them in C:\xampp\langs (jdk-..., mingw64, dotnet). A full path in JAVA, GPP or DOTNET above wins.
function langs_dir(){static $d=null;if($d!==null)return $d;$d='';
foreach([dirname(PHP_CLI,2),dirname((string)($_SERVER['DOCUMENT_ROOT']??'')),'C:/xampp'] as $b)if($b!==''&&$b!=='.'&&@is_dir($b.'/langs')){$d=$b.'/langs';break;}return $d;}
function tool($k){static $c=[];if(isset($c[$k]))return $c[$k];$d=langs_dir();$f='';
if($k==='java'){if(JAVA!=='java')$f=JAVA;elseif($d){$g=glob($d.'/jdk*/bin/java.exe')?:[];rsort($g);$f=$g[0]??'';}if($f==='')$f='java';}
elseif($k==='gpp'){if(GPP!=='g++')$f=GPP;elseif($d&&is_file($d.'/mingw64/bin/g++.exe'))$f=$d.'/mingw64/bin/g++.exe';
 if($f==='')foreach(['C:/msys64/ucrt64/bin/g++.exe','C:/msys64/mingw64/bin/g++.exe','C:/MinGW/bin/g++.exe'] as $x)if(@is_file($x)){$f=$x;break;}if($f==='')$f='g++';}
elseif($k==='dotnet'){if(DOTNET!=='dotnet')$f=DOTNET;elseif($d&&is_file($d.'/dotnet/dotnet.exe'))$f=$d.'/dotnet/dotnet.exe';elseif(@is_file('C:/Program Files/dotnet/dotnet.exe'))$f='C:/Program Files/dotnet/dotnet.exe';else $f='dotnet';}
$f=$f===basename($f)?$f:str_replace('/',DIRECTORY_SEPARATOR,$f);return $c[$k]=$f;}
// the .NET version to build C# with: the newest SDK next to dotnet.exe (net10.0, net8.0...)
function dotnet_tfm(){if(DOTNET_TFM!==''&&DOTNET_TFM!=='auto')return DOTNET_TFM;$b=dirname(tool('dotnet'));$m=0;
foreach(@is_dir($b.'/sdk')?(glob($b.'/sdk/*',GLOB_ONLYDIR)?:[]):[] as $x)if(preg_match('/^(\d+)\.\d+\.\d+$/',basename($x),$r))$m=max($m,(int)$r[1]);return 'net'.($m?:8).'.0';}
function run_ok($l){if(PUBLIC_SITE&&!ALLOW_RUN_ON_PUBLIC)return false;return $l==='php'||$l==='web'||ALLOW_NATIVE_RUN;}
function lname($l){return ['python'=>'Python','javascript'=>'JavaScript','html'=>'HTML / CSS','cpp'=>'C++','csharp'=>'C#','php'=>'PHP','java'=>'Java','web'=>'Web project'][$l]??$l;}
function settings(){if(!isset($_SESSION['user']))return '';$u=$_SESSION['user'];
$role=['admin'=>'Admin','teacher'=>'Teacher','student'=>'Student'][$u['role']]??'';
$h='<button type="button" id="gear" class="me" aria-haspopup="true" aria-controls="settings" title="Settings"><span class="av sm">'.e(mb_strtoupper(mb_substr($u['name'],0,1))).'</span><span class="mename"><b>'.e($u['name']).'</b><small>'.$role.'</small></span>'.ico('down').'</button><div id="settings" class="pop" hidden><b>Settings</b><label>Mode<select id="s-theme"><option value="dark">Dark</option><option value="light">Light</option></select></label>';
if(!quiet())$h.='<hr><small>Signed in as</small><b>'.e($u['name']).'</b><a class="btnlink" href="Logging/password.php">🔑 Change password</a><form method="post" action="Logging/logout.php"><input type="hidden" name="t" value="'.csrf().'"><button class="alt">Log out</button></form>';
return $h.'</div>';}
function pw($label,$min,$name='password',$auto='current-password'){return '<label>'.$label.'<span class="pw"><input type="password" name="'.$name.'" minlength="'.$min.'" autocomplete="'.$auto.'" required><button type="button" class="eye" aria-label="Hold to show the password" title="Hold to show the password">'.ico('eye').'</button></span></label>';}
// shows ***** and an eye button. The real text appears only when the eye is pressed (settings.js)
function secret($text){return '<span class="secret"><span class="sv" data-v="'.e($text).'">*****</span> <button type="button" class="peek" aria-label="Hold to show" title="Hold to show">'.ico('eye').'</button></span>';} // ***** until the eye is HELD (mouse, finger or Space key)
function infopanel($me,$role='student'){$t=$role==='teacher'?'Teacher':'Student';return '<div class="card info"><div class="who"><div class="av">'.e(mb_strtoupper(mb_substr($me['full_name'],0,1))).'</div><h2>'.$t.' Info</h2></div><div class="rows"><div><small>Full name</small><b>'.e($me['full_name']).'</b></div><div><small>'.$t.' ID</small><b>'.($me['student_id']?secret($me['student_id']):'Not set yet').'</b></div><div><small>Email</small><b>'.secret($me['email']).'</b></div><div><small>Joined</small><b>'.e(substr($me['created_at'],0,10)).'</b></div>'.($role==='teacher'?'':'<div><small>Year level</small><b>'.e(year_name($me['year_level']??null)).'</b></div>').'</div>'.($me['student_id']?'':'<form method="post" class="inl"><input type="hidden" name="t" value="'.csrf().'"><input name="sid" placeholder="Set your '.$t.' ID" pattern="[A-Za-z0-9._\-]{3,30}" required><button>Save ID</button></form>').($role==='teacher'||!empty($me['year_level'])?'':'<form method="post" class="inl"><input type="hidden" name="t" value="'.csrf().'"><select name="year" required><option value="">Choose your year level</option><option value="1">1st year</option><option value="2">2nd year</option><option value="3">3rd year</option><option value="4">4th year</option></select><button>Save year</button></form><small>You can set your year level once; only your teacher or the admin can change it later. Your quizzes and code exams come from the classes you join (Classes page).</small>').'</div>';}
function ai_exam_open($uid){global $pdo;$s=$pdo->prepare('SELECT 1 FROM exam_attempts WHERE student_id=? AND submitted_at IS NULL AND started_at>NOW()-INTERVAL 4 HOUR');$s->execute([$uid]);return (bool)$s->fetch();}
function ai_left($u){global $pdo;try{$s=$pdo->prepare('SELECT n FROM ai_usage WHERE user_id=? AND used_on=?');$s->execute([$u['id'],date('Y-m-d')]);return max(0,AI_DAILY_LIMIT-(int)$s->fetchColumn());}catch(Throwable $x){return AI_DAILY_LIMIT;}}
function ai_guard($u){global $pdo;if(!AI_ON)return 'AI_OFF';
if(ai_exam_open($u['id']))return 'AI is turned off while an exam is in progress.';
if(time()-($_SESSION['ai']??0)<3)return 'Please wait a few seconds between questions.';
if(ai_left($u)<=0)return 'You have used all '.AI_DAILY_LIMIT.' AI questions for today. Try again tomorrow.';
$_SESSION['ai']=time();try{$pdo->prepare('INSERT INTO ai_usage(user_id,used_on,n) VALUES(?,?,1) ON DUPLICATE KEY UPDATE n=n+1')->execute([$u['id'],date('Y-m-d')]);}catch(Throwable $x){}return null;}
function ai_cainfo(&$o){foreach(['C:/xampp/apache/bin/curl-ca-bundle.crt','C:/xampp/php/extras/ssl/cacert.pem','/etc/ssl/certs/ca-certificates.crt'] as $f)if(is_file($f)){$o[CURLOPT_CAINFO]=$f;break;}}
function gemini_call($system,$messages,$max=500){
$c=[];foreach($messages as $m){$t=is_array($m['content'])?implode("\n",array_map(fn($b)=>(string)($b['text']??''),$m['content'])):(string)$m['content'];$c[]=['role'=>($m['role']==='assistant'?'model':'user'),'parts'=>[['text'=>$t]]];}
$body=['system_instruction'=>['parts'=>[['text'=>$system]]],'contents'=>$c,'generationConfig'=>['maxOutputTokens'=>max(2048,$max*4)]];
$err='no answer';$models=array_values(array_unique(array_filter(array_map('trim',array_merge([GEMINI_MODEL],explode(',',GEMINI_BACKUP_MODELS))))));
foreach($models as $k=>$model){for($try=0;$try<2;$try++){
$ch=curl_init('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent');
$o=[CURLOPT_POST=>1,CURLOPT_RETURNTRANSFER=>1,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>40,CURLOPT_POSTFIELDS=>json_encode($body),CURLOPT_HTTPHEADER=>['content-type: application/json','x-goog-api-key: '.GEMINI_KEY]];ai_cainfo($o);
curl_setopt_array($ch,$o);$raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$r=json_decode((string)$raw,true);
$txt='';foreach(($r['candidates'][0]['content']['parts']??[]) as $p)if(isset($p['text'])&&empty($p['thought']))$txt.=$p['text'];
if(trim($txt)!=='')return ['ok'=>true,'text'=>trim($txt)];
$err=$r['error']['message']??(curl_error($ch)?:(isset($r['candidates'][0]['finishReason'])?'stopped ('.$r['candidates'][0]['finishReason'].')':'no answer'));
$busy=in_array($code,[429,500,503,504],true)||$code===0||preg_match('/high demand|overloaded|unavailable|try again|RESOURCE_EXHAUSTED/i',$err);
$gone=$code===404||preg_match('/not found|not supported|no longer available|not available to new/i',$err);if($gone)$last_gone=$err;
if($gone)break;if(!$busy)return ['ok'=>false,'text'=>'AI_ERR:'.$err];if($try===0)usleep(800000);}}
return ['ok'=>false,'text'=>'AI_ERR:'.$err.' (the AI did not answer, try again in a minute)'];}
function openai_call($system,$messages,$max=500){
$msgs=[['role'=>'system','content'=>$system]];foreach($messages as $m){$t=is_array($m['content'])?implode("\n",array_map(fn($b)=>(string)($b['text']??''),$m['content'])):(string)$m['content'];$msgs[]=['role'=>($m['role']==='assistant'?'assistant':'user'),'content'=>$t];}
$err='no answer';$models=array_values(array_unique(array_filter(array_map('trim',array_merge([OPENAI_MODEL],explode(',',OPENAI_BACKUP_MODELS))))));
foreach($models as $model){for($try=0;$try<2;$try++){
$ch=curl_init('https://api.openai.com/v1/chat/completions');
$o=[CURLOPT_POST=>1,CURLOPT_RETURNTRANSFER=>1,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>45,CURLOPT_POSTFIELDS=>json_encode(['model'=>$model,'messages'=>$msgs,'max_completion_tokens'=>max(2048,$max*4)]),CURLOPT_HTTPHEADER=>['content-type: application/json','Authorization: Bearer '.OPENAI_KEY]];ai_cainfo($o);
curl_setopt_array($ch,$o);$raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$r=json_decode((string)$raw,true);
$txt=trim((string)($r['choices'][0]['message']['content']??''));
if($txt!=='')return ['ok'=>true,'text'=>$txt];
$err=$r['error']['message']??(curl_error($ch)?:(isset($r['choices'][0]['finish_reason'])?'stopped ('.$r['choices'][0]['finish_reason'].')':'no answer'));
$busy=in_array($code,[429,500,502,503,504],true)||$code===0||preg_match('/overloaded|rate limit|try again|temporarily/i',$err);
$gone=$code===404||preg_match('/does not exist|not found|do not have access|not supported/i',$err);
if(preg_match('/quota|billing|insufficient/i',$err))return ['ok'=>false,'text'=>'AI_ERR:'.$err];
if($gone)break;if(!$busy)return ['ok'=>false,'text'=>'AI_ERR:'.$err];if($try===0)usleep(800000);}}
return ['ok'=>false,'text'=>'AI_ERR:'.$err.' (the AI did not answer, try again in a minute)'];}
function ai_call($system,$messages,$max=500){if(AI_OPENAI){$r=openai_call($system,$messages,$max);if($r['ok']||!AI_GEMINI)return $r;$g=gemini_call($system,$messages,$max);return $g['ok']?$g:$r;}
if(AI_GEMINI)return gemini_call($system,$messages,$max);$ch=curl_init(AI_URL);
$o=[CURLOPT_POST=>1,CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>45,CURLOPT_POSTFIELDS=>json_encode(['model'=>AI_MODEL,'max_tokens'=>$max,'system'=>$system,'messages'=>$messages]),CURLOPT_HTTPHEADER=>['content-type: application/json','x-api-key: '.AI_KEY,'anthropic-version: 2023-06-01']];
foreach(['C:/xampp/apache/bin/curl-ca-bundle.crt','C:/xampp/php/extras/ssl/cacert.pem','/etc/ssl/certs/ca-certificates.crt'] as $f)if(is_file($f)){$o[CURLOPT_CAINFO]=$f;break;}
curl_setopt_array($ch,$o);$raw=curl_exec($ch);$r=json_decode((string)$raw,true);
if(isset($r['content'][0]['text']))return ['ok'=>true,'text'=>$r['content'][0]['text']];
return ['ok'=>false,'text'=>'AI_ERR:'.($r['error']['message']??(curl_error($ch)?:'no answer from the AI service'))];}
function lesson_ai($lang,$lvl,$title,$n){if(!AI_ON)return null;
$sys='You write clear lessons for beginner IT students. Use very basic student-level code: short programs, simple names like x, name or total, no advanced features, a short comment on each step. Plain text only, no markdown headings. Wrap every important term in **double asterisks** the first time it appears. Structure: 2-3 sentences of simple explanation; then "Example 1:" followed by a code block; "Example 2:" followed by a different code block; "Example 3:" followed by a third code block; "Common mistake:" one line; "Practice:" one small task. Put code in blocks fenced with three backticks on their own lines (no language label). 200-300 words in total.';
if(AI_OPENAI){$g=openai_call($sys,[['role'=>'user','content'=>"Write lesson $n of 50 in the $lvl $lang course. Lesson title: $title"]],1100);if($g['ok'])return $g['text'];}
if(AI_GEMINI){$g=gemini_call($sys,[['role'=>'user','content'=>"Write lesson $n of 50 in the $lvl $lang course. Lesson title: $title"]],1100);return $g['ok']?$g['text']:null;}
$ch=curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch,[CURLOPT_POST=>1,CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>60,CURLOPT_POSTFIELDS=>json_encode(['model'=>AI_MODEL,'max_tokens'=>1100,'system'=>$sys,'messages'=>[['role'=>'user','content'=>"Write lesson $n of 50 in the $lvl $lang course. Lesson title: $title"]]]),CURLOPT_HTTPHEADER=>['content-type: application/json','x-api-key: '.AI_KEY,'anthropic-version: 2023-06-01']]);
$r=json_decode(curl_exec($ch),true);return $r['content'][0]['text']??null;}
function wrapcode($lg,$b){if(substr($b,0,1)==='!')return substr($b,1);if($lg==='cpp')return "#include <iostream>\n#include <string>\n#include <vector>\nusing namespace std;\n\nint main() {\n    ".str_replace("\n","\n    ",$b)."\n    return 0;\n}";return $lg==='php'?"<?php\n".$b:$b;}
function offline_lesson($lg,$lvl,$title){$d=json_decode(file_get_contents(__DIR__.'/data/fallback.json'),true);$t=strtolower($title);$lg=strtolower($lg);$n=lname($lg);
if($lg==='html'){foreach($d['html'] as $k)if(preg_match('/'.$k[0].'/',$t))return "This $lvl HTML and CSS lesson is about **$title**.\n\nAbout:\n".$k[1]."\n\nExample 1:\n```\n".$k[2]."\n```\nPractice:\n1. Copy the example into the playground and run it.\n2. Change the text, colors or sizes and look at the page again.\n3. Explain in your own words what each tag does.";
return "This $lvl HTML and CSS lesson is about **$title**.\n\nAbout:\nLearn **$title** in small steps. Write a tiny page that uses it, open it, then change one thing at a time and see what changes.\n\nExample 1:\n```\n<h1>My practice page</h1>\n<p>Try $title here.</p>\n```\nPractice:\n1. Build a small page that uses $title.\n2. Change one thing and predict the result first.\n3. Explain what each part does.";}
foreach($d['prog'] as $k)if(preg_match('/'.$k['re'].'/',$t)){$f='';foreach($k['focus'] as $x)if(preg_match('/'.$x[0].'/',$t)){$f=' '.$x[1];break;}
$st='';foreach($k['steps'] as $i=>$s)$st.=($i+1).'. '.$s."\n";$mi='';foreach($k['mistakes'] as $s)$mi.='- '.$s."\n";$pr='';foreach($k['practice'] as $i=>$s)$pr.=($i+1).'. '.$s."\n";
return "This $lvl $n lesson is about **$title**.$f\n\nAbout:\n".$k['what']."\n\nWhy it matters:\n".$k['why']."\n\nExample 1:\n```\n".wrapcode($lg,$k['code'][$lg])."\n```\nOutput:\n```\n".$k['out']."\n```\nStep by step:\n$st\nCommon mistakes:\n$mi\nPractice:\n$pr";}
return "This $lvl $n lesson is about **$title**.\n\nAbout:\nLearn **$title** in small steps: read about it, write a tiny program that uses it, run it, then change one thing and see what happens.\n\nExample 1:\n```\n".wrapcode($lg,$d['hello'][$lg])."\n```\nStep by step:\n1. Run the example above in the playground.\n2. Add a few lines that use $title.\n3. Run it again and read the output.\n\nPractice:\n1. Write a small program that uses $title.\n2. Change one value and guess the new output first.\n3. Explain the output in your own words.";}
function lang6(){return ['python','javascript','cpp','csharp','php','html'];}
function chat_box($p,$chips,$withCode=false){$h='<div class="card chat'.($withCode?' mini':'').'"><div id="'.$p.'log" class="log" aria-live="polite"></div><form id="'.$p.'cf"><textarea id="'.$p.'q" rows="3" placeholder="Type your question here. Enter sends, Shift+Enter makes a new line."></textarea><div class="inl"><button id="'.$p.'send">Send</button><button type="button" class="alt" id="'.$p.'clear">New chat</button>'.($withCode?'<label class="chk"><input type="checkbox" id="'.$p.'code" checked> Send my code with my question</label>':'').'<small id="'.$p.'left"></small></div></form><div class="chips" id="'.$p.'chips">';foreach($chips as $x)$h.='<button type="button" class="alt chip">'.e($x).'</button>';return $h.'</div></div>';}
function lb($l,$c='lb'){return '<img class="'.$c.'" src="assets/img/'.e($l).'.svg" alt="">';}
function tile($href,$ico,$t,$sub,$n=''){return '<a class="card tile2" href="'.$href.'"><img class="icon" src="assets/img/ic-'.$ico.'.png" alt=""><h3>'.$t.($n!==''?' <span class="pill">'.e($n).'</span>':'').'</h3><small>'.$sub.'</small></a>';}
function navlinks($u){if(!$u)return '<a href="Logging/login.php?open=1">Log in</a><a href="Logging/register.php">Sign up</a>';return '';}
// the menu of each role: [page, label, icon]
function menu_items($u){$r=$u['role']??'';
if($r==='admin')return [['Admin/admin.php','Dashboard','home'],['Admin/users.php','Users','users'],['Dashboard/approve.php','Approvals','approve'],['Admin/content.php','Quizzes &amp; exams','layers'],['Grades/promote.php','School year','cap'],['Admin/email.php','Email','mail'],['Admin/admin_security.php','Security','shield']];
if($r==='teacher')return [['Dashboard/dashboard.php','Dashboard','home'],['Classes/classes.php','Classes','class'],['Dashboard/approve.php','Approvals','approve'],['Quiz/quizzes.php','Quiz','quiz'],['Exam/exams.php','Code Exam','exam'],['Grades/grades.php','Grades','grades']];
return [['Dashboard/dashboard.php','Dashboard','home'],['Classes/classes.php','Classes','class'],['Modules/learn.php','Module','book'],['Challenges/challenges.php','Challenges','bug'],['Playground/playground.php','Playground','code'],['Assistant/assistant.php','AI Help','bot'],['Quiz/quizzes.php','Quiz','quiz'],['Exam/exams.php','Code Exam','exam'],['Grades/grades.php','Grades','grades']];}
// simple line icons (they take the text color, so they work in dark and light mode)
function ico($k){$p=['home'=>'<path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V21h4.5v-6h4v6h4.5V9.5"/>','book'=>'<path d="M2.5 5.5C5 4 8.5 4 12 6c3.5-2 7-2 9.5-.5V20c-2.5-1.5-6-1.5-9.5.5-3.5-2-7-2-9.5-.5Z"/><path d="M12 6v14.5"/>','bug'=>'<rect x="7" y="7" width="10" height="13" rx="5"/><path d="M12 11v9M9.5 4.5 10.5 7M14.5 4.5 13.5 7M3 13h4M17 13h4M4 8.5l3 2M20 8.5l-3 2M4 19l3-2M20 19l-3-2"/>','code'=>'<path d="m8 7-5 5 5 5M16 7l5 5-5 5M14 4l-4 16"/>','bot'=>'<rect x="4" y="8" width="16" height="12" rx="3"/><path d="M12 8V4.5M9.5 4.5h5M2 13v3M22 13v3"/><circle cx="9" cy="14" r="1.2"/><circle cx="15" cy="14" r="1.2"/>','quiz'=>'<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2.8h6V4"/><path d="m8.8 13 2.2 2.2 4.2-4.4"/>','exam'=>'<path d="M14 3H6.5A2.5 2.5 0 0 0 4 5.5v13A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5V9z"/><path d="M14 3v6h6"/><path d="m10 12.5-2 2 2 2M14 12.5l2 2-2 2"/>','grades'=>'<path d="M3.5 20.5h17"/><rect x="5" y="11" width="3.2" height="7" rx=".6"/><rect x="10.4" y="5.5" width="3.2" height="12.5" rx=".6"/><rect x="15.8" y="13" width="3.2" height="5" rx=".6"/>','approve'=>'<circle cx="9" cy="8" r="3.8"/><path d="M2 20.5c0-3.8 3-6.3 7-6.3 2 0 3.7.6 5 1.7"/><path d="m15.5 18 2.2 2.2 4.3-4.4"/>','users'=>'<circle cx="9" cy="8" r="3.8"/><path d="M2 20.5c0-3.8 3-6.3 7-6.3s7 2.5 7 6.3"/><path d="M15.5 4.5a3.6 3.6 0 0 1 0 7M18 14.6c2.4.8 4 2.8 4 5.9"/>','layers'=>'<path d="m12 3 9.5 5-9.5 5-9.5-5z"/><path d="m2.5 12.5 9.5 5 9.5-5"/><path d="m2.5 16.5 9.5 5 9.5-5"/>','cap'=>'<path d="m1.8 9 10.2-5 10.2 5-10.2 5z"/><path d="M6 11v5.2c3.3 2.6 8.7 2.6 12 0V11"/><path d="M22.2 9v6"/>','mail'=>'<rect x="3" y="5" width="18" height="14" rx="2.2"/><path d="m3.5 7 8.5 6 8.5-6"/>','bell'=>'<path d="M6 16.5V11a6 6 0 0 1 12 0v5.5l1.8 1.8H4.2z"/><path d="M10 21h4"/>','out'=>'<path d="M10 3.5H5.5a1.5 1.5 0 0 0-1.5 1.5v14a1.5 1.5 0 0 0 1.5 1.5H10"/><path d="m15 7.5 4.5 4.5-4.5 4.5M19.5 12H9"/>','menu'=>'<path d="M4 6.5h16M4 12h16M4 17.5h16"/>','class'=>'<rect x="2.5" y="3.5" width="19" height="12.5" rx="1.6"/><path d="M7.5 8h9M7.5 11.5h5.5M8 20.5l2.2-4.5M16 20.5l-2.2-4.5"/>','play'=>'<path d="M7 4.5v15l12-7.5z"/>','file'=>'<path d="M14 3H6.5A2.5 2.5 0 0 0 4 5.5v13A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5V9z"/><path d="M14 3v6h6"/>','plus'=>'<path d="M12 5v14M5 12h14"/>','full'=>'<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>','clock'=>'<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>','shield'=>'<path d="M12 3 4.5 6v6c0 4.6 3.2 7.9 7.5 9 4.3-1.1 7.5-4.4 7.5-9V6z"/><path d="m9 12 2.2 2.2L15.5 10"/>','down'=>'<path d="m6 9 6 6 6-6"/>','eye'=>'<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>'];
return '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($p[$k]??'').'</svg>';}
// the small red numbers in the left menu: new quizzes / code exams (students), exams to grade (teachers), accounts to approve
function menu_badges($u){global $pdo;$b=[];try{if($u['role']==='student'){
foreach([['Quiz/quizzes.php','SELECT q.opens_at,q.due_at,q.closed FROM quizzes q JOIN class_members m ON m.class_id=q.class_id AND m.student_id=? JOIN classes c ON c.id=q.class_id AND c.archived=0 WHERE NOT EXISTS(SELECT 1 FROM attempts a WHERE a.quiz_id=q.id AND a.student_id=m.student_id)'],['Exam/exams.php','SELECT x.opens_at,x.due_at,x.closed FROM coding_exams x JOIN class_members m ON m.class_id=x.class_id AND m.student_id=? JOIN classes c ON c.id=x.class_id AND c.archived=0 WHERE NOT EXISTS(SELECT 1 FROM exam_attempts a WHERE a.exam_id=x.id AND a.student_id=m.student_id)']] as $q){$s=$pdo->prepare($q[1]);$s->execute([$u['id']]);$b[$q[0]]=count(array_filter($s->fetchAll(),'can_start'));}}
else{$b['Dashboard/approve.php']=pending_count($u);if($u['role']==='teacher'){$s=$pdo->prepare('SELECT COUNT(*) FROM exam_attempts a JOIN coding_exams x ON x.id=a.exam_id WHERE x.teacher_id=? AND a.submitted_at IS NOT NULL AND a.score IS NULL');$s->execute([$u['id']]);$b['Exam/exams.php']=(int)$s->fetchColumn();}}}catch(Throwable $x){}return $b;}
// the left menu (sidebar) of a logged-in person. The page of the menu item is marked; a page inside the same folder marks it too (quiz.php marks Quiz)
function sidebar($u){$items=menu_items($u);$file=basename($_SERVER['SCRIPT_NAME']);$dir=basename(dirname($_SERVER['SCRIPT_NAME']));$on=-1;if($file==='playground.php'&&isset($_GET['c'])){$file='challenges.php';$dir='Challenges';} // a challenge is part of Challenges
foreach($items as $k=>$a)if(basename($a[0])===$file){$on=$k;break;}
if($on<0)foreach($items as $k=>$a){$d=dirname($a[0]);if($d===$dir&&$d!=='Dashboard'&&$d!=='Admin'){$on=$k;break;}}
$nb=menu_badges($u);$h='<aside class="side" id="side"><nav aria-label="Main menu">';
foreach($items as $k=>$a){$p=(int)($nb[$a[0]]??0);$h.='<a href="'.$a[0].'"'.($k===$on?' class="on" aria-current="page"':'').'><span class="si">'.ico($a[2]).($p?'<b class="nb">'.($p>9?'9+':$p).'</b>':'').'</span><span>'.$a[1].'</span></a>';}
$h.='</nav>';
if(!quiet())$h.='<form method="post" action="Logging/logout.php" class="out"><input type="hidden" name="t" value="'.csrf().'"><button type="submit"><span class="si">'.ico('out').'</span><span>Log out</span></button></form>';
return $h.'</aside><div class="shade" id="shade"></div>';}
function more_links($l){$m=['python'=>[['W3Schools Python','https://www.w3schools.com/python/'],['Python docs','https://docs.python.org/3/tutorial/']],'javascript'=>[['W3Schools JavaScript','https://www.w3schools.com/js/'],['MDN JavaScript','https://developer.mozilla.org/en-US/docs/Web/JavaScript']],'cpp'=>[['W3Schools C++','https://www.w3schools.com/cpp/'],['cppreference','https://en.cppreference.com/']],'csharp'=>[['W3Schools C#','https://www.w3schools.com/cs/'],['Microsoft Learn C#','https://learn.microsoft.com/dotnet/csharp/']],'php'=>[['W3Schools PHP','https://www.w3schools.com/php/'],['PHP manual','https://www.php.net/manual/en/']],'html'=>[['W3Schools HTML','https://www.w3schools.com/html/'],['W3Schools CSS','https://www.w3schools.com/css/'],['MDN Learn','https://developer.mozilla.org/en-US/docs/Learn']]];$h='<small>Extra reading: </small>';foreach($m[$l]??[] as $x)$h.='<a href="'.$x[1].'" target="_blank" rel="noopener">'.$x[0].'</a> ';return $h;}
