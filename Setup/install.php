<?php
// Open once in your browser: http://localhost/capstone-system/Setup/install.php
// It runs database/capstone_db.sql (safe: makes what is missing, deletes nothing) and brings the tables up to date.
// On web hosting open it as Setup/install.php?key=YOUR_INSTALL_KEY (set INSTALL_KEY in config.php first), then DELETE Setup/install.php.
$cfg=file_get_contents(dirname(__DIR__).'/config.php');
function cfg($n){global $cfg;return preg_match("/^const $n='([^']*)'/m",$cfg,$m)?$m[1]:null;}
$key=cfg('INSTALL_KEY');
$local=in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1']);
if(!$local&&!($key!==null&&$key!==''&&hash_equals($key,(string)($_GET['key']??''))))die('Open this page on the same computer that runs the server, or set INSTALL_KEY in config.php and open Setup/install.php?key=THAT_TEXT');
if(cfg('DB_HOST')===null||cfg('DB_NAME')===null)die('Could not read the database settings from config.php');
$host=cfg('DB_HOST');$db=cfg('DB_NAME');$user=cfg('DB_USER');$pass=cfg('DB_PASS');$msg=[];$err='';require_once dirname(__DIR__).'/database/schema.php';
// version 47: ONE clean file, database/capstone_db.sql. It is SAFE to run every time: it makes the tables that are missing and
// fills lessons / challenges only when they are empty. It never deletes accounts, classes, quizzes, grades or the email setup.
try{$pdo=new PDO("mysql:host=$host;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
try{$pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4");}catch(Throwable $e){} // web hosts often do not allow this: make the database in their control panel first
$pdo->exec("USE `$db`");
$f=dirname(__DIR__).'/database/capstone_db.sql';if(!is_file($f))throw new Exception('database/capstone_db.sql is missing from the folder.');
$sql=preg_replace('/^(CREATE DATABASE [^;\n]*|USE [`\w]+);[ \t]*\r?$/m','',file_get_contents($f)); // the right database is already selected above
$st=$pdo->query($sql);if($st){do{}while($st->nextRowset());}
$msg[]='Database checked with database/capstone_db.sql: missing tables were made, lessons and challenges were added where they were empty. Nothing was deleted.';
ensure_schema($pdo);$msg[]='Tables and columns checked: everything the pages need is there.';
$n=(int)$pdo->query("SELECT COUNT(*) FROM challenges WHERE language='html'")->fetchColumn();$msg[]='HTML / CSS challenges in the database: '.$n.' (should be 200).';
}catch(Throwable $e){$err=$e->getMessage();}
?><!doctype html><html><head><meta charset="utf-8"><title>Install</title><style>body{font:16px/1.6 system-ui;max-width:700px;margin:2rem auto;padding:0 1rem;background:#0f1220;color:#e6e9f5}code{background:#171b2e;padding:.1rem .4rem}.e{color:#fb7185}.o{color:#4ade80}a{color:#4cc9f0}</style></head><body>
<h1>CodeSmartAi-X setup</h1>
<?php if($err):?><p class="e"><b>Stopped:</b> <?=htmlspecialchars($err)?></p><p>If the message mentions <code>max_allowed_packet</code>, set <code>max_allowed_packet=64M</code> in XAMPP's <code>my.ini</code>, restart MySQL and open this page again. It continues where it stopped.</p>
<?php else:?><p class="o"><b>Your database is up to date.</b></p><?php endif;foreach($msg as $x)echo '<p>'.htmlspecialchars($x).'</p>';?>
<p><a href="../Logging/login.php">Go to the login page</a>. When everything is fine, delete <code>Setup/install.php</code> from the folder.</p></body></html>
