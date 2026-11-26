/* Quiz and exam protection (version 45).
   BLOCKED (not recorded, because they cannot happen): copy, cut, paste, drag and drop, right-click, keyboard shortcuts, the Alt and Windows keys.
   RECORDED for the teacher (they cannot be fully blocked by a web page): leaving the tab or window, and leaving full screen.
   Leaving 3 times submits the work. Everything is released when the work is submitted. */
(()=>{const f=document.getElementById('quiz'),w=document.getElementById('warn'),MAX=3;
let n=0,out=false,tm,on=false,done=false,forced=false,beat=0;
const WHAT=(f&&f.dataset.what)||'quiz';
const log=t=>{if(!f)return;const d=new FormData();d.append('attempt',f.dataset.attempt);d.append('t',f.dataset.t);d.append('type',t);navigator.sendBeacon(f.dataset.log||'Quiz/log_event.php',d)};
const warn=m=>{if(!w)return;w.textContent=m;w.hidden=false;clearTimeout(tm);tm=setTimeout(()=>w.hidden=true,4000)};
const NOPE={copy:'Copy and paste are turned off',cut:'Copy and paste are turned off',paste:'Copy and paste are turned off',drop:'Drag and drop is turned off',right_click:'Right-click is turned off'};
const block=(e,t)=>{e.preventDefault();warn((NOPE[t]||'Keyboard shortcuts are turned off')+' during the '+WHAT+'.')}; // blocked only: nothing is recorded
['copy','cut','paste','drop'].forEach(t=>document.addEventListener(t,e=>block(e,t)));
document.addEventListener('contextmenu',e=>block(e,'right_click'));
document.addEventListener('keydown',e=>{const k=(e.key||'').toLowerCase();
if(((e.ctrlKey||e.metaKey)&&'cvxaups'.includes(k)&&k.length===1)||k==='printscreen'||k==='f12'){block(e,'shortcut_'+k);return}
if(!on||done)return;
const gr=e.getModifierState&&e.getModifierState('AltGraph'); // AltGr is used to type some characters, leave it alone
if((k==='alt'||e.altKey)&&!gr){e.preventDefault();if(!e.repeat)warn('The Alt key is turned off during the '+WHAT+'.');return}
if(k==='meta'||k==='os'){e.preventDefault();if(!e.repeat)warn('The Windows key is turned off during the '+WHAT+'.')}});
// ---- full screen + keyboard lock (lock only works while the page is in full screen that the page itself started)
const lock=()=>{try{if(navigator.keyboard&&navigator.keyboard.lock)navigator.keyboard.lock().catch(()=>{})}catch(e){}};
const unlock=()=>{try{if(navigator.keyboard&&navigator.keyboard.unlock)navigator.keyboard.unlock()}catch(e){}};
const full=()=>{try{const r=document.documentElement.requestFullscreen&&document.documentElement.requestFullscreen();if(r&&r.then)r.then(lock,()=>{});else lock()}catch(e){}};
const leaveFull=()=>{try{if(document.fullscreenElement&&document.exitFullscreen)document.exitFullscreen().catch(()=>{})}catch(e){}};
document.addEventListener('fullscreenchange',()=>{if(on&&!done&&!document.fullscreenElement){log('fullscreen_exit');warn('Stay in full screen. Your teacher can see when you leave it. Click anywhere to go back.')}});
document.addEventListener('click',()=>{if(on&&!done&&!document.fullscreenElement)full()});
// ---- leaving the tab or window (Alt+Tab that the browser could not stop). Clicking inside the preview frame is not leaving.
const away=()=>{if(!on||out||done)return;out=true;n++;log('tab_switch');warn('You left the '+WHAT+' ('+n+'/'+MAX+'). Your teacher can see this.');if(n>=MAX)submitNow()};
document.addEventListener('visibilitychange',()=>document.hidden?away():out=false);
window.addEventListener('blur',()=>setTimeout(()=>{if(!document.hasFocus())away()},60));window.addEventListener('focus',()=>out=false);
window.addEventListener('beforeunload',e=>{if(on&&!done){e.preventDefault();e.returnValue=''}});
// ---- start, stop, submit
const start=()=>{on=true;done=false;out=false;document.body.classList.add('exam-mode');full();if(!beat)beat=setInterval(()=>{if(f)fetch('Logging/ping.php',{method:'POST',body:new URLSearchParams({t:f.dataset.t}),credentials:'same-origin'}).catch(()=>{})},240000)};
const stop=()=>{on=false;clearInterval(beat);beat=0;unlock();leaveFull();document.body.classList.remove('exam-mode')};
const submitNow=()=>{if(done)return;done=true;forced=true;stop();f.dispatchEvent(new Event('submit',{cancelable:true}));f.submit()};
// ---- "are you sure?" before a student submits by hand (the timer and the 3-strikes rule skip it)
const ask=()=>{if(document.getElementById('cfm'))return;const d=document.createElement('div');d.id='cfm';d.className='modal';d.setAttribute('role','dialog');d.setAttribute('aria-modal','true');
d.innerHTML='<div class="card"><h2>Submit now?</h2><p></p><button type="button" id="cfm-yes">Yes, submit</button> <button type="button" class="alt" id="cfm-no">No, go back</button></div>';
d.querySelector('p').textContent=f.dataset.confirm||'After you submit you cannot change your answers.';document.body.appendChild(d);
const no=document.getElementById('cfm-no');no.focus();no.onclick=()=>d.remove();document.getElementById('cfm-yes').onclick=()=>{d.remove();submitNow()};
d.addEventListener('keydown',e=>{if(e.key==='Escape')d.remove()})};
if(f)f.addEventListener('submit',e=>{if(forced)return;e.preventDefault();e.stopImmediatePropagation();ask()});
window.AC={log,warn,start,submitNow};})();
