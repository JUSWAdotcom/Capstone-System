<?php require __DIR__.'/../config.php';$u=need('any');$c=null;
if(isset($_GET['c'])){$s=$pdo->prepare('SELECT * FROM challenges WHERE id=?');$s->execute([(int)$_GET['c']]);$c=$s->fetch();}
$on=AI_ON&&$u['role']==='student';$lock=ai_exam_open($u['id']);
$ai=function()use($lock,$on){echo '<h3>Ask the AI Assistant</h3>';if($lock)echo '<p class="card">🔒 AI help is off while your code exam is open. Submit your exam first.</p>';else echo '<p><small>'.($on?'The AI Assistant answers here.':'The built-in tutor answers here.').' Tick the box to send your code too, then ask "check my code" or "why does my code print nothing?".</small></p>'.chat_box('pc',['Check my code','Why does my code print nothing?','What is a loop?','What is a variable?'],true);};
$aim='AIM={on:'.($on?'true':'false').',name:'.json_encode(AI_SHOWN).',left:'.(int)ai_left($u).',lock:'.($lock?'true':'false').'}';

// ---------- a CHALLENGE (fix the bug), in the editor like Visual Studio Code (version 44) ----------
// The buggy code opens in the editor. Run checks the output (or, for HTML, the page) against the goal; the work saves by itself.
if($c){
$dn=false;$q=$pdo->prepare('SELECT 1 FROM challenge_solves WHERE user_id=? AND challenge_id=?');$q->execute([$u['id'],$c['id']]);$dn=(bool)$q->fetch();
$q=$pdo->prepare("SELECT code FROM saved_work WHERE user_id=? AND slot=?");$q->execute([$u['id'],'ch'.$c['id']]);$sv=$q->fetchColumn();
$nx=null;$q=$pdo->prepare('SELECT id,level FROM challenges WHERE language=? AND id>? ORDER BY id LIMIT 1');$q->execute([$c['language'],$c['id']]);$nx=$q->fetch();
head('Challenge: '.$c['title']);
$html=$c['language']==='html';$exp=trim((string)$c['expected_output']);
echo '<div class="card chead" style="max-width:none"><div>'.lb($c['language']).'<b>'.e(lname($c['language'])).'</b> &middot; Level: <b>'.e($c['level']).'</b>'.($dn?' &nbsp;<span class="st st-done">Complete</span>':'').'<br><small>Goal: '.($html?'fix the HTML / CSS so the page is correct.':'fix the bug so the output is exactly the expected output.').' Your work saves by itself.</small></div><div id="chres"></div></div>';
echo '<div class="card" id="next"'.($dn?'':' hidden').'><b class="ok">✔ Challenge complete!</b> What next?<p><a class="btn" href="'.($nx?'Playground/playground.php?c='.$nx['id']:'Challenges/challenges.php?lang='.e($c['language'])).'">'.($nx?($nx['level']!==$c['level']?'Go to '.e($nx['level']).' level':'Next challenge'):'Back to challenges').'</a> <a class="btn alt" href="Challenges/challenges.php?lang='.e($c['language']).'">All challenges</a> <a class="btn alt" href="Dashboard/dashboard.php">Dashboard</a></p></div>';
$prob='<p><b>'.e($c['title']).'</b></p><p>'.($html?'Fix the HTML / CSS so the page is correct. Press Run to see the page.':'Fix the bug, then press Run. The output must be:').'</p>'.(!$html?'<pre style="margin:0 0 .6rem;padding:.5rem;background:#111;border-radius:6px;white-space:pre-wrap;font-size:.8rem">'.e($exp).'</pre>':'').'<details><summary>Show hint</summary><p>'.e($c['hint']).'</p></details>';
echo ide_html('Challenge: '.$c['title'],$prob,'<button type="button" class="alt" id="ch-reset" title="Start again from the buggy code">Reset code</button><button type="button" class="ide-run" data-ide-run>'.ico('play').'Run</button>',false,'CHALLENGE');
$ai();?>
<script>const CH=<?=json_encode(['id'=>$c['id'],'lang'=>$c['language'],'code'=>$c['buggy_code'],'exp'=>$exp,'saved'=>$sv===false?null:$sv])?>,T=<?=json_encode(csrf())?>,<?=$aim?>;</script>
<script src="assets/ide.js?v=48.1"></script><script src="assets/tutor.js?v=48.1"></script><script src="assets/assistant.js?v=48.1"></script>
<script>(()=>{const MAIN={python:'main.py',javascript:'main.js',cpp:'main.cpp',csharp:'Program.cs',php:'index.php',html:'index.html'},name=MAIN[CH.lang]||'main.txt',res=document.getElementById('chres');
let done=false;const solved=()=>{res.innerHTML='<span class="st st-done">✔ Correct! Challenge solved.</span>';if(!done){done=true;fetch('Challenges/challenge_solve.php',{method:'POST',body:new URLSearchParams({t:T,id:CH.id}),credentials:'same-origin'})}const nx=document.getElementById('next');nx.hidden=false;nx.scrollIntoView({behavior:'smooth',block:'center'})};
const notyet=t=>{res.innerHTML='<span class="st st-missed">✘ Not yet</span> <small>'+t+'</small>'};
const norm=s=>s.replace(/\s+/g,' ').replace(/ ?([<>=:;{},()]) ?/g,'$1').trim().toLowerCase();
const htmlOk=code=>{try{const r=JSON.parse(CH.exp.slice(5)),n=norm(code),has=r.has.map(norm);return has.every(h=>n.includes(h))&&r.not.map(norm).every(b=>!n.includes(b)||has.some(h=>h.includes(b)))}catch(e){return false}};
IDE.mount({lang:CH.lang,files:{[name]:CH.saved??CH.code},T,
onSave:(n,c)=>fetch('Playground/save_work.php',{method:'POST',body:new URLSearchParams({t:T,slot:'ch'+CH.id,code:c}),credentials:'same-origin'}).then(r=>r.json()).then(j=>'Saved '+(j.time||'')),
onResult:r=>{if(CH.lang==='html'){if(CH.exp.startsWith('HTML:')){if(htmlOk(IDE.files()[name]))solved();else notyet('Look at the preview, read the hint, and check the line that looks wrong.')}return}
if(r.replace(/\r/g,'').trim()===CH.exp){IDE.append('\n✔ Correct! Challenge solved.');solved()}else{IDE.append('\n✘ Not yet: the output does not match the goal.');notyet('The output does not match the goal yet.')}}});
document.getElementById('ch-reset').onclick=()=>IDE.ask({title:'Start again?',text:'Your changes are replaced by the original buggy code.',ok:'Start again',danger:true}).then(v=>{if(v){IDE.set(CH.code);res.innerHTML=''}});
if(typeof mkChat==='function'&&!AIM.lock)mkChat('pc',{on:AIM.on,name:AIM.name,left:AIM.left,ctx:()=>({lang:CH.lang,code:IDE.files()[name]||'',output:IDE.output(),include:!!(document.getElementById('pccode')&&document.getElementById('pccode').checked)})});})();</script>
<?php foot();exit;}

