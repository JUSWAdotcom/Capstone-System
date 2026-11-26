<?php require __DIR__.'/../config.php';$u=need('student');
if(isset($_GET['go'])){$g=(int)$_GET['go'];$s=$pdo->prepare('SELECT link FROM notifications WHERE id=? AND user_id=?');$s->execute([$g,$u['id']]);$l=(string)$s->fetchColumn();
$pdo->prepare('UPDATE notifications SET read_at=NOW(),shown=1 WHERE id=? AND user_id=? AND read_at IS NULL')->execute([$g,$u['id']]);
foreach(['quiz.php'=>'Quiz/','exams.php'=>'Exam/','grades.php'=>'Grades/'] as $old=>$dir){if(strpos($l,$old)===0)$l=$dir.$l;} // links saved by an older version
header('Location: '.BASE.(preg_match('/^[A-Za-z]+\/[a-z_]+\.php(\?[a-z0-9=&_]*)?$/',$l)?$l:'Dashboard/notifications.php'));exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){check();$pdo->prepare('UPDATE notifications SET read_at=NOW(),shown=1 WHERE user_id=? AND read_at IS NULL')->execute([$u['id']]);header('Location: '.BASE.'Dashboard/notifications.php');exit;}
head('Notifications');
$s=$pdo->prepare('SELECT id,kind,message,read_at,created_at FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 50');$s->execute([$u['id']]);$r=$s->fetchAll();
if(!$r)echo '<p><small>Nothing yet. When your teacher sets a quiz or a code exam, or grades your exam, you are told here.</small></p>';
else{echo '<form method="post" class="inl" style="margin:0 0 1rem"><input type="hidden" name="t" value="'.csrf().'"><button class="alt">Mark all as read</button></form>';
$ic=['quiz'=>'📝','exam'=>'🧪','grade'=>'📊','year'=>'🎓','announce'=>'📢'];
foreach($r as $n)echo '<a class="card note'.($n['read_at']?'':' unread').'" href="Dashboard/notifications.php?go='.(int)$n['id'].'"><span class="ico">'.($ic[$n['kind']]??'🔔').'</span><b>'.e($n['message']).'</b>'.($n['read_at']?'':' <span class="pill">New</span>').'<small>'.e(substr($n['created_at'],0,16)).'</small></a>';}
foot();
