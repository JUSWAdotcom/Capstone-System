<?php require __DIR__.'/../config.php';$u=need('all');
// Shows the picture of a code exam. Only the teacher of the exam, the admin, and the students of its class (or who took it) may see it.
$id=(int)($_GET['id']??0);$s=$pdo->prepare('SELECT id,teacher_id,class_id,attachment FROM coding_exams WHERE id=?');$s->execute([$id]);$x=$s->fetch();
$ok=$x&&$x['attachment']&&($u['role']==='admin'||(int)$x['teacher_id']===(int)$u['id']||($u['role']==='student'&&(in_class($x['class_id'],$u['id'])||(function()use($pdo,$id,$u){$a=$pdo->prepare('SELECT 1 FROM exam_attempts WHERE exam_id=? AND student_id=?');$a->execute([$id,$u['id']]);return (bool)$a->fetchColumn();})())));
$f=$ok&&preg_match('/^[a-f0-9]{24}\.(png|jpg|gif|webp)$/',$x['attachment'],$m)?UPLOAD_DIR.'/'.$x['attachment']:'';
if(!$f||!is_file($f)){http_response_code(404);exit('Not found');}
header('Content-Type: '.['png'=>'image/png','jpg'=>'image/jpeg','gif'=>'image/gif','webp'=>'image/webp'][$m[1]]);
header('X-Content-Type-Options: nosniff');header('Content-Disposition: inline; filename="expected-output.'.$m[1].'"');header('Cache-Control: private, max-age=3600');header("Content-Security-Policy: default-src 'none'");
readfile($f);
