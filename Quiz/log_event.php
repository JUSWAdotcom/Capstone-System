<?php require __DIR__.'/../config.php';$u=need('student');check();
$t=$_POST['type']??'';$id=(int)($_POST['attempt']??0);if(!in_array($t,['tab_switch','fullscreen_exit'],true))exit; // copy, paste, right-click... are blocked, not recorded (version 45)
$s=$pdo->prepare('SELECT id FROM attempts WHERE id=? AND student_id=?');$s->execute([$id,$u['id']]);
if($s->fetch())$pdo->prepare('INSERT INTO cheat_events(attempt_id,event_type) VALUES(?,?)')->execute([$id,$t]);
