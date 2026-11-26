<?php require __DIR__.'/../config.php';$u=need('any');check();header('Content-Type: application/json');
// MY FILES of the Playground (version 42). A file is made ONLY when the student presses + (New file) or uploads one.
// Typing saves into that same file (an UPDATE of one row), so the database never fills up with copies.
const PG_MAX_FILES=60;const PG_MAX_SIZE=100000;
function out($a){echo json_encode($a);exit;}
function pg_ok($n){return (bool)preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,50}\.(py|js|html|css|php|cpp|cs|java)$/',$n);}
function pg_lang($n){return ['py'=>'python','js'=>'javascript','html'=>'html','css'=>'css','php'=>'php','cpp'=>'cpp','cs'=>'csharp','java'=>'java'][strtolower(pathinfo($n,PATHINFO_EXTENSION))]??'text';}
function pg_start($n){return ['python'=>"# $n\nprint(\"Hello, IT student!\")\n",'javascript'=>"// $n\nconsole.log(\"Hello, IT student!\");\n",'html'=>"<!DOCTYPE html>\n<html>\n<head>\n  <meta charset=\"utf-8\">\n  <title>My page</title>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <h1>Hello!</h1>\n</body>\n</html>\n",'css'=>"body {\n  font-family: Arial, sans-serif;\n  margin: 2rem;\n}\n",'php'=>"<?php\necho \"Hello, IT student!\";\n",'cpp'=>"#include <iostream>\nusing namespace std;\n\nint main() {\n    cout << \"Hello, IT student!\" << endl;\n    return 0;\n}\n",'csharp'=>"Console.WriteLine(\"Hello, IT student!\");\n",'java'=>"public class Main {\n    public static void main(String[] args) {\n        System.out.println(\"Hello, IT student!\");\n    }\n}\n"][pg_lang($n)]??'';}
$do=(string)($_POST['do']??'');$n=trim((string)($_POST['name']??''));
$bad='Use a name like hello.py, index.html or style.css (letters, numbers, - and _, then the file type).';
if($do==='create'){if(!pg_ok($n))out(['err'=>$bad]);
$c=$pdo->prepare('SELECT COUNT(*) FROM saved_files WHERE user_id=?');$c->execute([$u['id']]);if((int)$c->fetchColumn()>=PG_MAX_FILES)out(['err'=>'You have '.PG_MAX_FILES.' files already. Delete some you do not need first.']);
$code=isset($_POST['code'])?mb_substr((string)$_POST['code'],0,PG_MAX_SIZE):pg_start($n);
try{$pdo->prepare('INSERT INTO saved_files(user_id,name,language,code) VALUES(?,?,?,?)')->execute([$u['id'],$n,pg_lang($n),$code]);}catch(PDOException $x){out(['err'=>'You already have a file with that name.']);}
out(['code'=>$code,'time'=>date('g:i A')]);}
if($do==='save'){$code=(string)($_POST['code']??'');if(strlen($code)>PG_MAX_SIZE)out(['err'=>'This file is too big to save (more than 100 KB).']);
$s=$pdo->prepare('UPDATE saved_files SET code=? WHERE user_id=? AND name=?');$s->execute([$code,$u['id'],$n]);
if(!$s->rowCount()){$e=$pdo->prepare('SELECT 1 FROM saved_files WHERE user_id=? AND name=?');$e->execute([$u['id'],$n]);if(!$e->fetchColumn())out(['err'=>'This file does not exist any more.']);}
out(['time'=>date('g:i A')]);}
if($do==='delete'){$pdo->prepare('DELETE FROM saved_files WHERE user_id=? AND name=?')->execute([$u['id'],$n]);out(['ok'=>1]);}
if($do==='rename'){$to=trim((string)($_POST['to']??''));if(!pg_ok($to))out(['err'=>$bad]);
try{$s=$pdo->prepare('UPDATE saved_files SET name=?,language=? WHERE user_id=? AND name=?');$s->execute([$to,pg_lang($to),$u['id'],$n]);}catch(PDOException $x){out(['err'=>'You already have a file with that name.']);}
out(['ok'=>1]);}
out(['err'=>'Unknown action.']);
