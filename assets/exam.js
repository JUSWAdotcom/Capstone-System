// The code exam page: the editor (assets/ide.js), the timer, full screen and submit.
(()=>{const f=document.getElementById('quiz'),clk=document.getElementById('clock'),box=document.getElementById('clockbox');let left=EX.left,go=false;
IDE.mount({lang:EX.lang,files:EX.files,store:EX.store,T:EX.T,add:EX.lang==='web'});
const show=()=>{clk.textContent=Math.floor(left/60)+':'+String(left%60).padStart(2,'0');box.classList.toggle('low',left<300)};show();
f.addEventListener('submit',()=>{document.getElementById('fc').value=IDE.payload();document.getElementById('fo').value=IDE.output()});
const tick=()=>{show();if(left--<=0){AC.submitNow();return}setTimeout(tick,1000)};
document.getElementById('start').onclick=()=>{AC.start();go=true;f.hidden=false;document.getElementById('intro').hidden=true;document.body.classList.add('ide-on');tick();document.getElementById('code').focus()};
})();
