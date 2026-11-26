// Main page (Logging/login.php): falling letters, typing line, light/dark button and the log in pop-up
(function(){
var reduce=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
var root=document.documentElement;

// ---------- the log in pop-up ----------
var modal=document.getElementById('lmodal'),opener=null;
function focusables(){return modal?[].slice.call(modal.querySelectorAll('a[href],button,input,select')).filter(function(x){return !x.disabled&&x.offsetParent!==null}):[]}
function openLogin(from){if(!modal)return;opener=from||document.activeElement;modal.classList.add('open');document.body.style.overflow='hidden';var f=modal.querySelector('input[name=sid]');if(f)setTimeout(function(){f.focus()},30)}
function closeLogin(){if(!modal)return;modal.classList.remove('open');document.body.style.overflow='';try{history.replaceState(null,'',location.pathname)}catch(e){}if(opener&&opener.focus)opener.focus()}
document.querySelectorAll('[data-open-login]').forEach(function(a){a.addEventListener('click',function(e){e.preventDefault();openLogin(a)})});
if(modal){
modal.addEventListener('mousedown',function(e){if(e.target===modal)closeLogin()});
var x=modal.querySelector('.lclose');if(x)x.addEventListener('click',function(e){e.preventDefault();closeLogin()});
document.addEventListener('keydown',function(e){
if(!modal.classList.contains('open'))return;
if(e.key==='Escape'){closeLogin();return}
if(e.key==='Tab'){var f=focusables();if(!f.length)return;var a=f[0],z=f[f.length-1];
if(e.shiftKey&&document.activeElement===a){e.preventDefault();z.focus()}else if(!e.shiftKey&&document.activeElement===z){e.preventDefault();a.focus()}}});
if(modal.classList.contains('open')){document.body.style.overflow='hidden';var f0=modal.querySelector('input[name=sid]');if(f0)f0.focus()}
}

// ---------- light / dark button ----------
var tb=document.getElementById('themebtn');
function icon(){if(tb)tb.textContent=root.dataset.theme==='light'?'🌙':'☀️'}
if(tb){icon();tb.addEventListener('click',function(){var t=root.dataset.theme==='light'?'dark':'light';root.dataset.theme=t;try{localStorage.theme=t}catch(e){}icon()})}

// ---------- the typing line: Learn PHP, C++, C#, ... ----------
var el=document.getElementById('typed');
if(el&&!reduce){
var words=['PHP','C++','C#','Java','HTML / CSS','JavaScript','Python'],i=0,j=0,del=false;
(function tick(){var w=words[i];el.textContent=w.slice(0,j);
if(!del&&j===w.length){del=true;return setTimeout(tick,1500)}
if(del&&j===0){del=false;i=(i+1)%words.length;return setTimeout(tick,350)}
j+=del?-1:1;setTimeout(tick,del?45:100)})();
}

// ---------- falling letters ----------
var c=document.getElementById('rain');
if(!c||!c.getContext)return;
var ctx=c.getContext('2d'),W=0,H=0,fs=18,dpr=Math.min(window.devicePixelRatio||1,1.5),cols=0,pos=[],spd=[],last=[],col='#4cc9f0',run=false,t0=0;
var chars='01{}[]()<>/;=+-*#$%&?!abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
function rnd(){return chars[Math.floor(Math.random()*chars.length)]}
function readColor(){var v=getComputedStyle(root).getPropertyValue('--ac').trim();if(v)col=v}
function size(){W=window.innerWidth;H=window.innerHeight;c.width=Math.round(W*dpr);c.height=Math.round(H*dpr);ctx.setTransform(dpr,0,0,dpr,0,0);
cols=Math.ceil(W/fs);pos=[];spd=[];last=[];
for(var k=0;k<cols;k++){pos[k]=-Math.random()*(H/fs);spd[k]=.25+Math.random()*.55;last[k]=-1}}
function still(){ctx.clearRect(0,0,W,H);ctx.font='bold '+fs+'px Consolas,Menlo,monospace';ctx.fillStyle=col;ctx.globalAlpha=.35;
for(var k=0;k<cols;k++){if(Math.random()<.5)continue;var n=3+Math.floor(Math.random()*8),y0=Math.floor(Math.random()*(H/fs));for(var m=0;m<n;m++){ctx.globalAlpha=.4*(1-m/n);ctx.fillText(rnd(),k*fs,(y0+m)*fs)}}ctx.globalAlpha=1}
function frame(t){
if(!run)return;requestAnimationFrame(frame);
if(t-t0<42)return;t0=t; // about 24 pictures per second is enough
ctx.globalCompositeOperation='destination-out';ctx.fillStyle='rgba(0,0,0,.1)';ctx.fillRect(0,0,W,H);
ctx.globalCompositeOperation='source-over';ctx.font='bold '+fs+'px Consolas,Menlo,monospace';ctx.fillStyle=col;
for(var k=0;k<cols;k++){pos[k]+=spd[k];var row=Math.floor(pos[k]);
if(row!==last[k]&&row>=0){last[k]=row;ctx.fillText(rnd(),k*fs,row*fs)}
if(row*fs>H&&Math.random()>.975){pos[k]=-Math.random()*12;last[k]=-1;spd[k]=.25+Math.random()*.55}}}
function start(){if(run)return;run=true;requestAnimationFrame(frame)}
function stop(){run=false}
size();readColor();
if(reduce){still()}else{start()}
var rt;window.addEventListener('resize',function(){clearTimeout(rt);rt=setTimeout(function(){size();if(reduce)still()},150)});
document.addEventListener('visibilitychange',function(){if(reduce)return;if(document.hidden)stop();else start()});
if(window.MutationObserver)new MutationObserver(function(){readColor();if(reduce)still()}).observe(root,{attributes:true,attributeFilter:['data-theme']});
})();
