<?php require __DIR__.'/../config.php';$u=need('any');$m=(int)($_GET['m']??0);$p=max(1,(int)($_GET['l']??1));
function fmt($t,$auto){$t=e($t);$t=preg_replace('/\*\*(.+?)\*\*/s','<strong>$1</strong>',$t);$t=preg_replace('/`([^`\n]+)`/','<code>$1</code>',$t);
if($auto)$t=preg_replace('/\b(variables?|functions?|loops?|arrays?|lists?|strings?|integers?|booleans?|conditions?|classes|class|objects?|parameters?|arguments?|operators?|methods?|recursion|pointers?|references?|constructors?|exceptions?|dictionar(?:y|ies)|vectors?|templates?|inheritance|interfaces?|syntax|algorithms?|index|properties|property|tags?|attributes?|selectors?|elements?|flexbox|grid)\b/i','<strong>$0</strong>',$t);
return preg_replace('/^(Example \d+|Output|Common mistakes?|Practice|Try it|About|Why it matters|Step by step):/m','<span class="lbl">$1</span>',$t);}
if($m){$s=$pdo->prepare('SELECT * FROM modules WHERE id=?');$s->execute([$m]);$mod=$s->fetch();if(!$mod)die('Module not found');
$s=$pdo->prepare('SELECT * FROM lessons WHERE module_id=? ORDER BY position');$s->execute([$m]);$ls=$s->fetchAll();$cur=$ls[$p-1]??($ls[0]??null);
if($cur&&$cur['body']===null){$b=lesson_ai(lname($mod['language']),$mod['level'],$cur['title'],$cur['position']);if($b){$pdo->prepare('UPDATE lessons SET body=? WHERE id=? AND body IS NULL')->execute([$b,$cur['id']]);$cur['body']=$b;}else $cur['body']=offline_lesson($mod['language'],$mod['level'],$cur['title']);}
head($mod['title']);echo '<div class="learn"><nav class="toc">';
foreach($ls as $l)echo '<a href="Modules/learn.php?m='.$m.'&l='.$l['position'].'"'.($cur&&$l['id']==$cur['id']?' class="on"':'').'>'.$l['position'].'. '.e($l['title']).'</a>';
echo '<a href="Modules/learn.php">All modules</a><p class="more">'.more_links($mod['language']).'</p></nav><article class="lesson" data-lang="'.e($mod['language']).'">';
if($cur){echo '<small>Lesson '.$cur['position'].' of '.count($ls).' - '.e($mod['level']).'</small><h2>'.e($cur['title']).'</h2>';
if($cur['body']===null)echo '';
else{$auto=strpos($cur['body'],'**')===false;foreach(explode('```',$cur['body']) as $i=>$t)echo $i%2?(preg_match('/^(python|javascript|js|html|css|cpp|c\+\+|csharp|c#|php|java|text)\s*\n/i',trim($t),$fk)?'<pre data-k="'.e(strtolower($fk[1])).'">':'<pre>').e(preg_replace('/^(python|javascript|js|html|css|cpp|c\+\+|csharp|c#|php|java|text)\s*\n/i','',trim($t))).'</pre>':'<p>'.nl2br(fmt(trim($t),$auto)).'</p>';}
echo '<p><a class="btn alt" href="Playground/playground.php">Try it in the playground</a></p><p>';
if($cur['position']>1)echo '<a class="btn alt" href="Modules/learn.php?m='.$m.'&l='.($cur['position']-1).'">Previous</a> ';
if($cur['position']<count($ls))echo '<a class="btn" href="Modules/learn.php?m='.$m.'&l='.($cur['position']+1).'">Next lesson</a>';echo '</p>';}
echo '</article></div><script src="assets/highlight.js?v=48.1"></script>';}
else{head('Modules');$last='';
foreach($pdo->query("SELECT m.*,(SELECT COUNT(*) FROM lessons WHERE module_id=m.id) n FROM modules m WHERE m.level IN ('Easy','Medium','Hard','Advanced') ORDER BY FIELD(language,'python','javascript','cpp','csharp','php','html'),FIELD(level,'Easy','Medium','Hard','Advanced'),id") as $r){
if($r['language']!==$last){if($last)echo '</div></details>';echo '<details class="lv" open><summary>'.lb($r['language']).'<b>'.e(lname($r['language'])).'</b></summary><p class="more">'.more_links($r['language']).'</p><div class="grid" style="margin-top:.8rem">';$last=$r['language'];}
echo '<a class="card" href="Modules/learn.php?m='.$r['id'].'"><h3>'.e($r['level']).'</h3><small>'.$r['n'].' lessons</small></a>';}
echo '</div></details>';}
foot();
