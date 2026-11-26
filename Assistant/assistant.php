<?php require __DIR__.'/../config.php';$u=need('student');head('AI Assistant');
if(ai_exam_open($u['id'])){echo '<div class="card"><h2>🔒 The AI Assistant is off during your exam</h2><p>It is turned off while a code exam is open. Submit your exam, then come back.</p></div>';foot();exit;}
$on=AI_ON;
echo '<p>'.($on?'Ask the <b>AI Assistant</b> about Python, JavaScript, HTML/CSS, PHP, C++ or C#. Paste your code and say what is wrong.':'<span class="pill">Built-in tutor</span> It answers common questions about Python, JavaScript, HTML/CSS, PHP, C++ and C# and checks pasted code for typical mistakes. The full AI Assistant joins this chat when your teacher switches it on.').' It is turned off while you are taking an exam.</p>'
.chat_box('',['What is a variable?','Explain a for loop with an example','Why does my code print nothing?','What is the difference between a list and an array?'])
.'<script>const T='.json_encode(csrf()).';</script><script src="assets/tutor.js?v=48.1"></script><script src="assets/assistant.js?v=48.1"></script><script>mkChat("",{on:'.($on?'true':'false').',name:'.json_encode(AI_SHOWN).',left:'.(int)ai_left($u).'})</script>';foot();
