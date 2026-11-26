<?php require __DIR__.'/../config.php';$u=need('any');check();header('Content-Type: application/json');
$slot=$_POST['slot']??'';// only challenges keep their work here; the Playground saves into the student's own files (files.php)
if(!preg_match('/^ch\d{1,6}$/',$slot))die(json_encode(['time'=>'(bad slot)']));
$pdo->prepare('INSERT INTO saved_work(user_id,slot,code) VALUES(?,?,?) ON DUPLICATE KEY UPDATE code=VALUES(code)')->execute([$u['id'],$slot,mb_substr($_POST['code']??'',0,30000)]);
echo json_encode(['time'=>date('H:i:s')]);
