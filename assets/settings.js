// Passwords, Student ID and email stay hidden (*****). They show only WHILE the eye button is held down
// (mouse button, finger, or the Space / Enter key) and hide again as soon as it is released.
const hold=(b,show,hide)=>{const on=e=>{if(e.type==='keydown'&&e.key!==' '&&e.key!=='Enter')return;e.preventDefault();show();b.classList.add('held')};
const off=()=>{hide();b.classList.remove('held')};
b.addEventListener('pointerdown',e=>{on(e);try{b.setPointerCapture(e.pointerId)}catch(x){}});['pointerup','pointercancel','lostpointercapture','blur'].forEach(t=>b.addEventListener(t,off));window.addEventListener('pointerup',off);
b.addEventListener('keydown',on);b.addEventListener('keyup',off);b.addEventListener('contextmenu',e=>e.preventDefault());b.addEventListener('click',e=>e.preventDefault())};
document.querySelectorAll('.eye').forEach(b=>{const i=b.parentNode.querySelector('input');hold(b,()=>{i.type='text'},()=>{i.type='password'})});
document.querySelectorAll('.peek').forEach(b=>{const s=b.parentNode.querySelector('.sv');hold(b,()=>{s.textContent=s.dataset.v;s.classList.add('open')},()=>{s.textContent='*****';s.classList.remove('open')})});
window.addEventListener('blur',()=>{document.querySelectorAll('.sv.open').forEach(s=>{s.textContent='*****';s.classList.remove('open')});document.querySelectorAll('.pw input[type=text]').forEach(i=>i.type='password')});
(()=>{const d=document.documentElement,g=document.getElementById('gear'),p=document.getElementById('settings'),th=document.getElementById('s-theme');
if(!g)return;th.value=d.dataset.theme;
g.onclick=()=>p.hidden=!p.hidden;
g.setAttribute('aria-expanded','false');new MutationObserver(()=>g.setAttribute('aria-expanded',String(!p.hidden))).observe(p,{attributes:true,attributeFilter:['hidden']});
document.addEventListener('click',e=>{if(!p.contains(e.target)&&!g.contains(e.target))p.hidden=true});
document.addEventListener('keydown',e=>{if(e.key==='Escape')p.hidden=true});
th.onchange=()=>{d.dataset.theme=th.value;try{localStorage.theme=th.value}catch(e){}};})();

// left menu on a phone: the menu button slides it in, a tap outside or Escape closes it
(()=>{const b=document.getElementById('menubtn'),sh=document.getElementById('shade');if(!b)return;
const set=o=>{document.body.classList.toggle('menu-open',o);b.setAttribute('aria-expanded',String(o))};
b.onclick=()=>set(!document.body.classList.contains('menu-open'));if(sh)sh.onclick=()=>set(false);
document.addEventListener('keydown',e=>{if(e.key==='Escape')set(false)});})();
