<?php require __DIR__.'/../config.php';$u=need('student');check();
$pdo->prepare('INSERT IGNORE INTO challenge_solves(user_id,challenge_id) VALUES(?,?)')->execute([$u['id'],(int)($_POST['id']??0)]);
