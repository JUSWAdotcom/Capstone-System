(()=>{
const $=id=>document.getElementById(id),list=$('qlist'),L=['a','b','c','d'];let n=0;
const esc=s=>String(s==null?'':s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const say=t=>$('pmsg').textContent=t;
function renum(){const rows=[...list.children];rows.forEach((r,i)=>r.querySelector('.qn').textContent='Question '+(i+1));
const miss=rows.filter(r=>!r.querySelector('input[type=radio]:checked')).length;
$('count').textContent=rows.length+' question'+(rows.length===1?'':'s')+(miss?' - '+miss+' still need an answer key':' - every question has an answer key')}
function add(d){d=d||{};const i=n++,r=document.createElement('div');r.className='qrow card';
r.innerHTML='<div class="qmain"><div class="qhead"><b class="qn"></b><button type="button" class="alt rm">Remove</button></div>'
+'<label>Question<textarea name="qs['+i+'][q]" rows="2" required>'+esc(d.q)+'</textarea></label>'
+L.map(x=>'<label class="optrow"><span class="ltr">'+x.toUpperCase()+'</span><input name="qs['+i+']['+x+']" value="'+esc(d[x])+'" required maxlength="255"></label>').join('')+'</div>'
+'<fieldset class="key"><legend>Answer key</legend>'+L.map(x=>'<label class="opt"><input type="radio" name="qs['+i+'][k]" value="'+x.toUpperCase()+'"'+(String(d.k||'').toUpperCase()===x.toUpperCase()?' checked':'')+' required> '+x.toUpperCase()+'</label>').join('')+'<small>Pick the correct answer</small></fieldset>';
r.querySelector('.rm').onclick=()=>{r.remove();renum()};r.addEventListener('change',renum);list.appendChild(r);renum();return r}
// turns pasted text into questions: "1. question", "A. answer" ... "Answer: B" (or a star after the right answer)
function parse(text){const out=[];let cur=null;
const flush=()=>{if(cur&&(cur.q||cur.a||cur.b||cur.c||cur.d))out.push(cur);cur=null};
const done=c=>c&&(c.k||(c.a&&c.b&&c.c&&c.d));
const start=q=>{flush();cur={q:q,a:'',b:'',c:'',d:'',k:'',last:'q'}};
for(const raw of String(text).replace(/\r/g,'').split('\n')){const line=raw.trim();if(!line)continue;let m;
if(cur&&(m=line.match(/^(?:answer|ans|correct(?:\s+answer)?|key)\s*(?:is)?\s*[:=\-]?\s*\(?([A-Da-d])\)?(?![A-Za-z])/i)))cur.k=m[1].toUpperCase();
else if(cur&&(m=line.match(/^\(?([A-Da-d])[.):]\s*(.+)$/))){let t=m[2].trim();const L2=m[1].toLowerCase();
if(/(\s\*|\(correct\)|✓|✔)\s*$/i.test(t)){t=t.replace(/\s*(\*|\(correct\)|✓|✔)\s*$/i,'');cur.k=L2.toUpperCase()}
cur[L2]=t;cur.last=L2}
else if((m=line.match(/^(?:q(?:uestion)?\s*)?\d+\s*[.):\-]\s*(.+)$/i)))start(m[1].trim());
else if(!cur)start(line);
else if(cur.last==='q'&&!cur.a)cur.q+=' '+line;
else if(done(cur))start(line);
else cur[cur.last]+=' '+line}
flush();return out}
$('addq').onclick=()=>{add();say('')};
$('addpaste').onclick=()=>{const q=parse($('paste').value);if(!q.length){say('No questions found. Use: 1. question, then A. B. C. D. on the next lines, then Answer: B');return}
const only=list.children.length===1&&![...list.querySelectorAll('textarea,input[type=text],input:not([type])')].some(x=>x.value.trim());if(only)list.innerHTML='';
q.forEach(add);const miss=q.filter(x=>!x.k).length;
say(q.length+' question'+(q.length===1?'':'s')+' added.'+(miss?' '+miss+' still need an answer key: pick it beside the question.':' The answer keys were filled in.'));$('paste').value=''};
$('applykeys').onclick=()=>{const t=$('keys').value;let c=0,m;const re=/(\d+)\s*[-.):=]?\s*([A-Da-d])(?![A-Za-z])/g;
while((m=re.exec(t))){const r=list.children[+m[1]-1];if(!r)continue;const b=r.querySelector('input[type=radio][value="'+m[2].toUpperCase()+'"]');if(b){b.checked=true;c++}}
renum();say(c?c+' answer'+(c===1?'':'s')+' applied.':'No answers found. Write them like 1-B, 2-A, 3-D')};
$('qform').addEventListener('submit',e=>{if(!list.children.length){e.preventDefault();say('Add at least one question first.')}});
if(typeof OLD!=='undefined'&&OLD.length)OLD.forEach(add);else add();
})();
