<?php
// Adds anything the pages need that an older database is missing. Safe to run again and again.
const SCHEMA_V=19; // 18-19 = version 47: removes what is not used any more, and unfinished sign-ups
// The 200 HTML / CSS challenges live in data/html_challenges.json. This puts back any that are missing,
// so the page never shows "0 of 0" even if Setup/install.php was not opened. It never touches the other languages.
function ensure_html_challenges(PDO $pdo){
if(!(bool)$pdo->query("SHOW TABLES LIKE 'challenges'")->fetchColumn())return;
$f=dirname(__DIR__).'/data/html_challenges.json';if(!is_file($f))return;
$rows=json_decode((string)file_get_contents($f),true);if(!is_array($rows)||count($rows)<1)return;
$want=[];foreach($rows as $r)$want[$r['level']."\n".$r['title']]=$r;
$seen=[];$extra=[];$fix=[];
foreach($pdo->query("SELECT id,level,title,buggy_code,hint,expected_output FROM challenges WHERE language='html' ORDER BY id") as $r){$k=$r['level']."\n".$r['title'];
if(!isset($want[$k])||isset($seen[$k])){$extra[]=(int)$r['id'];continue;}
$seen[$k]=1;$w=$want[$k];if($r['buggy_code']!==$w['code']||$r['hint']!==$w['hint']||$r['expected_output']!==$w['expected'])$fix[(int)$r['id']]=$w;}
$missing=array_diff_key($want,$seen);
if(!$missing&&!$extra&&!$fix)return;
$pdo->beginTransaction();
try{
$ins=$pdo->prepare("INSERT INTO challenges(language,level,title,buggy_code,hint,expected_output) VALUES('html',?,?,?,?,?)");
foreach($missing as $w)$ins->execute([$w['level'],$w['title'],$w['code'],$w['hint'],$w['expected']]);
$up=$pdo->prepare('UPDATE challenges SET buggy_code=?,hint=?,expected_output=? WHERE id=?');
foreach($fix as $id=>$w)$up->execute([$w['code'],$w['hint'],$w['expected'],$id]);
$del=$pdo->prepare('DELETE FROM challenges WHERE id=?');foreach($extra as $id)$del->execute([$id]);
$pdo->commit();
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
// The ONE admin account (version 27). It is made from ADMIN_ID and ADMIN_START_PASSWORD in config.php, only when no admin exists.
// If a student already took that Student ID or email, nothing is made (change ADMIN_ID in config.php, see the README).
function ensure_admin(PDO $pdo){
if((int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()>0)return;
$s=$pdo->prepare('SELECT 1 FROM users WHERE student_id=? OR email=?');$s->execute([ADMIN_ID,ADMIN_EMAIL]);if($s->fetchColumn())return;
$pdo->prepare("INSERT INTO users(full_name,student_id,email,password_hash,role) VALUES('Administrator',?,?,?,'admin')")->execute([ADMIN_ID,ADMIN_EMAIL,password_hash(ADMIN_START_PASSWORD,PASSWORD_DEFAULT)]);}
function ensure_schema(PDO $pdo){
$htmlErr=null;try{ensure_html_challenges($pdo);}catch(Throwable $e){$htmlErr=$e;} // first, so an error further down cannot block it
$tab=function($t)use($pdo){return (bool)$pdo->query("SHOW TABLES LIKE '$t'")->fetchColumn();};
$col=function($t,$c)use($pdo){$r=$pdo->query("SHOW COLUMNS FROM `$t` LIKE '$c'")->fetch(PDO::FETCH_ASSOC);return $r?:null;};
if($tab('users')&&!$col('users','student_id'))$pdo->exec('ALTER TABLE users ADD student_id VARCHAR(30) NULL UNIQUE AFTER full_name');
if($tab('coding_exams')&&!$col('coding_exams','max_points'))$pdo->exec('ALTER TABLE coding_exams ADD max_points INT NOT NULL DEFAULT 100');
if($tab('exam_attempts')){
foreach([['score','INT NULL'],['feedback','VARCHAR(500) NULL'],['graded_at','DATETIME NULL'],['year_level','TINYINT NULL']] as $a)if(!$col('exam_attempts',$a[0]))$pdo->exec('ALTER TABLE exam_attempts ADD '.$a[0].' '.$a[1]);}
if($tab('quizzes')&&!$col('quizzes','show_answers'))$pdo->exec('ALTER TABLE quizzes ADD show_answers TINYINT NOT NULL DEFAULT 1');
// school years (version 25): the year a student was in when the work was done, quizzes/exams can be closed, and every quiz/exam is marked with its school year
if($tab('attempts')&&!$col('attempts','year_level'))$pdo->exec('ALTER TABLE attempts ADD year_level TINYINT NULL');
if($tab('quizzes')&&!$col('quizzes','closed'))$pdo->exec('ALTER TABLE quizzes ADD closed TINYINT NOT NULL DEFAULT 0');
if($tab('quizzes')&&!$col('quizzes','school_year'))$pdo->exec('ALTER TABLE quizzes ADD school_year VARCHAR(9) NULL');
if($tab('coding_exams')&&!$col('coding_exams','closed'))$pdo->exec('ALTER TABLE coding_exams ADD closed TINYINT NOT NULL DEFAULT 0');
if($tab('coding_exams')&&!$col('coding_exams','school_year'))$pdo->exec('ALTER TABLE coding_exams ADD school_year VARCHAR(9) NULL');
// year levels: a student has one year (1 to 4, 5 = graduated); a quiz or code exam lists the years that may take it ('1234' = every year)
if($tab('users')&&!$col('users','year_level'))$pdo->exec('ALTER TABLE users ADD year_level TINYINT NULL AFTER student_id');
// the role 'admin' (one account, version 27)
// version 27: email verification. Accounts that already exist count as verified (DEFAULT 1); register.php makes new ones with 0
if($tab('users')&&!$col('users','email_verified'))$pdo->exec('ALTER TABLE users ADD email_verified TINYINT NOT NULL DEFAULT 1');
if($tab('users'))$pdo->exec('CREATE TABLE IF NOT EXISTS email_codes(user_id INT NOT NULL PRIMARY KEY, code_hash CHAR(64) NOT NULL, sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)');
// version 37: new accounts wait for approval (a teacher approves students, the admin approves students and teachers).
// Accounts that already exist count as approved (DEFAULT 1); register.php makes new ones with 0
if($tab('users')&&!$col('users','approved'))$pdo->exec('ALTER TABLE users ADD approved TINYINT NOT NULL DEFAULT 1, ADD approved_by INT NULL, ADD approved_at DATETIME NULL');
if($tab('users')){$ro=$col('users','role');$rt=(string)($ro['Type']??'');if($ro&&stripos($rt,'enum')!==false&&stripos($rt,'admin')===false)$pdo->exec("ALTER TABLE users MODIFY role ENUM('student','teacher','admin') NOT NULL DEFAULT 'student'");}
if($tab('lessons')){$b=$col('lessons','body');if($b&&$b['Null']==='NO')$pdo->exec('ALTER TABLE lessons MODIFY body TEXT NULL');}
// the current school year (settings), every time the teacher moved the students up (promotions) and what happened to each student (year_log)
$pdo->exec('CREATE TABLE IF NOT EXISTS settings(k VARCHAR(30) PRIMARY KEY, v VARCHAR(100) NOT NULL)');
$pdo->exec("CREATE TABLE IF NOT EXISTS promotions(id INT AUTO_INCREMENT PRIMARY KEY, from_year VARCHAR(9) NOT NULL, to_year VARCHAR(9) NOT NULL, graduate TINYINT NOT NULL DEFAULT 1, closed_quizzes TEXT NULL, closed_exams TEXT NULL, undone TINYINT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec('CREATE TABLE IF NOT EXISTS year_log(id INT AUTO_INCREMENT PRIMARY KEY, promotion_id INT NOT NULL, user_id INT NOT NULL, old_year TINYINT NULL, new_year TINYINT NULL, INDEX idx_promo(promotion_id))');
// counts wrong passwords (login lock)
$pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts(id INT AUTO_INCREMENT PRIMARY KEY, kind VARCHAR(10) NOT NULL, who VARCHAR(100) NOT NULL, tried_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_who(kind,who,tried_at))');
if($tab('users')){
$pdo->exec('CREATE TABLE IF NOT EXISTS saved_work(user_id INT NOT NULL, slot VARCHAR(30) NOT NULL, code MEDIUMTEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY(user_id,slot), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)');
$pdo->exec('CREATE TABLE IF NOT EXISTS saved_files(id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, name VARCHAR(80) NOT NULL, language VARCHAR(20) NOT NULL, code MEDIUMTEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_file(user_id,name), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)');
$fresh=!$tab('notifications');
$pdo->exec("CREATE TABLE IF NOT EXISTS notifications(id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, kind VARCHAR(20) NOT NULL, message VARCHAR(255) NOT NULL, link VARCHAR(120) NOT NULL DEFAULT '', shown TINYINT NOT NULL DEFAULT 0, read_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)");
if($fresh){ // tell students about quizzes and exams that were set before notifications existed
if($tab('quizzes')&&$tab('attempts'))$pdo->exec("INSERT INTO notifications(user_id,kind,message,link) SELECT u.id,'quiz',CONCAT('New quiz: ',q.title),CONCAT('Quiz/quiz.php?id=',q.id) FROM users u CROSS JOIN quizzes q WHERE u.role='student' AND NOT EXISTS(SELECT 1 FROM attempts a WHERE a.quiz_id=q.id AND a.student_id=u.id AND a.submitted_at IS NOT NULL)");
if($tab('coding_exams')&&$tab('exam_attempts'))$pdo->exec("INSERT INTO notifications(user_id,kind,message,link) SELECT u.id,'exam',CONCAT('New code exam: ',x.title),'Exam/exams.php' FROM users u CROSS JOIN coding_exams x WHERE u.role='student' AND NOT EXISTS(SELECT 1 FROM exam_attempts a WHERE a.exam_id=x.id AND a.student_id=u.id AND a.submitted_at IS NOT NULL)");}
$pdo->exec('DROP TABLE IF EXISTS enrolled_ids'); // version 36 had an enrollment list; teacher approval (version 37) replaced it
$pdo->exec('CREATE TABLE IF NOT EXISTS ai_usage(user_id INT NOT NULL, used_on DATE NOT NULL, n INT NOT NULL DEFAULT 0, PRIMARY KEY(user_id,used_on), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)');
if($tab('attempts')&&$tab('questions'))$pdo->exec('CREATE TABLE IF NOT EXISTS quiz_answers(attempt_id INT NOT NULL, question_id INT NOT NULL, choice CHAR(1) NOT NULL, PRIMARY KEY(attempt_id,question_id), FOREIGN KEY(attempt_id) REFERENCES attempts(id) ON DELETE CASCADE, FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE)');
}
if($tab('modules')){try{$pdo->exec("DELETE FROM modules WHERE level='Quick start'");}catch(Throwable $e){}}
// version 23 moved the pages into folders: update the links saved in old notifications
if($tab('notifications')){
$pdo->exec("UPDATE notifications SET link=CONCAT('Quiz/',link) WHERE link LIKE 'quiz.php%'");
$pdo->exec("UPDATE notifications SET link='Exam/exams.php' WHERE link='exams.php'");
$pdo->exec("UPDATE notifications SET link='Grades/grades.php' WHERE link='grades.php'");}
// version 41: CLASSES (subjects). A teacher makes a class and gives its join code; students join it (irregular students simply join
// the classes of the subjects they take). Every quiz and code exam belongs to one class and has a start time and a deadline.
if($tab('users')){
$pdo->exec('CREATE TABLE IF NOT EXISTS classes(id INT AUTO_INCREMENT PRIMARY KEY, teacher_id INT NOT NULL, name VARCHAR(100) NOT NULL, section VARCHAR(40) NOT NULL DEFAULT \'\', year_level TINYINT NULL, join_code VARCHAR(10) NOT NULL UNIQUE, school_year VARCHAR(9) NULL, archived TINYINT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(teacher_id) REFERENCES users(id) ON DELETE CASCADE)');
$pdo->exec('CREATE TABLE IF NOT EXISTS class_members(class_id INT NOT NULL, student_id INT NOT NULL, joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(class_id,student_id), INDEX idx_member(student_id), FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE CASCADE, FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE)');}
foreach(['quizzes','coding_exams'] as $t)if($tab($t)){
if(!$col($t,'class_id'))$pdo->exec("ALTER TABLE $t ADD class_id INT NULL, ADD INDEX idx_class(class_id)");
if(!$col($t,'opens_at'))$pdo->exec("ALTER TABLE $t ADD opens_at DATETIME NULL, ADD due_at DATETIME NULL");
if($col($t,'closed'))$pdo->exec("UPDATE $t SET due_at=COALESCE(due_at,NOW()),closed=0 WHERE closed=1");} // the old Close button is gone: a closed one gets a deadline that already passed
if($tab('coding_exams')&&!$col('coding_exams','starter'))$pdo->exec('ALTER TABLE coding_exams ADD starter MEDIUMTEXT NULL'); // the first files of a web project exam
// version 43: a picture of the expected output on a code exam (checked and re-saved), and the admin sign-in log
if($tab('coding_exams')&&!$col('coding_exams','attachment'))$pdo->exec('ALTER TABLE coding_exams ADD attachment VARCHAR(80) NULL, ADD attachment_check VARCHAR(20) NULL');
$pdo->exec('CREATE TABLE IF NOT EXISTS admin_log(id INT AUTO_INCREMENT PRIMARY KEY, event VARCHAR(30) NOT NULL, ip VARCHAR(45) NOT NULL, agent VARCHAR(160) NOT NULL DEFAULT \'\', at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_at(at))');
// version 44: the admin email is the group's Gmail (ADMIN_EMAIL), so the step-2 login code can really be emailed
if($tab('users'))try{$pdo->prepare("UPDATE users SET email=? WHERE role='admin' AND email='admin@codesmart.local'")->execute([ADMIN_EMAIL]);}catch(Throwable $x){}
// version 47: remove what is not used any more (each step on its own, so one problem cannot stop the rest)
// - users.extra_years, quizzes.years, coding_exams.years: the old year-level lists. Since version 41 an irregular student simply
//   joins the classes of the subjects he or she takes, and every quiz / code exam belongs to a class.
// - schema_migrations: only Setup/install.php used it, for the old step-by-step SQL files 01 to 13 (now one file, database/capstone_db.sql)
// - old integrity records of BLOCKED actions (copy, paste, right-click, shortcuts): they cannot happen, so they are not shown (version 45)
$clean=[];
if($tab('users')&&$col('users','extra_years'))$clean[]='ALTER TABLE users DROP COLUMN extra_years';
foreach(['quizzes','coding_exams'] as $t)if($tab($t)&&$col($t,'years'))$clean[]="ALTER TABLE $t DROP COLUMN years";
$clean[]='DROP TABLE IF EXISTS schema_migrations';
foreach(['cheat_events','exam_events'] as $t)if($tab($t))$clean[]="DELETE FROM $t WHERE event_type NOT IN ('tab_switch','fullscreen_exit')";
// - sign-ups whose email code was never typed (since version 47 an account is made only after the code, so these are left over)
if($tab('users')&&$col('users','email_verified'))$clean[]="DELETE FROM users WHERE email_verified=0 AND role<>'admin'";
foreach($clean as $q)try{$pdo->exec($q);}catch(Throwable $e){error_log('CodeSmartAi-X clean-up: '.$q.': '.$e->getMessage());}
if(defined('ADMIN_ID')&&$tab('users')&&$col('users','approved'))ensure_admin($pdo); // Setup/install.php does not load config.php: the first page that opens makes the admin
if($htmlErr)throw $htmlErr;
}
