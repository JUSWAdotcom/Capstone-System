// CodeSmartAi-X code editor in the style of Visual Studio Code (version 41).
// Files on the left (EXPLORER), tabs, line numbers, colours, Run, OUTPUT and PREVIEW on the right.
// Used by the code exam (students), the full screen viewer (teachers) and the Playground (version 42).
// PLAYGROUND MODE (lang:'auto'): every file is run by its own type (main.py with Python, page.html in PREVIEW...).
// The files live on the server: the page passes hooks (onAdd, onSave, onDelete, onRename). A file is only made when the
// student presses + (New file), and typing saves into that same file, so the database is not filled with copies.
//   IDE.mount({lang, files, readOnly, store, add, T})   lang: python, javascript, cpp, csharp, php, java, html or web
//   IDE.payload()  what is saved: the code (one file) or {"files":{...}} for a web project
//   IDE.output()   the text in OUTPUT
//   IDE.run()
// A WEB PROJECT has several files (index.html, style.css, script.js and PHP files for the back end).
// The preview puts the CSS and JavaScript into the page. Links and forms that go to another file of the project
// (for example <form action="process.php" method="post">) open that file: PHP files run on the server with the form data.
window.IDE=(()=>{
const $=i=>document.getElementById(i);
const ESC=s=>String(s).replace(/[&<>]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
const MAIN={python:'main.py',javascript:'main.js',cpp:'main.cpp',csharp:'Program.cs',php:'index.php',java:'Main.java',html:'index.html'};
const START={python:'# Write your Python code here\nprint("Hello, IT student!")\n',javascript:'// Write your JavaScript code here\nconsole.log("Hello, IT student!");\n',
cpp:'#include <iostream>\nusing namespace std;\n\nint main() {\n    cout << "Hello, IT student!" << endl;\n    return 0;\n}\n',csharp:'Console.WriteLine("Hello, IT student!");\n',
php:'<?php\necho "Hello, IT student!" . PHP_EOL;\n',java:'public class Main {\n    public static void main(String[] args) {\n        System.out.println("Hello, IT student!");\n    }\n}\n',
html:'<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <title>My page</title>\n  <style>\n    body { font-family: Arial, sans-serif; }\n  </style>\n</head>\n<body>\n  <h1>Hello!</h1>\n</body>\n</html>\n'};
const SERVER=['php','cpp','csharp','java'];
const kind=n=>({py:'python',js:'javascript',cpp:'cpp',cs:'csharp',php:'php',java:'java',html:'html',htm:'html',css:'css'})[(n.split('.').pop()||'').toLowerCase()]||'text';
const ICON={python:'PY',javascript:'JS',cpp:'C++',csharp:'C#',php:'PHP',java:'J',html:'<>',css:'#',text:'TXT'};
// ---------- colours (like VS Code Dark+) ----------
const KW={python:'and|as|assert|break|class|continue|def|del|elif|else|except|False|finally|for|from|global|if|import|in|is|lambda|None|nonlocal|not|or|pass|raise|return|True|try|while|with|yield|print|input|len|range|int|str|float|self',
javascript:'async|await|break|case|catch|class|const|continue|default|do|else|export|false|finally|for|function|if|import|in|instanceof|let|new|null|of|return|switch|this|throw|true|try|typeof|undefined|var|while|console|document|window',
cpp:'auto|bool|break|case|catch|char|class|const|continue|default|delete|do|double|else|false|float|for|if|include|int|long|namespace|new|nullptr|private|protected|public|return|short|static|string|struct|switch|this|throw|true|try|using|void|while|std|cout|cin|endl|vector|main',
csharp:'bool|break|case|catch|char|class|const|continue|decimal|default|do|double|else|false|float|for|foreach|if|in|int|long|namespace|new|null|private|protected|public|return|static|string|switch|this|throw|true|try|using|var|void|while|Console|WriteLine|ReadLine',
java:'boolean|break|case|catch|char|class|const|continue|default|do|double|else|extends|false|final|float|for|if|implements|import|int|interface|long|new|null|package|private|protected|public|return|short|static|String|super|switch|this|throw|throws|true|try|void|while|System|out|println|Scanner',
php:'array|as|break|case|catch|class|const|continue|default|do|echo|else|elseif|empty|false|for|foreach|function|if|include|isset|new|null|print|private|public|require|return|static|switch|true|try|unset|while|htmlspecialchars|PHP_EOL'};
const RX={};
const rx=k=>{if(RX[k])return RX[k];let r;
if(k==='html')r=/(<!--[\s\S]*?-->)|("[^"\n]*"|'[^'\n]*')|(<\/?[A-Za-z][\w-]*|\/?>)|(\s[A-Za-z_:][\w:.-]*)(?==)/g;
else if(k==='css')r=/(\/\*[\s\S]*?\*\/)|("[^"\n]*"|'[^'\n]*')|(#[0-9a-fA-F]{3,8}\b|-?\d*\.?\d+(?:px|em|rem|%|vh|vw|s|ms|deg)?\b)|([a-z-]+)(?=\s*:)/g;
else{const c=k==='python'?'#[^\n]*':(k==='php'?'\\/\\/[^\\n]*|#[^\\n]*|\\/\\*[\\s\\S]*?\\*\\/':'\\/\\/[^\\n]*|\\/\\*[\\s\\S]*?\\*\\/');
r=new RegExp('('+c+')|("(?:[^"\\\\\\n]|\\\\.)*"|\'(?:[^\'\\\\\\n]|\\\\.)*\''+(k==='javascript'?'|`[^`]*`':'')+')|(\\b\\d+(?:\\.\\d+)?\\b)|\\b('+(KW[k]||'if|else|for|while|return')+')\\b'+(k==='php'?'|(\\$[A-Za-z_]\\w*)':''),'g')}
return RX[k]=r};
const paint=(code,k)=>{if(k==='text')return ESC(code);const r=rx(k);r.lastIndex=0;let o='',i=0,m;
while((m=r.exec(code))){if(m[0]===''){r.lastIndex++;continue}o+=ESC(code.slice(i,m.index));const t=ESC(m[0]);
if(k==='html')o+=m[1]?'<i class="c">'+t+'</i>':m[2]?'<i class="s">'+t+'</i>':m[3]?'<i class="t">'+t+'</i>':'<i class="a">'+t+'</i>';
else if(k==='css')o+=m[1]?'<i class="c">'+t+'</i>':m[2]?'<i class="s">'+t+'</i>':m[3]?'<i class="n">'+t+'</i>':'<i class="a">'+t+'</i>';
else o+=m[1]?'<i class="c">'+t+'</i>':m[2]?'<i class="s">'+t+'</i>':m[3]?'<i class="n">'+t+'</i>':m[4]?'<i class="k">'+t+'</i>':'<i class="v">'+t+'</i>';
i=m.index+m[0].length}return o+ESC(code.slice(i))};
// ---------- state ----------
let F={},cur='',lang='python',ro=false,store='',T='',add=false,buf='',w=null,timer=0,page='',saveT=0,O={},tabsAll=true,opened=[];
let ta,hl,ln,out,web,tabs,list,pos,note;
const names=()=>Object.keys(F);
const isWeb=()=>lang==='web'||lang==='html'||lang==='auto';
const saveLocal=()=>{if(O.onSave&&cur){clearTimeout(saveT);const n=cur,v=F[cur];note.textContent='Saving...';saveT=setTimeout(()=>O.onSave(n,v).then(t=>note.textContent=t||'Saved').catch(()=>note.textContent='Not saved: check your connection'),900);return}if(!store||ro)return;clearTimeout(saveT);saveT=setTimeout(()=>{try{localStorage[store]=JSON.stringify(F);note.textContent='Saved on this computer'}catch(e){}},400)};
const draw=()=>{const k=kind(cur);hl.innerHTML=paint(ta.value,k)+'\n';const n=ta.value.split('\n').length;if(ln.dataset.n!=n){ln.dataset.n=n;ln.textContent=Array.from({length:n},(_,i)=>i+1).join('\n')}sync()};
const sync=()=>{hl.style.transform='translate('+(-ta.scrollLeft)+'px,'+(-ta.scrollTop)+'px)';ln.style.transform='translateY('+(-ta.scrollTop)+'px)'};
const where=()=>{const b=ta.value.slice(0,ta.selectionStart).split('\n');pos.textContent='Ln '+b.length+', Col '+(b[b.length-1].length+1)};
const fileRow=n=>'<li class="'+(n===cur?'on':'')+'" data-f="'+ESC(n)+'"'+(O.onRename?' title="Double-click to rename"':'')+'><span class="fi fi-'+kind(n)+'">'+ICON[kind(n)]+'</span><span class="fn">'+ESC(n)+'</span>'+(O.onRename&&!ro?'<button type="button" class="fx" data-r="'+ESC(n)+'" title="Rename file" aria-label="Rename '+ESC(n)+'">✎</button>':'')+(add&&!ro&&(names().length>1||O.onDelete)?'<button type="button" class="fx" data-x="'+ESC(n)+'" title="Delete file" aria-label="Delete '+ESC(n)+'">×</button>':'')+'</li>';
const showFiles=()=>{const key=names().join('|');if(list.dataset.k!==key){list.dataset.k=key;list.innerHTML=names().map(fileRow).join('')}else list.querySelectorAll('li').forEach(li=>li.classList.toggle('on',li.dataset.f===cur));const tl=tabsAll?names():opened.filter(n=>n in F);tabs.innerHTML=tl.map(n=>'<button type="button" class="'+(n===cur?'on':'')+'" data-f="'+ESC(n)+'"><span class="fi fi-'+kind(n)+'">'+ICON[kind(n)]+'</span>'+ESC(n)+(tabsAll?'':'<span class="tx" data-close="'+ESC(n)+'" title="Close">×</span>')+'</button>').join('');$('ide-lang').textContent=cur?{python:'Python',javascript:'JavaScript',cpp:'C++',csharp:'C#',php:'PHP',java:'Java',html:'HTML',css:'CSS',text:'Text'}[kind(cur)]:'';empty()};
// no file open (Playground with no files yet): the editor is locked and says what to do
const empty=()=>{const none=!cur;ta.readOnly=ro||none;ta.placeholder=none?(names().length?'Open a file from EXPLORER on the left.':'Create a file first: press + next to EXPLORER (for example hello.py, index.html, style.css).'):'';if(none){ta.value='';draw()}};
const open=n=>{if(!(n in F))return;if(cur&&cur in F)F[cur]=ta.value;cur=n;if(!opened.includes(n))opened.push(n);if(O.onOpen)O.onOpen(n);ta.value=F[n];ta.scrollTop=ta.scrollLeft=0;showFiles();draw();where();if(!ro)ta.focus()};
const panel=p=>{document.querySelectorAll('.ide-ptabs [data-p]').forEach(b=>b.classList.toggle('on',b.dataset.p===p));out.hidden=p!=='out';web.hidden=p!=='web'};
// ---------- typing helpers (Tab = 4 spaces, Enter keeps the indent, Ctrl+Enter = Run) ----------
const ins=t=>{ta.focus();if(!document.execCommand('insertText',false,t)){const s=ta.selectionStart;ta.setRangeText(t,s,ta.selectionEnd,'end');ta.dispatchEvent(new Event('input'))}};
const keys=e=>{if(e.key==='Enter'&&(e.ctrlKey||e.metaKey)){e.preventDefault();run();return}if(ro)return;
if(e.key==='Tab'){e.preventDefault();ins('    ');return}
if(e.key==='Enter'){const b=ta.value.slice(0,ta.selectionStart),line=b.slice(b.lastIndexOf('\n')+1),ind=(line.match(/^[ \t]*/)||[''])[0];const more=/[{:(\[]\s*$/.test(line)?'    ':'';e.preventDefault();ins('\n'+ind+more)}};
// ---------- running ----------
const WSRC=`let py;onmessage=async e=>{const{lang,code}=e.data,out=s=>postMessage({o:s});try{if(lang==='python'){if(!py){try{importScripts('https://cdn.jsdelivr.net/pyodide/v0.26.4/full/pyodide.js');py=await loadPyodide()}catch(x){py=null;const m=new Error('Python could not start. Python runs inside the browser and downloads itself (about 10 MB) the first time, so this computer needs the internet for the first Python run. Check the internet connection, then press Run again.');m.py=1;throw m}}py.setStdout({batched:out});py.setStderr({batched:out});await py.runPythonAsync(code)}else{console.log=console.error=console.warn=(...a)=>out(a.map(x=>typeof x==='object'?JSON.stringify(x):String(x)).join(' '));const r=(0,eval)(code);if(r&&r.then)await r}}catch(err){out(err.py?err.message:'Error: '+err.message)}postMessage({done:1})};`;
const say=t=>{out.textContent=t};
const runWorker=(l,code)=>{buf='';say(l==='python'?'Running... (Python loads on the first run)':'Running...');if(!w){w=new Worker(URL.createObjectURL(new Blob([WSRC])));w.onmessage=e=>{if(e.data.o!==undefined){buf+=e.data.o+'\n';say(buf)}if(e.data.done){clearTimeout(timer);say(buf||'(no output)');if(O.onResult)O.onResult(buf)}}}
clearTimeout(timer);timer=setTimeout(()=>{if(w){w.terminate();w=null}say(buf+'Stopped: the code ran for over 20 seconds (endless loop?).')},20000);w.postMessage({lang:l,code})};
const post=o=>fetch('Playground/run_code.php',{method:'POST',body:new URLSearchParams({t:T,...o}),credentials:'same-origin'}).then(r=>r.json());
const runServer=(l,code)=>{buf='';say('Compiling and running on the server...');post({lang:l,code}).then(j=>{buf=j.out;say(buf);if(O.onResult)O.onResult(buf)}).catch(()=>say('Could not reach the server.'))};
// web project: put style.css / script.js into the page and catch links and forms that go to other files of the project
const NAV='<script>(function(){var P=function(o){o.ide=1;parent.postMessage(o,"*")};document.addEventListener("submit",function(e){var f=e.target,a=(f.getAttribute("action")||"").trim();if(/^[a-z]+:/i.test(a))return;e.preventDefault();var d={};try{new FormData(f).forEach(function(v,k){if(typeof v==="string")d[k]=v})}catch(x){}if(e.submitter&&e.submitter.name)d[e.submitter.name]=e.submitter.value||"";P({file:a.split("?")[0],method:(f.getAttribute("method")||"get").toLowerCase(),data:d})},true);document.addEventListener("click",function(e){var a=e.target.closest?e.target.closest("a[href]"):null;if(!a)return;var h=a.getAttribute("href");if(!h||h.charAt(0)==="#"||/^[a-z]+:/i.test(h))return;e.preventDefault();var p=h.split("?"),q={};if(p[1])p[1].split("&").forEach(function(kv){var s=kv.split("=");if(s[0])q[decodeURIComponent(s[0])]=decodeURIComponent((s[1]||"").replace(/\\+/g," "))});P({file:p[0],method:"get",data:q})},true)})();<\/script>';
const clean=f=>String(f||'').replace(/^\.\//,'').split('#')[0];
const inline=html=>{html=html.replace(/<link\b[^>]*>/gi,m=>{const h=(m.match(/href\s*=\s*["']([^"']+)["']/i)||[])[1];return h&&/stylesheet/i.test(m)&&clean(h) in F?'<style>\n'+F[clean(h)]+'\n</style>':m});
html=html.replace(/<script\b([^>]*?)\ssrc\s*=\s*["']([^"']+)["']([^>]*)>\s*<\/script>/gi,(m,a,h,b)=>clean(h) in F?'<script'+a+b+'>\n'+F[clean(h)].replace(/<\/script/gi,'<\\/script')+'\n<\/script>':m);
return /<head[^>]*>/i.test(html)?html.replace(/<head[^>]*>/i,m=>m+NAV):NAV+html};
const show=(html,f)=>{page=f;web.srcdoc=inline(html);panel('web')};
const go=(f,method,data)=>{f=clean(f)||page||'index.html';if(!(f in F)){web.srcdoc='<p style="font:16px sans-serif;padding:1rem">File not found in the project: <b>'+ESC(f)+'</b></p>';return}
if(kind(f)!=='php'){say('Showing '+f);show(F[f],f);return}
say('Running '+f+' on the server ('+method.toUpperCase()+')...');const wf={};for(const n of names())if(['html','css','javascript','php'].includes(kind(n)))wf[n]=F[n];post({lang:'web',files:JSON.stringify(wf),entry:f,method,data:JSON.stringify(data||{})}).then(j=>{buf=j.out;say('Ran '+f+' on the server ('+method.toUpperCase()+').'+(j.err?'\n'+j.err:''));if(j.err)panel('out');else if(lang==='auto'&&!/<[a-z!][^>]*>/i.test(j.out)){say(j.out||'(no output)');panel('out')}else show(j.out,f)}).catch(()=>say('Could not reach the server.'))};
window.addEventListener('message',e=>{if(e.source!==web.contentWindow||!e.data||!e.data.ide)return;go(e.data.file,e.data.method||'get',e.data.data)});
const entry=()=>['index.php','index.html'].find(n=>n in F)||names().find(n=>kind(n)==='html')||names().find(n=>kind(n)==='php');
const run=()=>{if(!cur){say('Create or open a file first.');panel('out');return}F[cur]=ta.value;
if(lang==='auto'){const k=kind(cur);if(k==='html'||k==='php'){go(cur,'get',{});return}if(k==='css'){const e=names().find(n=>kind(n)==='html'&&new RegExp('href=["\']'+cur.replace(/[.]/g,'\\.')+'["\']').test(F[n]))||entry();if(e){go(e,'get',{});return}say('Link this CSS file from an HTML file (<link rel="stylesheet" href="'+cur+'">), then run the HTML file.');panel('out');return}
if(k==='text'){say('This file type cannot run. Rename it to end in .py, .js, .html, .php, .cpp, .cs or .java.');panel('out');return}panel('out');(SERVER.includes(k)?runServer:runWorker)(k,ta.value);return}
if(lang==='web'){const e=entry();if(!e){say('Add an index.html (or index.php) file first.');panel('out');return}go(e,'get',{});return}
if(lang==='html'){say('Preview updated.');show(ta.value,cur);if(O.onResult)O.onResult(ta.value);return}panel('out');(SERVER.includes(lang)?runServer:runWorker)(lang,ta.value)};
// ---------- files ----------
// ---------- dialogs (version 47): our own boxes instead of the browser's "localhost says" boxes ----------
const LANGS=[['python','Python','py','main.py'],['javascript','JavaScript','js','script.js'],['html','HTML','html','index.html'],['css','CSS','css','style.css'],
['php','PHP','php','index.php'],['cpp','C++','cpp','main.cpp'],['csharp','C#','cs','Program.cs'],['java','Java','java','Main.java']];
const ABOUT={python:'Python file: runs in your browser.',javascript:'JavaScript file: runs in your browser (or inside your HTML page).',html:'HTML page: shows in PREVIEW and can use your .css and .js files.',
css:'CSS file: link it from an HTML page to see the design.',php:'PHP file: runs on the server, the page shows in PREVIEW.',cpp:'C++ file: compiled and run on the server.',
csharp:'C# file: compiled and run on the server.',java:'Java file: runs the class named Main (public class Main).'};
// ask({title, text (HTML, names escaped), input (start value), langs (chips), ok, cancel, danger, taken(name), check(value) -> '' or a message (or a Promise of it)})
// resolves with the typed value (or true when there is no input box), or null when cancelled
const ask=o=>new Promise(done=>{const back=document.activeElement,bg=document.createElement('div');bg.className='idlg-bg';
const L=o.langs?LANGS.filter(l=>o.langs.includes(l[0])):null,hasIn=o.input!==undefined;
bg.innerHTML='<form class="idlg" role="dialog" aria-modal="true" aria-labelledby="idlg-t" novalidate><h3 id="idlg-t"></h3>'+(o.text?'<p class="idlg-p"></p>':'')
+(L?'<div class="idlg-langs" role="group" aria-label="Language">'+L.map(l=>'<button type="button" data-k="'+l[0]+'" aria-pressed="false"><span class="fi fi-'+l[0]+'">'+ESC(ICON[l[0]])+'</span><b>'+l[1]+'</b><small>.'+l[2]+'</small></button>').join('')+'</div>':'')
+(hasIn?'<label class="idlg-l">'+ESC(o.label||'File name')+'<input class="idlg-in" autocomplete="off" spellcheck="false" maxlength="60" aria-describedby="idlg-info"></label><div class="idlg-info" id="idlg-info" aria-live="polite"></div>':'')
+'<p class="idlg-err" role="alert"></p><div class="idlg-btns">'+(o.cancel===false?'':'<button type="button" class="idlg-no">'+ESC(o.cancel||'Cancel')+'</button>')
+'<button type="submit" class="idlg-ok'+(o.danger?' danger':'')+'">'+ESC(o.ok||'OK')+'</button></div></form>';
const f=bg.querySelector('form'),inp=bg.querySelector('.idlg-in'),info=bg.querySelector('.idlg-info'),err=bg.querySelector('.idlg-err'),okb=bg.querySelector('.idlg-ok'),nob=bg.querySelector('.idlg-no');
bg.querySelector('h3').textContent=o.title;if(o.text)bg.querySelector('.idlg-p').innerHTML=o.text;
const ext=v=>(v.match(/\.([A-Za-z0-9]+)$/)||[])[1]||'';
const upd=()=>{const v=inp.value.trim(),k=kind(v||'x'),known=!!ext(v)&&k!=='text';
info.innerHTML=known?'<span class="fi fi-'+k+'">'+ESC(ICON[k])+'</span><span>'+ESC(ABOUT[k]||'')+'</span>':'<span>'+(L?'Choose a language above, or type':'Type')+' a name that ends with the file type, for example <code>'+(L?ESC(L[0][3]):'page.html')+'</code>.</span>';
if(L)bg.querySelectorAll('.idlg-langs button').forEach(b=>{const on=known&&b.dataset.k===k;b.classList.toggle('on',on);b.setAttribute('aria-pressed',on?'true':'false')});err.textContent=''};
const key=e=>{if(e.key==='Escape'){e.preventDefault();e.stopPropagation();close(null)}else if(e.key==='Tab'){const z=[...f.querySelectorAll('button,input')].filter(x=>!x.disabled);const i=z.indexOf(document.activeElement);
if(e.shiftKey&&i<=0){e.preventDefault();z[z.length-1].focus()}else if(!e.shiftKey&&i===z.length-1){e.preventDefault();z[0].focus()}}};
const close=v=>{document.removeEventListener('keydown',key,true);bg.remove();if(back&&back.focus)try{back.focus()}catch(x){}done(v)};
document.addEventListener('keydown',key,true);
if(hasIn){inp.value=o.input||'';inp.addEventListener('input',upd);upd()}
if(L)bg.querySelector('.idlg-langs').addEventListener('click',e=>{const b=e.target.closest('button[data-k]');if(!b)return;const l=LANGS.find(x=>x[0]===b.dataset.k),v=inp.value.trim();
let base=v.replace(/\.[A-Za-z0-9]*$/,'').replace(/[^A-Za-z0-9_-]+/g,'-').replace(/^[^A-Za-z0-9]+/,'');const typed=!!base;if(!typed)base=l[3].replace(/\.[^.]+$/,'');
let n=base+'.'+l[2];if(!typed&&o.taken)for(let i=2;o.taken(n)&&i<99;i++)n=base+i+'.'+l[2];
inp.value=n;upd();inp.focus();inp.setSelectionRange(0,n.length-l[2].length-1)});
if(nob)nob.onclick=()=>close(null);
bg.addEventListener('mousedown',e=>{if(e.target===bg)close(null)});
f.onsubmit=e=>{e.preventDefault();if(okb.disabled)return;const v=hasIn?inp.value.trim():true;okb.disabled=true;
Promise.resolve(o.check?o.check(v):'').then(m=>{okb.disabled=false;if(m){err.textContent=m;if(hasIn){inp.focus();inp.select()}return}close(v)})};
document.body.appendChild(bg);
if(hasIn){inp.focus();const dot=inp.value.lastIndexOf('.');if(inp.value)inp.setSelectionRange(0,dot>0?dot:inp.value.length)}else(o.danger&&nob?nob:okb).focus()});
const tell=(title,text)=>ask({title,text:ESC(text),cancel:false,ok:'OK'});
const FILE_RE=/^[A-Za-z0-9][A-Za-z0-9_-]{0,50}\.(py|js|html|css|php|cpp|cs|java)$/,WEB_RE=/^[A-Za-z0-9][A-Za-z0-9_-]{0,39}\.(html|css|js|php)$/;
const BAD_NAME='Use letters, numbers, - and _ (no spaces), then the file type. For example: hello.py, index.html, Main.java.';
const addFile=()=>{const own=!!O.onAdd;let made=null;
ask({title:'New file',text:own?'Pick a language, then give your file a name.':'Add a file to this web project.',langs:own?LANGS.map(l=>l[0]):['html','css','javascript','php'],input:'',ok:'Create file',taken:n=>n in F,
check:n=>{if(!n)return 'Type a file name, or pick a language above.';
if(!(own?FILE_RE:WEB_RE).test(n))return own?BAD_NAME:'Use a name like page2.html, style.css, app.js or process.php (letters, numbers, - and _ only).';
if(n in F)return 'You already have a file named '+n+'.';if(own)return O.onAdd(n).then(r=>{if(r&&r.err)return r.err;made=r||{};return ''}).catch(()=>'Could not reach the server. Please try again.');
if(names().length>=12)return 'A project can have up to 12 files.';return ''}}).then(n=>{if(!n)return;
if(own){if(cur)F[cur]=ta.value;F[n]=made.code||'';open(n);return}
F[cur]=ta.value;F[n]=kind(n)==='php'?'<?php\n\n':(kind(n)==='html'?'<!DOCTYPE html>\n<html>\n<head>\n  <meta charset="utf-8">\n  <link rel="stylesheet" href="style.css">\n</head>\n<body>\n\n</body>\n</html>\n':'');open(n);saveLocal()})};
const drop=n=>{delete F[n];opened=opened.filter(x=>x!==n);if(cur===n){cur='';const nx=opened.find(x=>x in F)||(tabsAll?names()[0]:'');if(nx)open(nx);else{showFiles();draw()}}else showFiles()};
const delFile=n=>{const own=!!O.onDelete;if(!own&&names().length<2)return tell('Cannot delete','A project needs at least one file.');
ask({title:'Delete file?',text:'Delete <b>'+ESC(n)+'</b>?'+(own?' This cannot be undone.':''),ok:'Delete',danger:true,
check:()=>own?O.onDelete(n).then(r=>r&&r.err?r.err:'').catch(()=>'Could not reach the server. Please try again.'):''}).then(v=>{if(v===null)return;drop(n);if(!own)saveLocal()})};
const renFile=n=>{if(!O.onRename||ro)return;
ask({title:'Rename file',text:'New name for <b>'+ESC(n)+'</b>:',input:n,ok:'Rename',
check:m=>{if(!m)return 'Type a new name.';if(m===n)return '';if(!FILE_RE.test(m))return BAD_NAME;if(m in F)return 'You already have a file named '+m+'.';
return O.onRename(n,m).then(r=>r&&r.err?r.err:'').catch(()=>'Could not reach the server. Please try again.')}}).then(m=>{if(!m||m===n)return;
if(cur===n)F[n]=ta.value;F[m]=F[n];delete F[n];opened=opened.map(x=>x===n?m:x);if(cur===n)cur=m;showFiles();draw()})};
const closeTab=n=>{opened=opened.filter(x=>x!==n);if(cur===n){F[cur]=ta.value;cur='';const nx=opened[opened.length-1];if(nx)open(nx);else{showFiles();draw();where()}}else showFiles()};
// ---------- start ----------
const mount=o=>{O=o;lang=o.lang||'python';ro=!!o.readOnly;store=o.store||'';T=o.T||'';add=!!o.add;tabsAll=o.tabsAll!==false;
ta=$('code');hl=$('ide-hl');ln=$('ide-ln');out=$('ide-out');web=$('ide-web');tabs=$('ide-tabs');list=$('ide-files');pos=$('ide-pos');note=$('ide-note');
let f=o.files&&(Object.keys(o.files).length||o.allowEmpty)?o.files:{[MAIN[lang]||'main.txt']:START[lang]||''};
if(store&&!ro){try{const s=JSON.parse(localStorage[store]||'null');if(s&&typeof s==='object'&&Object.keys(s).length){f=s;note.textContent='Your work on this computer was loaded'}}catch(e){}}
F=Object.assign({},f);ta.readOnly=ro;
ta.addEventListener('input',()=>{if(!cur)return;F[cur]=ta.value;draw();where();saveLocal()});ta.addEventListener('scroll',sync);ta.addEventListener('keydown',keys);
['click','keyup'].forEach(t=>ta.addEventListener(t,where));
list.addEventListener('click',e=>{const x=e.target.closest('[data-x]');if(x){delFile(x.dataset.x);return}const rn=e.target.closest('[data-r]');if(rn){renFile(rn.dataset.r);return}const r=e.target.closest('[data-f]');if(r)open(r.dataset.f)});
list.addEventListener('dblclick',e=>{const r=e.target.closest('[data-f]');if(r)renFile(r.dataset.f)});
tabs.addEventListener('click',e=>{const c=e.target.closest('[data-close]');if(c){e.stopPropagation();closeTab(c.dataset.close);return}const r=e.target.closest('[data-f]');if(r)open(r.dataset.f)});
const ab=$('ide-add');if(ab){ab.hidden=!add||ro;ab.onclick=addFile}
document.querySelectorAll('.ide-ptabs [data-p]').forEach(b=>b.onclick=()=>panel(b.dataset.p));
const pw=document.querySelector('.ide-ptabs [data-p="web"]');if(pw)pw.hidden=!isWeb();
// OUTPUT / PREVIEW full view (version 47). Before, this button used the browser's full screen: in a quiz or code exam the page is
// ALREADY in full screen, so the button LEFT full screen (the student got "Left the page"). Now the output fills the editor window
// inside the page; the browser's full screen is only asked for outside exams (Playground, challenges), never during one.
const fs=$('ide-full');if(fs&&!fs.dataset.on){fs.dataset.on='1';const box=$('ide'),FULL=fs.innerHTML;let ours=false;
const MIN='<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5"/></svg>';
const exam=()=>document.body.classList.contains('exam-mode');
const setMax=on=>{box.classList.toggle('out-max',on);document.body.classList.toggle('out-max-on',on);fs.classList.toggle('maxon',on);
fs.innerHTML=on?MIN+'<span>Exit full view</span>':FULL;fs.title=on?'Back to the normal size (Esc)':'Show OUTPUT / PREVIEW in full view';fs.setAttribute('aria-label',fs.title);fs.setAttribute('aria-pressed',on?'true':'false');
if(on){if(!exam()&&!document.fullscreenElement&&document.documentElement.requestFullscreen){const r=document.documentElement.requestFullscreen();if(r&&r.then)r.then(()=>{ours=true},()=>{})}}
else if(ours){ours=false;if(document.fullscreenElement&&document.exitFullscreen&&!exam())document.exitFullscreen().catch(()=>{})}};
fs.onclick=()=>setMax(!box.classList.contains('out-max'));
document.addEventListener('fullscreenchange',()=>{if(ours&&!document.fullscreenElement){ours=false;setMax(false)}});
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&box.classList.contains('out-max')&&!document.querySelector('.idlg-bg,.modal')){e.preventDefault();setMax(false)}})}
document.querySelectorAll('[data-ide-run]').forEach(b=>b.onclick=run);
const first=o.openFile&&o.openFile in F?o.openFile:(entry()&&lang==='web'?entry():Object.keys(F)[0]);if(first)open(first);else{showFiles();draw()}panel(lang==='auto'?'out':(isWeb()?'web':'out'));
if(o.output!==undefined){buf=o.output;say(o.output)}else say(lang==='auto'?'Press Run (or Ctrl+Enter) to run the open file. HTML and PHP files show in PREVIEW.\nPython loads on its first run.':(isWeb()?'Press Run to see the page.':'Press Run (or Ctrl+Enter) to run your code.'));
if(o.autorun)run()};
return {ask,tell,mount,run,current:()=>cur,kind,append:t=>{buf+=t;say(buf)},set:c=>{if(!cur)return;ta.value=c;F[cur]=c;draw();where();saveLocal()},add:(n,c)=>{if(cur)F[cur]=ta.value;F[n]=c;open(n)},files:()=>{if(cur)F[cur]=ta.value;return F},payload:()=>{F[cur]=ta.value;return lang==='web'?JSON.stringify({files:F}):F[cur]},output:()=>buf,clearLocal:()=>{try{if(store)delete localStorage[store]}catch(e){}}};
})();
