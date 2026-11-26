// Live countdowns: <span class="cd" data-t="unix seconds" data-pre="in " data-post=" left">.
// It uses the clock of the server (window.NOW_MS), so a wrong clock on the student's computer does not matter.
// When a countdown reaches zero the page reloads: a quiz that just opened gets its Start button, one that just ended shows Missed.
(()=>{const els=[...document.querySelectorAll('.cd[data-t]')];if(!els.length)return;
const skew=(window.NOW_MS||Date.now())-Date.now(),now=()=>Math.floor((Date.now()+skew)/1000),t0=now();let gone=false;
const fmt=s=>{const d=Math.floor(s/86400),h=Math.floor(s%86400/3600),m=Math.floor(s%3600/60),x=s%60;return d?d+'d '+h+'h':h?h+'h '+m+'m':m+'m '+String(x).padStart(2,'0')+'s'};
const tick=()=>{const n=now();for(const e of els){const left=+e.dataset.t-n;
e.textContent=left>0?(e.dataset.pre||'')+fmt(left)+(e.dataset.post||''):'now';e.classList.toggle('hot',left>0&&left<3600);
if(left<=0&&+e.dataset.t>t0&&!gone&&!e.hasAttribute('data-noreload')){gone=true;setTimeout(()=>location.reload(),1500)}}};
tick();setInterval(tick,1000)})();
