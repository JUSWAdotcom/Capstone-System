/* Chat box used by the AI Assistant page (id prefix "") and the Playground (id prefix "pc").
   With an AI key it asks the AI through Assistant/ai_chat.php. Without one (or if the AI does not answer)
   the built-in tutor in tutor.js answers, so the chat always works. */
window.mkChat=(p,o)=>{
const $=i=>document.getElementById(p+i),log=$('log'),q=$('q'),send=$('send'),cf=$('cf');
if(!log||!q||!cf||!send)return;
let hist=[],busy=false,last=null;
const esc=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
// text is escaped first, then **bold**, `code` and ``` blocks are turned into safe HTML
const fmt=t=>String(t).split('```').map((s,i)=>i%2?'<pre>'+esc(s.replace(/^[A-Za-z+#]*\n/,'').trim())+'</pre>':(s.trim()?'<p>'+esc(s.trim()).replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/`([^`\n]+)`/g,'<code>$1</code>').replace(/\n/g,'<br>')+'</p>':'')).join('');
const add=(who,html)=>{const d=document.createElement('div');d.className='msg '+who;d.innerHTML=html;log.appendChild(d);log.scrollTop=log.scrollHeight;return d};
const left=n=>{const l=$('left');if(l)l.textContent=o.on?n+' AI question'+(n===1?'':'s')+' left today':'Built-in tutor: no limit'};
const hello=()=>add('bot',fmt(o.on?'Hi! I am your AI study assistant. Ask me about **Python**, **JavaScript**, **HTML/CSS**, **PHP**, **C++** or **C#**. You can paste your code and tell me what is wrong, and I will explain it step by step.':'Hi! I am your tutor. Ask me about **Python**, **JavaScript**, **HTML/CSS**, **PHP**, **C++** or **C#**, for example "How do I write a for loop in C++?". Paste your code and say **check my code** and I will look for typical mistakes.'));
hello();left(o.left||0);
const local=(text,c,withCode,why)=>{const r=Tutor.answer(text,{lang:c.lang,code:withCode?c.code:'',output:c.output||'',last});if(r.lang)last=r.lang;return (why?why+'\n\n':'')+r.text};
async function ask(text){text=text.trim();if(busy||!text)return;
const c=o.ctx?o.ctx():{},withCode=!!(c.include&&c.code&&c.code.trim());
busy=true;send.disabled=true;
add('me','<p>'+esc(text).replace(/\n/g,'<br>')+(withCode?'<br><small>(your code was sent too)</small>':'')+'</p>');
const w=add('bot','<p><small>Thinking...</small></p>');
const done=html=>{w.innerHTML=html;busy=false;send.disabled=false;q.focus();log.scrollTop=log.scrollHeight};
if(!o.on||typeof T==='undefined'){done(fmt(local(text,c,withCode,'')));return}
const full=withCode?text+'\n\nMy code ('+((window.Tutor&&Tutor.names[c.lang])||c.lang)+'):\n```'+c.lang+'\n'+c.code.slice(0,4500)+'\n```'+(c.output?'\nOutput:\n```\n'+String(c.output).slice(0,500)+'\n```':''):text;
hist.push({role:'user',text:full});
try{const r=await fetch('Assistant/ai_chat.php',{method:'POST',body:new URLSearchParams({t:T,messages:JSON.stringify(hist.slice(-10))})});const j=await r.json();let t=String(j.reply||'');
if(typeof j.left==='number')left(j.left);
if(t==='AI_OFF'||t.indexOf('AI_ERR:')===0){hist.pop();done(fmt(local(text,c,withCode,t==='AI_OFF'?'':'The AI Assistant is busy right now, so the built-in tutor answers instead:')))}
else if(!j.ok){hist.pop();done(fmt(/^You have used all/.test(t)?t+'\n\nThe built-in tutor can still help:\n\n'+local(text,c,withCode,''):t))}
else{hist.push({role:'assistant',text:t});done(fmt(t))}}
catch(e){hist.pop();done(fmt(local(text,c,withCode,'Could not reach the AI Assistant, so the built-in tutor answers instead:')))}}
cf.addEventListener('submit',e=>{e.preventDefault();const t=q.value;q.value='';ask(t)});
q.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();cf.requestSubmit()}});
const cl=$('clear');if(cl)cl.onclick=()=>{hist=[];log.innerHTML='';hello();q.focus()};
document.querySelectorAll('#'+p+'chips .chip').forEach(b=>b.onclick=()=>{q.value=b.textContent;q.focus()});
};