// ---------- the PLAYGROUND (version 42): an editor like Visual Studio Code with the student's own files ----------
// A file is made only when the student presses + (New file) or uploads one, and typing saves into that same file.
$ext=['python'=>'py','javascript'=>'js','html'=>'html','css'=>'css','php'=>'php','cpp'=>'cpp','csharp'=>'cs','java'=>'java'];
$ok=fn($n)=>preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,50}\.(py|js|html|css|php|cpp|cs|java)$/',$n);
// files saved by older versions (names like "my loop") get a proper name with the file type, once
$q=$pdo->prepare('SELECT id,name,language FROM saved_files WHERE user_id=?');$q->execute([$u['id']]);$all=$q->fetchAll();$taken=array_column($all,'name');
foreach($all as $r)if(!$ok($r['name'])){$x=$ext[$r['language']]??'';if($x==='')continue;$b=trim(preg_replace('/[^A-Za-z0-9_-]+/','-',pathinfo($r['name'],PATHINFO_FILENAME)),'-_')?:'file';$b=substr(preg_match('/^[A-Za-z0-9]/',$b)?$b:'f'.$b,0,40);
$n=$b.'.'.$x;for($i=2;in_array($n,$taken,true);$i++)$n=$b.'-'.$i.'.'.$x;try{$pdo->prepare('UPDATE saved_files SET name=? WHERE id=?')->execute([$n,$r['id']]);$taken[]=$n;}catch(PDOException $e){}}
// the work of the old playground (one box per language) becomes files, once
$q=$pdo->prepare("SELECT slot,code FROM saved_work WHERE user_id=? AND slot IN ('python','javascript','html','cpp','csharp','php')");$q->execute([$u['id']]);
foreach($q->fetchAll() as $r){if(trim((string)$r['code'])!==''){$n='my-'.$r['slot'].'.'.$ext[$r['slot']];try{$pdo->prepare('INSERT INTO saved_files(user_id,name,language,code) VALUES(?,?,?,?)')->execute([$u['id'],$n,$r['slot'],$r['code']]);}catch(PDOException $e){}}
$pdo->prepare('DELETE FROM saved_work WHERE user_id=? AND slot=?')->execute([$u['id'],$r['slot']]);}
$q=$pdo->prepare('SELECT name,code FROM saved_files WHERE user_id=? ORDER BY name');$q->execute([$u['id']]);$files=[];foreach($q as $r)if($ok($r['name']))$files[$r['name']]=(string)$r['code'];
head('Playground');flash();
$tips='<p>Your own coding space.</p><p><b>1.</b> Press <b>+</b> next to EXPLORER and give the file a name with its type: <code>hello.py</code>, <code>loops.js</code>, <code>index.html</code>, <code>style.css</code>, <code>app.php</code>, <code>main.cpp</code>, <code>Program.cs</code>, <code>Main.java</code>.</p><p><b>2.</b> Type your code. It is <b>saved into that file automatically</b>.</p><p><b>3.</b> Press <b>Run</b> (or Ctrl+Enter). HTML, CSS, JavaScript and PHP files work together like a small website.</p><p><small>Double-click a file to rename it. Upload / Download move a file between here and your computer.</small></p>';
echo '<div class="pg">'.ide_html('Playground',$tips,'<input type="file" id="pg-in" hidden accept=".py,.js,.html,.htm,.css,.php,.cpp,.cs,.java,.txt"><button type="button" class="alt" id="pg-up" title="Open a file from your computer">Upload</button><button type="button" class="alt" id="pg-dl" title="Save the open file to your computer">Download</button><button type="button" class="ide-run" data-ide-run>'.ico('play').'Run</button>',true,'HOW IT WORKS').'</div>';
$ai();?>
<script>const T=<?=json_encode(csrf())?>,FILES=<?=json_encode($files?:new stdClass)?>,<?=$aim?>;</script>
<script src="assets/ide.js?v=48.1"></script><script src="assets/tutor.js?v=48.1"></script><script src="assets/assistant.js?v=48.1"></script>
<script>(()=>{const api=o=>fetch('Playground/files.php',{method:'POST',body:new URLSearchParams({t:T,...o}),credentials:'same-origin'}).then(r=>r.json());
let last=<?=json_encode((string)($_GET['open']??''))?>;try{if(!last)last=localStorage.pg_last||''}catch(e){}
IDE.mount({lang:'auto',files:FILES,allowEmpty:true,tabsAll:false,add:true,T,openFile:last,
onAdd:n=>api({do:'create',name:n}),
onSave:(n,c)=>api({do:'save',name:n,code:c}).then(j=>{if(j.err)throw new Error(j.err);return 'Saved '+j.time}),
onDelete:n=>api({do:'delete',name:n}),
onRename:(a,b)=>api({do:'rename',name:a,to:b}),
onOpen:n=>{try{localStorage.pg_last=n}catch(e){}}});
const fin=document.getElementById('pg-in');document.getElementById('pg-up').onclick=()=>fin.click();
fin.onchange=e=>{const f=e.target.files[0];e.target.value='';if(!f)return;if(f.size>100000)return IDE.tell('File too big','That file is bigger than 100 KB. Please upload a smaller file.');
let n=f.name.replace(/\.htm$/i,'.html').replace(/[^A-Za-z0-9._-]+/g,'-').replace(/^[^A-Za-z0-9]+/,'');if(/\.txt$/i.test(n))n=n.replace(/\.txt$/i,'.py');
f.text().then(t=>{const go=nm=>api({do:'create',name:nm,code:t}).then(j=>{if(!j.err){IDE.add(nm,t);return}let ok='';IDE.ask({title:'Upload file',text:String(j.err).replace(/[&<>]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;'})[c])+' Give it another name:',input:nm,ok:'Upload',check:m=>!m?'Type a file name.':api({do:'create',name:m,code:t}).then(k=>{if(k.err)return k.err;ok=m;return ''})}).then(m=>{if(m&&ok)IDE.add(ok,t)})});go(n)})};
document.getElementById('pg-dl').onclick=()=>{const n=IDE.current();if(!n)return IDE.tell('No file open','Open a file first, then press Download.');const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([IDE.files()[n]],{type:'text/plain'}));a.download=n;a.click();setTimeout(()=>URL.revokeObjectURL(a.href),1000)};
const L={python:'python',javascript:'javascript',html:'html',css:'html',php:'php',cpp:'cpp',csharp:'csharp',java:'java'};
if(typeof mkChat==='function'&&!AIM.lock)mkChat('pc',{on:AIM.on,name:AIM.name,left:AIM.left,ctx:()=>{const n=IDE.current();return {lang:L[IDE.kind(n||'x.py')]||'python',code:n?IDE.files()[n]:'',output:IDE.output(),include:!!(document.getElementById('pccode')&&document.getElementById('pccode').checked)}}});})();</script>
<?php foot();
