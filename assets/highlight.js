/* Colours for the code examples in the lessons (version 46): the same colours as the code editor (like VS Code Dark+).
   Python, JavaScript, HTML (with the CSS in <style> and the JavaScript in <script>), CSS, PHP, C++, C# and Java.
   "Output" blocks stay plain. Every block gets a Copy button. */
(()=>{const A=document.querySelector('.lesson');if(!A)return;
const ESC=s=>s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
const W=(c,t)=>'<i class="'+c+'">'+ESC(t)+'</i>';
const KW={python:'and|as|assert|async|await|break|class|continue|def|del|elif|else|except|False|finally|for|from|global|if|import|in|is|lambda|None|nonlocal|not|or|pass|raise|return|True|try|while|with|yield|self',
javascript:'async|await|break|case|catch|class|const|continue|default|delete|do|else|export|extends|false|finally|for|function|if|import|in|instanceof|let|new|null|of|return|static|super|switch|this|throw|true|try|typeof|undefined|var|void|while|yield',
cpp:'auto|bool|break|case|catch|char|class|const|continue|default|delete|do|double|else|enum|false|float|for|if|int|long|namespace|new|nullptr|private|protected|public|return|short|signed|sizeof|static|struct|switch|template|this|throw|true|try|typedef|unsigned|using|virtual|void|while|std|string|vector|cout|cin|endl',
csharp:'abstract|as|base|bool|break|case|catch|char|class|const|continue|decimal|default|do|double|else|enum|false|finally|float|for|foreach|get|if|in|int|interface|is|List|long|namespace|new|null|out|override|private|protected|public|readonly|return|set|static|string|struct|switch|this|throw|true|try|using|var|virtual|void|while',
java:'abstract|boolean|break|byte|case|catch|char|class|continue|default|do|double|else|enum|extends|false|final|finally|float|for|if|implements|import|instanceof|int|interface|long|new|null|package|private|protected|public|return|short|static|String|super|switch|this|throw|throws|true|try|void|while',
php:'abstract|array|as|break|case|catch|class|const|continue|default|do|echo|else|elseif|empty|extends|false|finally|fn|for|foreach|function|if|include|isset|match|new|null|print|private|protected|public|require|return|static|switch|throw|true|try|unset|use|while'};
const ALIAS={js:'javascript','c++':'cpp','c#':'csharp',cs:'csharp',py:'python'};
const RX={};
// ---- Python, JavaScript, PHP, C++, C#, Java ----
const prog=(code,k)=>{if(!RX[k]){const c=k==='python'?'#[^\\n]*':k==='php'?'\\/\\/[^\\n]*|#(?!\\[)[^\\n]*|\\/\\*[\\s\\S]*?\\*\\/':k==='cpp'?'\\/\\/[^\\n]*|\\/\\*[\\s\\S]*?\\*\\/|#\\s*\\w+[^\\n]*':'\\/\\/[^\\n]*|\\/\\*[\\s\\S]*?\\*\\/';
 const s=(k==='python'?'"""[\\s\\S]*?"""|\'\'\'[\\s\\S]*?\'\'\'|[fFrRbB]?':'')+'"(?:[^"\\\\\\n]|\\\\.)*"|\'(?:[^\'\\\\\\n]|\\\\.)*\''+(k==='javascript'?'|`(?:[^`\\\\]|\\\\.)*`':'')+(k==='csharp'?'|\\$"(?:[^"\\\\\\n]|\\\\.)*"':'');
 RX[k]=new RegExp('('+c+')|('+s+')|(\\b\\d+(?:\\.\\d+)?[fFLlmM]?\\b)|\\b('+KW[k]+')\\b|(\\$[A-Za-z_]\\w*)|\\b([A-Z]\\w*)\\b(?=\\s*[.(<\\[]|\\s+[a-z_])|\\b([A-Za-z_]\\w*)(?=\\s*\\()','g')}
 const r=RX[k];r.lastIndex=0;let o='',i=0,m;
 while((m=r.exec(code))){if(m[0]===''){r.lastIndex++;continue}o+=ESC(code.slice(i,m.index));
  o+=m[1]?(k==='cpp'&&m[1][0]==='#'?W('k',m[1]):W('c',m[1])):m[2]?W('s',m[2]):m[3]?W('n',m[3]):m[4]?W('k',m[4]):m[5]?W('v',m[5]):m[6]?W('x',m[6]):W('f',m[7]);i=r.lastIndex}
 return o+ESC(code.slice(i))};
// ---- CSS: selectors, properties, values, numbers and colours ----
const css=code=>{const r=/(\/\*[\s\S]*?\*\/)|("[^"\n]*"|'[^'\n]*')|([{}])|(;)|(:)|([^{};:"'\/]+|\/)/g;let o='',m,d=0,prop=false,val=false;
 const next=i=>{const x=code.slice(i).search(/[{;}]/);return x<0?'':code[i+x]};
 const value=t=>ESC(t).replace(/(#[0-9a-fA-F]{3,8}\b)|(-?\d*\.?\d+(?:px|em|rem|%|vh|vw|vmin|vmax|s|ms|deg|fr|ch)?\b)|(!important)|([A-Za-z-]+(?=\())|([A-Za-z-]+)/g,(z,h,n,im,fn,w)=>h||n?'<i class="n">'+z+'</i>':im?'<i class="k">'+z+'</i>':fn?'<i class="f">'+z+'</i>':'<i class="s">'+z+'</i>');
 while((m=r.exec(code))){const t=m[0];
  if(m[1])o+=W('c',t);else if(m[2])o+=W('s',t);
  else if(m[3]){o+=ESC(t);if(t==='{'){d++;prop=true;val=false}else{d=Math.max(0,d-1);prop=d>0;val=false}}
  else if(m[4]){o+=t;if(d>0){prop=true;val=false}}
  else if(m[5]){o+=t;if(d>0&&prop&&!val){val=true;prop=false}}
  else{if(!t.trim()){o+=t;continue}
   if(val)o+=value(t);
   else if(d===0||next(r.lastIndex)==='{')o+=/^\s*@/.test(t)?t.replace(/^(\s*)(@[\w-]+)(.*)$/s,(z,a,b,c)=>a+W('k',b)+value(c)):W('sel',t);
   else if(prop)o+=W('p',t);else o+=ESC(t)}}
 return o};
// ---- HTML (and the CSS / JavaScript inside it) ----
const tags=code=>{const r=/(<!--[\s\S]*?-->)|(<!DOCTYPE[^>]*>)|(<\/?[A-Za-z][\w-]*)|(\/?>)|(\s[A-Za-z_:@][\w:.-]*)(?=\s*=)|("[^"]*"|'[^']*')/gi;let o='',i=0,m,inTag=false;
 while((m=r.exec(code))){o+=ESC(code.slice(i,m.index));const t=m[0];
  if(m[1])o+=W('c',t);else if(m[2])o+=W('t',t);else if(m[3]){o+=W('t',t);inTag=true}else if(m[4]){o+=W('t',t);inTag=false}
  else if(m[5])o+=inTag?W('a',t):ESC(t);else o+=inTag?W('s',t):ESC(t);i=r.lastIndex}
 return o+ESC(code.slice(i))};
const html=code=>{let o='',i=0,m;const r=/(<(style|script)\b[^>]*>)([\s\S]*?)(<\/\2\s*>)/gi;
 while((m=r.exec(code))){o+=tags(code.slice(i,m.index))+tags(m[1])+(m[2].toLowerCase()==='style'?css(m[3]):prog(m[3],'javascript'))+tags(m[4]);i=r.lastIndex}
 return o+tags(code.slice(i))};
// which language is this block? the ```language line, otherwise the language of the module
const which=(pre,mod)=>{let k=(pre.dataset.k||'').toLowerCase();k=ALIAS[k]||k;const t=pre.textContent;
 if(k==='text')return '';if(k==='html'||k==='css'||KW[k])return k;
 if(mod==='html')return /<\/?[a-z!][^>]*>/i.test(t)?'html':(/[{}]/.test(t)&&/:/.test(t)?'css':'html');
 return KW[mod]?mod:''};
const mod=ALIAS[A.dataset.lang]||A.dataset.lang||'';
A.querySelectorAll('pre').forEach(pre=>{const prev=pre.previousElementSibling,raw=pre.textContent;
 if(prev&&/Output\s*:?\s*$/i.test(prev.textContent)){pre.classList.add('outp')}
 else{const k=which(pre,mod);if(k){pre.innerHTML=k==='html'?html(raw):k==='css'?css(raw):prog(raw,k);pre.classList.add('syn','lang-'+k)}}
 const b=document.createElement('button');b.type='button';b.className='copy alt';b.textContent='Copy';
 b.onclick=()=>navigator.clipboard.writeText(raw).then(()=>{b.textContent='Copied ✓';setTimeout(()=>b.textContent='Copy',1500)});pre.appendChild(b)})})();
