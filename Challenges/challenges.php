<?php require __DIR__.'/../config.php';$u=need('any');
$langs=['python','javascript','cpp','csharp','php','html'];$L=in_array($_GET['lang']??'',$langs)?$_GET['lang']:'python';
// RESET (version 45): a student who finished EVERY challenge of a language may start that language again from zero.
// The button only shows when all are complete, and nothing is reset before the student confirms.
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['a']??'')==='reset'&&$u['role']==='student'){check();
$R=in_array($_POST['lang']??'',$langs,true)?$_POST['lang']:'';
if($R!==''){$q=$pdo->prepare('SELECT id FROM challenges WHERE language=?');$q->execute([$R]);$ids=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
$q=$pdo->prepare('SELECT COUNT(DISTINCT s.challenge_id) FROM challenge_solves s JOIN challenges c ON c.id=s.challenge_id WHERE s.user_id=? AND c.language=?');$q->execute([$u['id'],$R]);
if($ids&&(int)$q->fetchColumn()>=count($ids)){$in=implode(',',$ids);
$pdo->prepare("DELETE FROM challenge_solves WHERE user_id=? AND challenge_id IN ($in)")->execute([$u['id']]);
$pdo->prepare("DELETE FROM saved_work WHERE user_id=? AND slot IN ('ch".implode("','ch",$ids)."')")->execute([$u['id']]);
flash('Your '.lname($R).' challenges were reset. Every challenge starts again from its original code. Good luck!');}
else flash('You can reset only after you finish every '.lname($R).' challenge.');}
header('Location: '.BASE.'Challenges/challenges.php?lang='.urlencode($R?:$L));exit;}
head('Bug-fix challenges');flash();
$s=$pdo->prepare("SELECT c.id,c.language,c.level,c.title,(SELECT 1 FROM challenge_solves s WHERE s.challenge_id=c.id AND s.user_id=?) done FROM challenges c ORDER BY language,FIELD(level,'Easy','Medium','Hard','Advanced'),id");$s->execute([$u['id']]);
$by=[];foreach($s as $r)$by[$r['language']][$r['level']][]=$r;
function pc($rows){$t=count($rows);return [$t?round(count(array_filter($rows,fn($r)=>$r['done']))/$t*100):0,count(array_filter($rows,fn($r)=>$r['done'])),$t];}
function pb($rows){[$p,$d,$t]=pc($rows);return '<div class="pb"><i style="width:'.$p.'%"></i></div><small>'.$d.' of '.$t.' complete</small>';}
echo '<div class="grid">';
foreach($langs as $l){$all=array_merge(...array_values($by[$l]??[]));[$p]=pc($all);
echo '<a class="card'.($l===$L?' on':'').'" href="Challenges/challenges.php?lang='.$l.'"><h2>'.lb($l).e(lname($l)).($p==100?' <span class="ok">Complete ✔</span>':'').'<span class="pct">'.$p.'%</span></h2>'.pb($all).'</a>';}
echo '</div><p>Green tiles are complete. Open any tile again to redo it, even after finishing.</p><h2>'.e(lname($L)).' challenges</h2>';
$allL=array_merge(...array_values($by[$L]??[]));[$pL,$dL,$tL]=pc($allL);
if($u['role']==='student'&&$tL>0&&$dL>=$tL)echo '<div class="card done-all"><div><b>You finished all '.$tL.' '.e(lname($L)).' challenges.</b><br><small>You can keep your progress as it is, or reset it to practice every challenge again from the start. Your choice.</small></div>'
.'<form method="post" onsubmit="return confirm(\'Reset all '.e(lname($L)).' challenges? Your progress and your saved fixes for '.e(lname($L)).' will be cleared, and every challenge starts again from its original code.\')"><input type="hidden" name="t" value="'.csrf().'"><input type="hidden" name="a" value="reset"><input type="hidden" name="lang" value="'.e($L).'"><button class="alt">Reset '.e(lname($L)).' challenges</button></form></div>';
foreach(['Easy','Medium','Hard','Advanced'] as $lv){$rows=$by[$L][$lv]??[];[$p]=pc($rows);
echo '<details class="lv"'.($lv==='Easy'?' open':'').'><summary><b>'.$lv.'</b>'.($rows&&$p==100?' <span class="ok">Complete ✔</span>':'').'<span class="pct">'.$p.'%</span></summary>'.pb($rows).'<div class="tiles">';
foreach($rows as $i=>$r)echo '<a class="tile'.($r['done']?' done':'').'" href="Playground/playground.php?c='.$r['id'].'" title="'.e($r['title']).($r['done']?' - complete, tap to redo':'').'">'.($i+1).'</a>';
echo '</div></details>';}
foot();
