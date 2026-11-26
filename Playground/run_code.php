<?php require __DIR__.'/../config.php';need('any');check();header('Content-Type: application/json');
// Runs PHP, C++, C# and Java on this computer (Python, JavaScript and HTML run in the browser).
// lang=web runs one PHP file of a WEB PROJECT exam (version 41): all the files of the project are written to a temporary folder,
// the form / link data is put into $_GET or $_POST, and the HTML that the PHP file prints goes back to the preview.
$L=$_POST['lang']??'';
function done($t){echo json_encode(['out'=>$t]);exit;}
function fail($t){global $L;echo json_encode($L==='web'?['out'=>'','err'=>$t]:['out'=>$t]);exit;}
function rrm($p){foreach(glob("$p/*")as $f)is_dir($f)?rrm($f):unlink($f);@rmdir($p);}
function sh($cmd,$cwd,$sec,$max=4000){$p=proc_open($cmd,[1=>['pipe','w'],2=>['pipe','w']],$pp,$cwd);if(!is_resource($p))return 'Could not start the program.';
stream_set_blocking($pp[1],false);stream_set_blocking($pp[2],false);$o='';$end=time()+$sec;
while(time()<$end){$o.=stream_get_contents($pp[1]).stream_get_contents($pp[2]);if(!proc_get_status($p)['running'])break;usleep(50000);}
$o.=stream_get_contents($pp[1]).stream_get_contents($pp[2]);
if(proc_get_status($p)['running']){proc_terminate($p);$o.="\nStopped: the program ran for over $sec seconds.";}
proc_close($p);return substr($o,0,$max);}
function missing($t){return (bool)preg_match("/^\s*(?:'[^']*' is not recognized as an internal or external command|The system cannot find the path specified|sh: (?:\d+: )?\S+: not found|\S+: command not found|Could not start the program)/i",$t);} // the compiler is not installed (not an error of the student's code)
const HOWTO="The teacher or admin installs it once on the computer that runs CodeSmartAI-X: open the Launcher folder and double-click \"Install languages.bat\". Students do not install anything.";
// version 48: SAFETY CHECK for C++, Java and C#. These run as real programs on the computer that runs CodeSmartAI-X, so a student
// could otherwise read config.php (the AI key), delete files or shut the computer down. Programs that start other programs, use the
// Windows system, or open files outside their own folder are not run. Normal programs (and simple file names like "data.txt") work.
// It is a basic guard, not a perfect sandbox: keep backups, and on a public website these languages are turned off (PUBLIC_SITE).
function native_unsafe($L,$code){
$c=preg_replace('/\bsystem\s*\(\s*"(?:pause|cls|clear)"\s*\)/i','0',$code); // system("pause") / system("cls") of beginners are harmless
$paths=['/"(?:[A-Za-z]:(?:\\\\\\\\|\/)|\\\\\\\\\\\\\\\\|\/(?:etc|home|usr|var|root|tmp|proc|mnt|opt|Users|Windows)\b)/'=>'a full path to a folder of the computer',
 '/"[^"\n]*:\\\\\\\\/'=>'a full path to a folder of the computer','/\.\.(?:\\\\\\\\|\/)|"\.\."/'=>'".." (the folder above)','/(?:xampp|htdocs|config\.php)/i'=>'the files of the website'];
$rules=['cpp'=>['/\b(?:system|popen|_popen|_wpopen|_wsystem|exec[lv]p?e?|fork|vfork|_?spawn[lv]p?e?|CreateProcess\w*|ShellExecute\w*|WinExec|LoadLibrary\w*|GetProcAddress|DeleteFile\w*|MoveFile\w*|freopen|_?chdir|_?getcwd|GetCurrentDirectory\w*|GetModuleFileName\w*|realpath|_fullpath|_?rmdir)\s*\(/'=>'a command that starts programs or changes folders',
 '/#\s*include\s*<\s*(?:windows|winbase|winuser|shellapi|process|direct|dirent|filesystem|experimental\/filesystem|sys\/[a-z]+)\.?h?\s*>/i'=>'a system library (#include)','/#\s*include\s*"[^"]*[\\\\\/:]/'=>'#include of a file from another folder','/\b(?:__asm__|__asm|asm)\b/'=>'inline assembly',
 '/\b(?:remove|rename|_unlink|unlink)\s*\(\s*"/'=>'deleting or renaming files'],
'java'=>['/\b(?:Runtime|ProcessBuilder|ProcessHandle|ClassLoader|URLClassLoader|MethodHandles|SecurityManager)\b/'=>'Runtime / ProcessBuilder (starting programs)','/java\.lang\.reflect|Class\.forName|\bnative\b/'=>'reflection or native code',
 '/System\.(?:getenv|load|loadLibrary|getProperty|setProperty)\b/'=>'system settings','/\b(?:getParentFile|getParent|toAbsolutePath|getAbsoluteFile|getAbsolutePath|getCanonical\w*|listRoots|newDirectoryStream|walk)\s*\(/'=>'moving to other folders'],
'csharp'=>['/System\.Diagnostics|\bProcess(?:StartInfo)?\b/'=>'Process (starting programs)','/\b(?:DllImport|LibraryImport|extern|unsafe)\b|System\.Reflection|\bAssembly\.|\bActivator\./'=>'native code or reflection',
 '/Microsoft\.Win32|\bRegistry\b|\bDriveInfo\b|\bAppDomain\b|AppContext\.BaseDirectory/'=>'the Windows system','/Environment\.(?:GetEnvironmentVariable|GetFolderPath|CurrentDirectory|SystemDirectory|SetEnvironmentVariable|GetLogicalDrives)|Directory\.(?:GetParent|SetCurrentDirectory|GetCurrentDirectory|GetLogicalDrives)|GetFullPath|\.Parent\b/'=>'moving to other folders']];
foreach(($rules[$L]??[])+$paths as $rx=>$what)if(preg_match($rx,$c))return $what;return '';}
// version 48: C# errors without the hidden temporary folder, each error once: "Program.cs(1,39): error CS1002: ; expected"
function cs_clean($r,$d){$r=str_ireplace([$d.'\\',$d.'/',str_replace('\\','/',$d).'/'],'',$r);$r=preg_replace('/ ?\[[^\]\r\n]*app\.csproj\]/','',$r);
if(!preg_match('/\berror CS\d+/',$r))return $r;$r=preg_replace('/\s*The build failed\. Fix the build errors and run again\.\s*/',"\n",$r);
$o=[];foreach(preg_split('/\r?\n/',$r) as $l){$l=rtrim($l);if($l!==''&&!in_array($l,$o,true))$o[]=$l;}return "Compile error:\n".implode("\n",$o);}
$NOFN='exec,shell_exec,system,passthru,popen,proc_open,pcntl_exec';
if(!in_array($L,['php','cpp','csharp','java','web']))fail('Unknown language.');
if($L==='web'){$fs=json_decode((string)($_POST['files']??''),true);if(!is_array($fs)||count($fs)>60)fail('The project files could not be read.');$tot=0;
foreach($fs as $n=>$c){if(!is_string($n)||!preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,50}\.(html|css|js|php)$/',$n)||!is_string($c))fail('Bad file name: '.$n);$tot+=strlen($c);}
if($tot>600000)fail('The project is too big (more than 600 KB).');
$entry=(string)($_POST['entry']??'');if(!isset($fs[$entry]))fail('File not found in the project: '.$entry);
if(substr($entry,-4)!=='.php')done($fs[$entry]);} // a plain HTML page needs no server
if(!run_ok($L))fail(PUBLIC_SITE&&!ALLOW_RUN_ON_PUBLIC?'Running PHP, C++, C# and Java is turned off on this public website for safety (a program could harm the server). Python, JavaScript and HTML / CSS still run in your browser. To run the others, use the system on a school computer.':'Running C++, C# and Java is turned off on this server (ALLOW_NATIVE_RUN in config.php).');
$gap=($L==='web'?0.8:2)-(microtime(true)-(float)($_SESSION['run']??0));if($gap>($L==='web'?0.8:2))fail('Please wait a moment between runs.'); // version 48: a run that comes a little too soon waits instead of failing
$_SESSION['run']=microtime(true)+max(0,$gap);session_write_close();if($gap>0)usleep((int)($gap*1000000)); // other pages of this student do not wait for the program
$code=substr($_POST['code']??'',0,50000);
if(in_array($L,['cpp','java','csharp'],true)&&($why=native_unsafe($L,$code))!=='')fail('For safety this program was not run: it uses '.$why.'. In CodeSmartAI-X a program cannot start other programs, use the Windows system or open files outside its own folder. Simple file names like "data.txt" are fine.');
$d=sys_get_temp_dir().DIRECTORY_SEPARATOR.'csx_'.bin2hex(random_bytes(6));mkdir($d);$S=DIRECTORY_SEPARATOR;
if($L==='php'){file_put_contents("$d/a.php",$code);$r=sh('"'.PHP_CLI.'" -d display_errors=1 -d open_basedir="'.$d.'" -d disable_functions='.$NOFN.' "'.$d.$S.'a.php"',$d,5);}
elseif($L==='web'){foreach($fs as $n=>$c)file_put_contents($d.$S.$n,$c);
$m=($_POST['method']??'get')==='post'?'post':'get';$data=json_decode((string)($_POST['data']??'{}'),true);$clean=[];
if(is_array($data))foreach(array_slice($data,0,100,true) as $k=>$v)if(is_string($k)&&is_scalar($v))$clean[mb_substr($k,0,100)]=mb_substr((string)$v,0,5000);
file_put_contents($d.$S.'__req.json',json_encode(['m'=>$m,'d'=>$clean]));
file_put_contents($d.$S.'__prep.php','<?php $__r=json_decode(file_get_contents(__DIR__."/__req.json"),true);$_SERVER["REQUEST_METHOD"]=strtoupper($__r["m"]);if($__r["m"]==="post")$_POST=$__r["d"];else $_GET=$__r["d"];$_REQUEST=array_merge($_GET,$_POST);unset($__r);');
$r=sh('"'.PHP_CLI.'" -d display_errors=1 -d html_errors=0 -d auto_prepend_file="'.$d.$S.'__prep.php" -d open_basedir="'.$d.'" -d session.save_path="'.$d.'" -d disable_functions='.$NOFN.' "'.$d.$S.$entry.'"',$d,5,300000);
rrm($d);done($r);}
elseif($L==='cpp'){file_put_contents("$d/a.cpp",$code);$g=tool('gpp');if($g!=='g++'&&stripos((string)getenv('PATH'),dirname($g))===false)putenv('PATH='.dirname($g).PATH_SEPARATOR.getenv('PATH'));
$b=sh('"'.$g.'" -std=c++17'.(PHP_OS_FAMILY==='Windows'?' -static':'').' a.cpp -o a.exe',$d,40); // -static: the program runs without the MinGW DLLs
$r=is_file("$d/a.exe")?sh('"'.$d.$S.'a.exe"',$d,5):(missing($b)?"C++ is not installed on this computer yet.\n\n".HOWTO:"Compile error:\n".$b);}
elseif($L==='java'){file_put_contents("$d/Main.java",$code);$r=sh('"'.tool('java').'" Main.java',$d,30);
if(missing($r))$r="Java is not installed on this computer yet.\n\n".HOWTO;}
else{file_put_contents("$d/Program.cs",$code);file_put_contents("$d/app.csproj",'<Project Sdk="Microsoft.NET.Sdk"><PropertyGroup><OutputType>Exe</OutputType><TargetFramework>'.dotnet_tfm().'</TargetFramework><ImplicitUsings>enable</ImplicitUsings><Nullable>disable</Nullable></PropertyGroup></Project>');
foreach(['DOTNET_NOLOGO=1','DOTNET_CLI_TELEMETRY_OPTOUT=1','DOTNET_SKIP_FIRST_TIME_EXPERIENCE=1','DOTNET_GENERATE_ASPNET_CERTIFICATE=false'] as $v)putenv($v);
$r=sh('"'.tool('dotnet').'" run --nologo -v q',$d,60);if(missing($r)||preg_match('/No \.NET SDKs were found|install a \.NET SDK/i',$r))$r="C# (.NET SDK) is not installed on this computer yet.\n\n".HOWTO;
else $r=cs_clean($r,$d);}
rrm($d);done(trim($r)===''?'(no output)':$r);
