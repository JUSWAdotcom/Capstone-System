/* CodeSmartAi-X built-in tutor. Works with no AI key.
   Used by the AI Assistant chat (page and Playground chat). */
const RULES=[[/SyntaxError|syntax error|expected|parse error|unexpected|CS1002|CS1513|CS1003/i,'This is a syntax error: a small typing mistake. Check the marked line and the line before it for a missing bracket, quote, colon or semicolon.'],[/IndentationError|unindent|expected an indented block/i,'Python uses spaces to show blocks. Every line inside if, for, while or def must be indented the same amount (4 spaces).'],[/NameError|is not defined|undeclared|was not declared|does not exist in the current context|CS0103|Undefined (variable|constant)|cannot find symbol/i,'A name is used before it exists. Check the spelling and capital letters, and create the variable before using it.'],[/TypeError|cannot concatenate|Unsupported operand|CS0029|CS0019|invalid operands|no match for/i,'Two different kinds of values are mixed. Convert first: str(), int(), Number(), int.Parse() or stoi().'],[/IndexError|out of range|out_of_bounds|ArgumentOutOfRange|Undefined (array key|offset)/i,'You asked for a position that does not exist. The first position is 0 and the last is length minus 1.'],[/KeyError/i,'That dictionary key does not exist. Check spelling and capitals, or use .get(key, default).'],[/ZeroDivision|divide by zero|DivideByZero/i,'You divided by zero. Check the divisor before dividing.'],[/Cannot read|NullReference|null reference/i,'A value is empty (null or undefined) when you use it. Make sure it has a value first.'],[/is not a function|not callable/i,'You called something that is not a function. Check the name and the brackets.'],[/Stopped|ran for over/i,'The program ran too long. Make sure every loop changes something so its condition becomes false.']];
const strip=s=>s.replace(/"(?:\\.|[^"\\\n])*"|'(?:\\.|[^'\\\n])*'/g,'""');
const localTips=(l,c,o,solve)=>{const t=[],lines=c.split('\n');
const m=[...o.matchAll(/line (\d+)|\.cpp:(\d+)|\.cs\((\d+),/gi)].pop(),ln=m?+(m[1]||m[2]||m[3]):0;
if(ln&&lines[ln-1]!==undefined)t.push('The error points to line '+ln+': '+lines[ln-1].trim());
RULES.forEach(([r,x])=>{if(r.test(o)&&t.length<5)t.push(x)});
if(l!=='html'){let d={'(':0,'[':0,'{':0};lines.forEach((x,i)=>{const s=strip(x.replace(/\/\/.*|#(?!include).*/,''));for(const ch of s){if(ch in d)d[ch]++;if(ch===')')d['(']--;if(ch===']')d['[']--;if(ch==='}')d['{']--}
if((x.match(/"/g)||[]).length%2&&!/^\s*(\/\/|#)/.test(x)&&!/'.*"/.test(x))t.push('Line '+(i+1)+' has a quote that is never closed.')});
if(d['(']>0)t.push('A ( is never closed with ).');if(d['(']<0)t.push('There is an extra ).');if(d['{']>0)t.push('A { is never closed with }.');if(d['{']<0)t.push('There is an extra }.');if(d['[']!==0)t.push('The square brackets [ ] do not match.')}
if(l==='python')lines.forEach((x,i)=>{const s=x.replace(/#.*/,'').trimEnd();if(/^\s*(if|elif|else|for|while|def|class|try|except|finally|with)\b/.test(s)&&!s.endsWith(':')&&!s.endsWith(',')&&!/[\(\[\{]$/.test(s))t.push('Line '+(i+1)+': add a colon ( : ) at the end of this line.');if(/^\s*print\s+["'\w]/.test(s))t.push('Line '+(i+1)+': in Python 3, print needs brackets: print(...).')});
if(['cpp','csharp','php','javascript'].includes(l))lines.forEach((x,i)=>{const s=strip(x.replace(/\/\/.*/,'')).trim();if(/^(if|while)\s*\(.*[^=!<>]=(?!=)[^=]/.test(s))t.push('Line '+(i+1)+': inside ( ) use == to compare. One = stores a value.');
if(l!=='javascript'&&s&&!/^(if|for|while|else|switch|class|namespace|using|#|public|private|function|do|try|catch|finally|case|default|<\?|\?>|\{|\}|\/\*|\*)/.test(s)&&!/[;{},(:]$/.test(s)&&!/^\w+\(.*\)\s*$/.test('')&&/[)\w"\]]$/.test(s))t.push('Line '+(i+1)+' may be missing a semicolon ( ; ) at the end.')});
if(l==='php'){lines.forEach((x,i)=>{if(/^\s*[a-z_]\w*\s*=[^=]/i.test(x)&&!/^\s*(const|define)/i.test(x))t.push('Line '+(i+1)+': PHP variables start with a dollar sign, for example $name.')});if(!/<\?php/.test(c))t.push('A PHP file should start with <?php.')}
if(l==='cpp'&&/\bcout\b/.test(c)&&!/#include\s*<iostream>/.test(c))t.push('cout needs #include <iostream> at the top.');
if(l==='javascript'&&/\bvar\s/.test(c))t.push('Use let or const instead of var. They are safer.');
if(l==='html'){const tg=['div','p','span','a','ul','ol','li','table','tr','td','th','h1','h2','h3','button','form','label','select','option','style','script','body','head','html','nav','header','footer','main','section'];tg.forEach(g=>{const a=(c.match(new RegExp('<'+g+'[\\s>]','gi'))||[]).length,b=(c.match(new RegExp('</'+g+'>','gi'))||[]).length;if(a!==b)t.push('There are '+a+' opening <'+g+'> tags but '+b+' closing </'+g+'> tags.')});
if(/<img(?![^>]*\balt=)/i.test(c))t.push('Every <img> needs an alt="..." text that describes the picture.');if(/colou?r\s*:/i.test(c)&&/colour\s*:/i.test(c))t.push('CSS spells it color, not colour.');if(/\{[^}]*:[^;}]*\}/.test(c.replace(/\n/g,' ')))t.push('A CSS rule may be missing the semicolon ( ; ) after a value.')}
if(typeof CH!=='undefined'&&CH&&o.trim()&&!CH.exp.startsWith('HTML:')){const a=o.replace(/\r/g,'').trim().split('\n'),b=CH.exp.split('\n');const i=a.findIndex((x,k)=>x!==b[k]);if(i>=0||a.length!==b.length){const k=i<0?Math.min(a.length,b.length):i;t.push('Your output differs on line '+(k+1)+'. Expected: "'+(b[k]??'(nothing)')+'" but you got: "'+(a[k]??'(nothing)')+'".')}}
if(!t.length)t.push(o&&!/error/i.test(o)?'No mistakes found by the checker. Compare each output line with what you expect.':'Press Run code first, then ask again so the tutor can read your output.');
return 'Built-in tutor (no AI key needed):\n'+[...new Set(t)].slice(0,6).map(x=>'- '+x).join('\n')+'\n- Next step: change one value and predict the output before you run it.'+(solve?'\n(Showing a full fix needs the online AI. Use the hints above and the Show hint button.)':'')};
/* ---------- built-in tutor chat: answers common questions with no AI key ---------- */
(()=>{
const LN={python:'Python',javascript:'JavaScript',cpp:'C++',csharp:'C#',php:'PHP',html:'HTML / CSS'};
const TOP={
print:{name:'Printing output',intro:'To **print** means to show something on the screen. Text goes inside **quotes**.',
code:{python:`print("Hello, World!")
print("My name is", "Ana")`,
javascript:`console.log("Hello, World!");
console.log("My name is", "Ana");`,
cpp:`#include <iostream>
using namespace std;

int main() {
    cout << "Hello, World!" << endl;
    cout << "My name is " << "Ana" << endl;
    return 0;
}`,
csharp:`Console.WriteLine("Hello, World!");
Console.WriteLine("My name is " + "Ana");`,
php:`<?php
echo "Hello, World!" . PHP_EOL;
echo "My name is " . "Ana" . PHP_EOL;`},
notes:{python:'print() starts a new line by itself.',javascript:'console.log() shows its result in the Output box.',cpp:'Every C++ program starts in int main(). endl starts a new line.',csharp:'Console.WriteLine starts a new line after the text. Console.Write does not.',php:'PHP code starts with <?php. echo shows text, and the dot ( . ) joins pieces of text.'}},
variables:{name:'Variables and data types',intro:'A **variable** is a named box that stores a value. The **data type** says what kind of value it holds: whole number, decimal number, text or true/false.',
code:{python:`name = "Ana"        # text (str)
age = 20            # whole number (int)
price = 9.5         # decimal number (float)
is_student = True   # true or false (bool)
print(name, age, price, is_student)`,
javascript:`let name = "Ana";      // text (string)
let age = 20;          // number
const PI = 3.14;       // const can never change
let isStudent = true;  // true or false (boolean)
console.log(name, age, PI, isStudent);`,
cpp:`#include <iostream>
#include <string>
using namespace std;

int main() {
    string name = "Ana";   // text
    int age = 20;          // whole number
    double price = 9.5;    // decimal number
    bool isStudent = true; // true or false
    cout << name << " " << age << " " << price << " " << isStudent << endl;
    return 0;
}`,
csharp:`string name = "Ana";    // text
int age = 20;           // whole number
double price = 9.5;     // decimal number
bool isStudent = true;  // true or false
Console.WriteLine(name + " " + age + " " + price + " " + isStudent);`,
php:`<?php
$name = "Ana";       // text (string)
$age = 20;           // whole number (int)
$price = 9.5;        // decimal number (float)
$isStudent = true;   // true or false (bool)
echo $name . " " . $age . " " . $price . PHP_EOL;`},
notes:{python:'Python works out the type for you. Variable names usually use small letters and underscores.',javascript:'Use let for values that change and const for values that never change.',cpp:'In C++ you must write the type before the name, and end every statement with a semicolon ( ; ).',csharp:'In C# you write the type before the name, and end every statement with a semicolon ( ; ).',php:'Every PHP variable starts with a dollar sign ( $ ).'}},
input:{name:'Reading input from the user',intro:'**Input** lets the program ask the user to type something. The value comes in as text, so convert it if you need a number.',
code:{python:`name = input("Your name: ")
age = int(input("Your age: "))   # int() turns the text into a number
print("Hello", name, "you are", age)`,
javascript:`// In a web page, prompt() opens a small box for typing
let name = prompt("Your name:");
let age = Number(prompt("Your age:"));   // Number() turns text into a number
console.log("Hello " + name + ", you are " + age);`,
cpp:`#include <iostream>
#include <string>
using namespace std;

int main() {
    string name;
    int age;
    cout << "Your name: ";
    cin >> name;
    cout << "Your age: ";
    cin >> age;
    cout << "Hello " << name << ", you are " << age << endl;
    return 0;
}`,
csharp:`Console.Write("Your name: ");
string name = Console.ReadLine();
Console.Write("Your age: ");
int age = int.Parse(Console.ReadLine());   // int.Parse turns text into a number
Console.WriteLine("Hello " + name + ", you are " + age);`,
php:`<?php
// In the terminal:
$name = readline("Your name: ");
echo "Hello " . $name . PHP_EOL;

// From an HTML form that uses method="post" and <input name="name">:
// $name = $_POST["name"];`},
notes:{python:'The Playground has no box for typing while the program runs, so practice with fixed values like name = "Ana" first.',javascript:'The Playground has no typing box, so practice with fixed values first.',cpp:'cin >> reads one word. The Playground has no typing box, so practice with fixed values first.',csharp:'The Playground has no typing box, so practice with fixed values first.',php:'The Playground has no typing box, so practice with fixed values first.'}},
math:{name:'Math operators',intro:'**Operators** do the math: + adds, - subtracts, * multiplies, / divides and % gives the **remainder** (what is left after dividing).',
code:{python:`a = 10
b = 3
print(a + b)    # 13
print(a - b)    # 7
print(a * b)    # 30
print(a / b)    # 3.3333333333333335
print(a // b)   # 3  (whole part only)
print(a % b)    # 1  (remainder)
print(a ** 2)   # 100 (power)`,
javascript:`let a = 10;
let b = 3;
console.log(a + b);               // 13
console.log(a - b);               // 7
console.log(a * b);               // 30
console.log(a / b);               // 3.3333333333333335
console.log(Math.floor(a / b));   // 3  (whole part only)
console.log(a % b);               // 1  (remainder)
console.log(a ** 2);              // 100 (power)`,
cpp:`#include <iostream>
using namespace std;

int main() {
    int a = 10;
    int b = 3;
    cout << a + b << endl;           // 13
    cout << a - b << endl;           // 7
    cout << a * b << endl;           // 30
    cout << a / b << endl;           // 3  (int divided by int gives a whole number)
    cout << a % b << endl;           // 1  (remainder)
    cout << (double)a / b << endl;   // 3.33333
    return 0;
}`,
csharp:`int a = 10;
int b = 3;
Console.WriteLine(a + b);            // 13
Console.WriteLine(a - b);            // 7
Console.WriteLine(a * b);            // 30
Console.WriteLine(a / b);            // 3  (int divided by int gives a whole number)
Console.WriteLine(a % b);            // 1  (remainder)
Console.WriteLine((double)a / b);    // 3.3333333333333335`,
php:`<?php
$a = 10;
$b = 3;
echo $a + $b . PHP_EOL;          // 13
echo $a - $b . PHP_EOL;          // 7
echo $a * $b . PHP_EOL;          // 30
echo $a / $b . PHP_EOL;          // 3.3333333333333
echo intdiv($a, $b) . PHP_EOL;   // 3  (whole part only)
echo $a % $b . PHP_EOL;          // 1  (remainder)
echo $a ** 2 . PHP_EOL;          // 100 (power)`},
tip:'Brackets work like in school math: (2 + 3) * 4 is 20, but 2 + 3 * 4 is 14.'},
cond:{name:'if / else decisions',intro:'An **if** statement runs code only when a **condition** is true. **else** runs when it is false. Use **==** to compare (one = stores a value).',
code:{python:`score = 85
if score >= 90:
    print("Excellent")
elif score >= 75:
    print("Good")
else:
    print("Keep practicing")`,
javascript:`let score = 85;
if (score >= 90) {
  console.log("Excellent");
} else if (score >= 75) {
  console.log("Good");
} else {
  console.log("Keep practicing");
}`,
cpp:`#include <iostream>
using namespace std;

int main() {
    int score = 85;
    if (score >= 90) {
        cout << "Excellent" << endl;
    } else if (score >= 75) {
        cout << "Good" << endl;
    } else {
        cout << "Keep practicing" << endl;
    }
    return 0;
}`,
csharp:`int score = 85;
if (score >= 90)
{
    Console.WriteLine("Excellent");
}
else if (score >= 75)
{
    Console.WriteLine("Good");
}
else
{
    Console.WriteLine("Keep practicing");
}`,
php:`<?php
$score = 85;
if ($score >= 90) {
    echo "Excellent" . PHP_EOL;
} elseif ($score >= 75) {
    echo "Good" . PHP_EOL;
} else {
    echo "Keep practicing" . PHP_EOL;
}`},
notes:{python:'Python uses a colon ( : ) and indentation (4 spaces) instead of curly brackets. Use and, or, not to combine conditions.',javascript:'Combine conditions with && (and), || (or) and ! (not).',cpp:'Combine conditions with && (and), || (or) and ! (not).',csharp:'Combine conditions with && (and), || (or) and ! (not).',php:'Combine conditions with && (and), || (or) and ! (not).'}},
sw:{name:'switch (many choices)',intro:'A **switch** compares one value against many choices. It is neater than a long chain of if / else.',
code:{python:`day = 3
match day:
    case 1:
        print("Monday")
    case 2:
        print("Tuesday")
    case 3:
        print("Wednesday")
    case _:
        print("Another day")`,
javascript:`let day = 3;
switch (day) {
  case 1:
    console.log("Monday");
    break;
  case 2:
    console.log("Tuesday");
    break;
  case 3:
    console.log("Wednesday");
    break;
  default:
    console.log("Another day");
}`,
cpp:`#include <iostream>
using namespace std;

int main() {
    int day = 3;
    switch (day) {
        case 1:
            cout << "Monday" << endl;
            break;
        case 2:
            cout << "Tuesday" << endl;
            break;
        case 3:
            cout << "Wednesday" << endl;
            break;
        default:
            cout << "Another day" << endl;
    }
    return 0;
}`,
csharp:`int day = 3;
switch (day)
{
    case 1:
        Console.WriteLine("Monday");
        break;
    case 2:
        Console.WriteLine("Tuesday");
        break;
    case 3:
        Console.WriteLine("Wednesday");
        break;
    default:
        Console.WriteLine("Another day");
        break;
}`,
php:`<?php
$day = 3;
switch ($day) {
    case 1:
        echo "Monday" . PHP_EOL;
        break;
    case 2:
        echo "Tuesday" . PHP_EOL;
        break;
    case 3:
        echo "Wednesday" . PHP_EOL;
        break;
    default:
        echo "Another day" . PHP_EOL;
}`},
tip:'Do not forget break (not needed in Python). Without it the code keeps running into the next case.'},
forl:{name:'for loop',intro:'A **for loop** repeats code a set number of times. This one counts from 1 to 5.',
code:{python:`for i in range(1, 6):   # 1, 2, 3, 4, 5
    print(i)`,
javascript:`for (let i = 1; i <= 5; i++) {
  console.log(i);
}`,
cpp:`#include <iostream>
using namespace std;

int main() {
    for (int i = 1; i <= 5; i++) {
        cout << i << endl;
    }
    return 0;
}`,
csharp:`for (int i = 1; i <= 5; i++)
{
    Console.WriteLine(i);
}`,
php:`<?php
for ($i = 1; $i <= 5; $i++) {
    echo $i . PHP_EOL;
}`},
notes:{python:'range(1, 6) stops BEFORE 6. Python starts counting at the first number you give and stops before the last.',javascript:'The three parts are: start (let i = 1), keep going while (i <= 5), and step (i++ adds 1).',cpp:'The three parts are: start (int i = 1), keep going while (i <= 5), and step (i++ adds 1).',csharp:'The three parts are: start (int i = 1), keep going while (i <= 5), and step (i++ adds 1).',php:'The three parts are: start ($i = 1), keep going while ($i <= 5), and step ($i++ adds 1).'}},
whl:{name:'while loop',intro:'A **while loop** keeps repeating as long as its condition is true. Make sure something inside the loop changes the condition, or it will never stop.',
code:{python:`count = 1
while count <= 5:
    print(count)
    count = count + 1   # without this line the loop never ends`,
javascript:`let count = 1;
while (count <= 5) {
  console.log(count);
  count = count + 1;   // without this line the loop never ends
}`,
cpp:`#include <iostream>
using namespace std;

int main() {
    int count = 1;
    while (count <= 5) {
        cout << count << endl;
        count = count + 1;   // without this line the loop never ends
    }
    return 0;
}`,
csharp:`int count = 1;
while (count <= 5)
{
    Console.WriteLine(count);
    count = count + 1;   // without this line the loop never ends
}`,
php:`<?php
$count = 1;
while ($count <= 5) {
    echo $count . PHP_EOL;
    $count = $count + 1;   // without this line the loop never ends
}`},
tip:'If the program runs forever, you made an infinite loop. Check that the condition can become false.'},
func:{name:'Functions',intro:'A **function** is a block of code with a name that you can use again and again. **Parameters** are the values you give it, and **return** sends a result back.',
code:{python:`def add(a, b):
    return a + b

result = add(2, 3)
print(result)   # 5`,
javascript:`function add(a, b) {
  return a + b;
}

let result = add(2, 3);
console.log(result);   // 5`,
cpp:`#include <iostream>
using namespace std;

int add(int a, int b) {
    return a + b;
}

int main() {
    int result = add(2, 3);
    cout << result << endl;   // 5
    return 0;
}`,
csharp:`int Add(int a, int b)
{
    return a + b;
}

int result = Add(2, 3);
Console.WriteLine(result);   // 5`,
php:`<?php
function add($a, $b) {
    return $a + $b;
}

$result = add(2, 3);
echo $result . PHP_EOL;   // 5`},
tip:'Defining a function does nothing by itself. You must call it, for example add(2, 3).'},
arr:{name:'Lists and arrays',intro:'A **list** (or **array**) stores many values in one variable. Each value has a position called an **index**, and the first index is **0**.',
code:{python:`fruits = ["apple", "banana", "mango"]
fruits.append("orange")      # add to the end
print(fruits[0])             # apple
print(len(fruits))           # 4
for f in fruits:
    print(f)`,
javascript:`let fruits = ["apple", "banana", "mango"];
fruits.push("orange");              // add to the end
console.log(fruits[0]);             // apple
console.log(fruits.length);         // 4
for (let f of fruits) {
  console.log(f);
}`,
cpp:`#include <iostream>
#include <vector>
#include <string>
using namespace std;

int main() {
    vector<string> fruits = {"apple", "banana", "mango"};
    fruits.push_back("orange");          // add to the end
    cout << fruits[0] << endl;           // apple
    cout << fruits.size() << endl;       // 4
    for (string f : fruits) {
        cout << f << endl;
    }
    return 0;
}`,
csharp:`using System.Collections.Generic;

List<string> fruits = new List<string> { "apple", "banana", "mango" };
fruits.Add("orange");                    // add to the end
Console.WriteLine(fruits[0]);            // apple
Console.WriteLine(fruits.Count);         // 4
foreach (string f in fruits)
{
    Console.WriteLine(f);
}`,
php:`<?php
$fruits = ["apple", "banana", "mango"];
$fruits[] = "orange";                    // add to the end
echo $fruits[0] . PHP_EOL;               // apple
echo count($fruits) . PHP_EOL;           // 4
foreach ($fruits as $f) {
    echo $f . PHP_EOL;
}`},
tip:'Asking for an index that does not exist (like fruits[10] when there are 4 items) causes an error or gives nothing.'},
dict:{name:'Dictionaries / key-value pairs',intro:'A **dictionary** (also called a **map** or an **associative array**) stores values by a **key** instead of a number, like a contact list: name -> phone.',
code:{python:`student = {"name": "Ana", "age": 20}
print(student["name"])        # Ana
student["age"] = 21           # change a value
for key in student:
    print(key, student[key])`,
javascript:`let student = { name: "Ana", age: 20 };
console.log(student.name);    // Ana
student.age = 21;             // change a value
for (let key in student) {
  console.log(key, student[key]);
}`,
cpp:`#include <iostream>
#include <map>
#include <string>
using namespace std;

int main() {
    map<string, int> ages;
    ages["Ana"] = 20;
    ages["Ben"] = 22;
    cout << ages["Ana"] << endl;   // 20
    for (auto p : ages) {
        cout << p.first << " " << p.second << endl;
    }
    return 0;
}`,
csharp:`using System.Collections.Generic;

Dictionary<string, int> ages = new Dictionary<string, int>();
ages["Ana"] = 20;
ages["Ben"] = 22;
Console.WriteLine(ages["Ana"]);   // 20
foreach (var p in ages)
{
    Console.WriteLine(p.Key + " " + p.Value);
}`,
php:`<?php
$ages = ["Ana" => 20, "Ben" => 22];
echo $ages["Ana"] . PHP_EOL;    // 20
$ages["Ana"] = 21;              // change a value
foreach ($ages as $name => $age) {
    echo $name . " " . $age . PHP_EOL;
}`}},
str:{name:'Strings (text)',intro:'A **string** is text. You can get its length, change its case, pick one character by index and replace parts of it.',
code:{python:`text = "Hello World"
print(len(text))                    # 11
print(text.upper())                 # HELLO WORLD
print(text.lower())                 # hello world
print(text[0])                      # H
print(text.replace("World", "Ana")) # Hello Ana
print("Hi " + "Ana")                # Hi Ana
print(text[::-1])                   # dlroW olleH (reversed)`,
javascript:`let text = "Hello World";
console.log(text.length);                 // 11
console.log(text.toUpperCase());          // HELLO WORLD
console.log(text.toLowerCase());          // hello world
console.log(text[0]);                     // H
console.log(text.replace("World", "Ana")); // Hello Ana
console.log("Hi " + "Ana");               // Hi Ana
console.log(text.split("").reverse().join(""));   // dlroW olleH (reversed)`,
cpp:`#include <iostream>
#include <string>
using namespace std;

int main() {
    string text = "Hello World";
    cout << text.length() << endl;        // 11
    cout << text[0] << endl;              // H
    text = text + "!";                    // join text with +
    cout << text << endl;                 // Hello World!
    text.replace(6, 5, "Ana");            // replace 5 letters from position 6
    cout << text << endl;                 // Hello Ana!
    return 0;
}`,
csharp:`string text = "Hello World";
Console.WriteLine(text.Length);                  // 11
Console.WriteLine(text.ToUpper());               // HELLO WORLD
Console.WriteLine(text.ToLower());               // hello world
Console.WriteLine(text[0]);                      // H
Console.WriteLine(text.Replace("World", "Ana")); // Hello Ana
Console.WriteLine("Hi " + "Ana");                // Hi Ana`,
php:`<?php
$text = "Hello World";
echo strlen($text) . PHP_EOL;                    // 11
echo strtoupper($text) . PHP_EOL;                // HELLO WORLD
echo strtolower($text) . PHP_EOL;                // hello world
echo $text[0] . PHP_EOL;                         // H
echo str_replace("World", "Ana", $text) . PHP_EOL; // Hello Ana
echo "Hi " . "Ana" . PHP_EOL;                    // Hi Ana`},
tip:'The first character is at index 0, not 1.'},
cls:{name:'Classes and objects',intro:'A **class** is a blueprint. An **object** is one thing made from that blueprint. A class holds **properties** (data) and **methods** (actions). The **constructor** sets up a new object.',
code:{python:`class Student:
    def __init__(self, name, age):   # the constructor
        self.name = name
        self.age = age

    def greet(self):                 # a method
        print("Hi, I am", self.name)

s = Student("Ana", 20)               # make an object
s.greet()                            # Hi, I am Ana`,
javascript:`class Student {
  constructor(name, age) {           // the constructor
    this.name = name;
    this.age = age;
  }
  greet() {                          // a method
    console.log("Hi, I am " + this.name);
  }
}

let s = new Student("Ana", 20);      // make an object
s.greet();                           // Hi, I am Ana`,
cpp:`#include <iostream>
#include <string>
using namespace std;

class Student {
public:
    string name;
    int age;
    void greet() {                   // a method
        cout << "Hi, I am " << name << endl;
    }
};

int main() {
    Student s;                       // make an object
    s.name = "Ana";
    s.age = 20;
    s.greet();                       // Hi, I am Ana
    return 0;
}`,
csharp:`Student s = new Student("Ana", 20);   // make an object
s.Greet();                             // Hi, I am Ana

class Student
{
    public string Name;
    public int Age;

    public Student(string name, int age)   // the constructor
    {
        Name = name;
        Age = age;
    }

    public void Greet()                    // a method
    {
        Console.WriteLine("Hi, I am " + Name);
    }
}`,
php:`<?php
class Student {
    public $name;
    public $age;

    public function __construct($name, $age) {   // the constructor
        $this->name = $name;
        $this->age = $age;
    }

    public function greet() {                    // a method
        echo "Hi, I am " . $this->name . PHP_EOL;
    }
}

$s = new Student("Ana", 20);                     // make an object
$s->greet();                                     // Hi, I am Ana`}},
err:{name:'Handling errors (try / catch)',intro:'**try** runs code that might fail. If it fails, the program does not crash: the **except / catch** part runs instead.',
code:{python:`try:
    number = int("abc")          # this fails
except ValueError:
    print("That is not a number")`,
javascript:`try {
  let data = JSON.parse("abc");  // this fails
} catch (error) {
  console.log("That is not valid JSON");
}`,
cpp:`#include <iostream>
#include <string>
using namespace std;

int main() {
    try {
        int number = stoi("abc");    // this fails
        cout << number << endl;
    } catch (const exception& e) {
        cout << "That is not a number" << endl;
    }
    return 0;
}`,
csharp:`try
{
    int number = int.Parse("abc");   // this fails
}
catch (FormatException)
{
    Console.WriteLine("That is not a number");
}`,
php:`<?php
try {
    echo intdiv(10, 0);              // this fails
} catch (DivisionByZeroError $e) {
    echo "You cannot divide by zero" . PHP_EOL;
}`},
tip:'Catch only the errors you expect, and show a friendly message.'},
rec:{name:'Recursion',intro:'**Recursion** means a function calls **itself** to solve a smaller piece of the problem. It always needs a **base case**, a point where it stops calling itself.',
code:{python:`def factorial(n):
    if n <= 1:                    # base case: stop here
        return 1
    return n * factorial(n - 1)   # the function calls itself

print(factorial(5))   # 120`,
javascript:`function factorial(n) {
  if (n <= 1) {                    // base case: stop here
    return 1;
  }
  return n * factorial(n - 1);     // the function calls itself
}

console.log(factorial(5));   // 120`,
cpp:`#include <iostream>
using namespace std;

int factorial(int n) {
    if (n <= 1) {                  // base case: stop here
        return 1;
    }
    return n * factorial(n - 1);   // the function calls itself
}

int main() {
    cout << factorial(5) << endl;  // 120
    return 0;
}`,
csharp:`int Factorial(int n)
{
    if (n <= 1)                    // base case: stop here
    {
        return 1;
    }
    return n * Factorial(n - 1);   // the function calls itself
}

Console.WriteLine(Factorial(5));   // 120`,
php:`<?php
function factorial($n) {
    if ($n <= 1) {                 // base case: stop here
        return 1;
    }
    return $n * factorial($n - 1); // the function calls itself
}

echo factorial(5) . PHP_EOL;       // 120`},
tip:'Without a base case the function calls itself forever and the program crashes.'},
com:{name:'Comments',intro:'A **comment** is a note for humans. The computer ignores it. Use comments to explain your code.',
code:{python:`# This is a one-line comment
print("Hi")   # a comment after code

"""
This is a longer note
over many lines.
"""`,
javascript:`// This is a one-line comment
console.log("Hi");   // a comment after code

/* This is a longer note
   over many lines. */`,
cpp:`// This is a one-line comment
#include <iostream>
using namespace std;

int main() {
    cout << "Hi" << endl;   // a comment after code

    /* This is a longer note
       over many lines. */
    return 0;
}`,
csharp:`// This is a one-line comment
Console.WriteLine("Hi");   // a comment after code

/* This is a longer note
   over many lines. */`,
php:`<?php
// This is a one-line comment
# This is also a one-line comment
echo "Hi" . PHP_EOL;   // a comment after code

/* This is a longer note
   over many lines. */`}}
};
/* HTML and CSS topics */
const HT={
page:{name:'The basic structure of a web page',intro:'**HTML** builds the structure of a web page with **tags**. Most tags come in pairs: an opening tag and a closing tag with a slash.',
code:`<!DOCTYPE html>
<html>
<head>
  <title>My first page</title>
</head>
<body>
  <h1>Hello, World!</h1>
  <p>This is my first web page.</p>
</body>
</html>`,tip:'What you see on the page goes inside <body>. The <title> shows in the browser tab.'},
text:{name:'Headings and paragraphs',intro:'Use **h1** to **h6** for headings (h1 is the biggest) and **p** for paragraphs.',
code:`<h1>Main heading</h1>
<h2>Smaller heading</h2>
<p>This is a paragraph with <b>bold</b> and <i>italic</i> text.</p>
<p>Line one<br>Line two</p>`,tip:'<br> makes a new line and has no closing tag.'},
link:{name:'Links',intro:'The **a** (anchor) tag makes a link. The **href** attribute holds the address.',
code:`<a href="https://www.example.com">Visit Example</a>
<a href="page2.html">Go to page 2</a>
<a href="https://www.example.com" target="_blank">Open in a new tab</a>`,tip:'The text between <a> and </a> is what the user clicks.'},
img:{name:'Images',intro:'The **img** tag shows a picture. **src** is the file name and **alt** describes the picture (it helps screen readers and shows if the picture is missing).',
code:`<img src="photo.png" alt="A photo of my cat" width="200">`,tip:'img has no closing tag. Always write the alt text.'},
list:{name:'Lists',intro:'Use **ul** for a bullet list and **ol** for a numbered list. Every item is an **li**.',
code:`<ul>
  <li>Apples</li>
  <li>Bananas</li>
</ul>

<ol>
  <li>Wake up</li>
  <li>Code</li>
</ol>`,tip:'Close the list with the same kind of tag you opened (</ul> for <ul>).'},
table:{name:'Tables',intro:'A **table** has rows (**tr**). A row holds heading cells (**th**) or normal cells (**td**).',
code:`<table border="1">
  <tr>
    <th>Name</th>
    <th>Age</th>
  </tr>
  <tr>
    <td>Ana</td>
    <td>20</td>
  </tr>
</table>`,tip:'Close every cell with its own tag (</td>), not with </tr>.'},
form:{name:'Forms and buttons',intro:'A **form** collects input from the user. The **input** tag changes with its **type**: text, password, checkbox and more.',
code:`<form>
  <label>Name: <input type="text" name="name" placeholder="Your name"></label>
  <label>Password: <input type="password" name="pw"></label>
  <input type="checkbox" name="agree"> I agree
  <button type="submit">Send</button>
</form>`,tip:'type="submit" is spelled with a b, not "sumbit".'},
css:{name:'CSS colors and fonts',intro:'**CSS** styles the page. A rule has a **selector** (what to style) and **properties** with values inside curly brackets. End each property with a semicolon.',
code:`<style>
  h1 {
    color: navy;
    font-family: Arial, sans-serif;
  }
  p {
    color: gray;
    font-size: 18px;
    background-color: lightyellow;
  }
</style>
<h1>Styled heading</h1>
<p>Styled paragraph</p>`,tip:'CSS spells it color (American spelling), not colour.'},
sel:{name:'CSS classes and ids',intro:'A **class** can be used on many elements and starts with a **dot** in CSS. An **id** is for one element and starts with **#**.',
code:`<style>
  .card { background-color: lightblue; }   /* class: a dot */
  #title { color: red; }                    /* id: a hash */
</style>
<h1 id="title">My title</h1>
<div class="card">Card one</div>
<div class="card">Card two</div>`,tip:'In the HTML you write class="card" and id="title" without the dot or the hash.'},
box:{name:'The CSS box model (margin, padding, border)',intro:'Every element is a box. **padding** is the space inside, **border** is the line around it and **margin** is the space outside.',
code:`<style>
  .box {
    width: 200px;
    padding: 20px;
    border: 2px solid black;
    margin: 10px;
    background-color: lightgreen;
  }
</style>
<div class="box">Padding is inside, margin is outside.</div>`,tip:'Do not put a space between the number and the unit: 10px, not 10 px.'},
flex:{name:'Layout with flexbox',intro:'**Flexbox** puts items in a row or a column and spaces them. Add display: flex to the parent.',
code:`<style>
  .row {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .row div { background-color: lightblue; padding: 10px; }
</style>
<div class="row">
  <div>One</div>
  <div>Two</div>
  <div>Three</div>
</div>`,tip:'justify-content spaces items along the row. align-items lines them up the other way. flex-direction: column stacks them.'},
com:{name:'Comments in HTML and CSS',intro:'A **comment** is a note the browser ignores.',
code:`<!-- This is an HTML comment -->
<style>
  /* This is a CSS comment */
  p { color: red; }
</style>
<p>Hello</p>`,tip:'HTML comments start with <!-- and end with -->.'}
};
const WHAT={python:'**Python** is a beginner-friendly language with short, readable code. It is used for scripts, data and AI.',javascript:'**JavaScript** makes web pages interactive. It runs in the browser and also on servers.',cpp:'**C++** is a fast, powerful language used for games, systems and programs that need speed. You must declare types and compile it.',csharp:'**C#** (say "C sharp") is a language by Microsoft used for desktop apps, games (Unity) and web services.',php:'**PHP** is a language that runs on the server. It builds web pages and talks to databases such as MySQL. This system is written in PHP.',html:'**HTML** builds the structure of a web page (headings, paragraphs, links, images). **CSS** styles it with colors, fonts and layout. They are markup and style languages, not programming languages.'};
const LANGRX=[['cpp',/c\+\+|\bcpp\b|\bcout\b|\bcin\b|std::/],['csharp',/c#|\bcsharp\b|c sharp|\.net\b|writeline/],['php',/\bphp\b|\$_(get|post)\b/],['python',/\bpython\b|\bpy\b|\.py\b|pyodide/],['javascript',/\bjavascript\b|\bjs\b|node\.?js|\.js\b|console\.log/],['html',/\bhtml\b|\bcss\b|web ?page|stylesheet|\bdiv\b|\bpadding\b|\bmargin\b|\bflexbox\b|\bhref\b|\bhyperlink\b|\bbox model\b/]];
const detect=t=>{for(const [l,r] of LANGRX)if(r.test(t))return l;return null};
const guess=c=>/<\?php|\$\w+\s*=|\becho\b/.test(c)?'php':/#include|\bcout\b|std::/.test(c)?'cpp':/Console\.|using System|static void|\bstring\s+\w+\s*=/.test(c)?'csharp':/<(html|div|p|h[1-6]|body|span|a|ul|img|style)\b|\{[^}]*:[^}]*;/i.test(c)?'html':/\bconsole\.|\blet\b|\bconst\b|=>|\bfunction\b/.test(c)?'javascript':/\bdef\b|\bprint\(|\belif\b|\bimport\b|:\s*$/m.test(c)?'python':null;
const looks=l=>/[;{}=()<>\[\]]|^\s*(def|if|elif|else|for|while|class|print|echo|cout|cin|console|import|using|function|return|#include)\b/.test(l);
const pasted=t=>{const f=[...t.matchAll(/```[A-Za-z+#]*\n?([\s\S]*?)```/g)].map(m=>m[1].trim()).filter(Boolean);if(f.length)return f.join('\n');
const L=t.split('\n');if(L.length<2)return '';const ix=L.map((l,i)=>looks(l)?i:-1).filter(i=>i>=0);if(ix.length<2)return '';return L.slice(ix[0],ix[ix.length-1]+1).join('\n')};
const UNSUP=/\b(java|ruby|golang|rust|swift|kotlin|typescript|perl|sql|dart|scala|matlab|assembly)\b/;
const SPECIAL=[
[/^(hi|hello|hey|good (morning|afternoon|evening)|yo)\b[\s!.,]*$/,'Hi! I am your tutor. Ask me about **Python**, **JavaScript**, **HTML/CSS**, **PHP**, **C++** or **C#**, for example "How do I write a for loop in C++?". You can also paste your code and I will check it for typical mistakes.'],
[/^(thanks|thank you|ty|salamat)\b/,'You are welcome! Ask me anything else about your code.'],
[/who are you|what can you do|what do you know|are you (claude|gemini|gpt|chatgpt|an? ai|real)|your name|^help[\s?!.]*$/,'I am the built-in tutor of CodeSmartAi-X. I explain the basics of **Python**, **JavaScript**, **HTML/CSS**, **PHP**, **C++** and **C#** with short examples (printing, variables, input, math, if/else, loops, functions, lists, dictionaries, strings, classes, comments, switch, errors) and I check pasted code for typical mistakes. The full AI Assistant answers here too when your teacher switches it on.'],
[/difference between (a )?(list|lists) and (an )?(array|arrays)|(array|arrays) (vs|versus|or) (list|lists)|(list|lists) (vs|versus|or) (array|arrays)/,'An **array** is an ordered group of values, and each value has an **index** that starts at 0. In **C++** and **C#**, a plain array has a fixed size, while a **list** (C#: List, C++: vector) can grow and shrink. **Python** calls its collection a **list**, and **JavaScript** and **PHP** call theirs an **array**, but in these three languages they can all grow, so they work like lists.\n```python\nnumbers = [10, 20, 30]   # Python list\nnumbers.append(40)       # it can grow\n```'],
[/\b(for|while)\b.{0,20}\b(vs|versus|or|and)\b.{0,20}\b(for|while)\b|difference between (a )?(for|while)\b/,'Use a **for loop** when you know how many times to repeat (for example 10 times, or once for each item in a list). Use a **while loop** when you repeat until something changes (for example, until the user types the right password). Both can do the same job, but for is shorter when counting.'],
[/difference between\s+(=|==|=\s*and\s*==|assignment)|(=|==)\s*(vs|versus|or|and)\s*(==|=)|single equal|double equal/,'One equals sign ( **=** ) **stores** a value in a variable: x = 5 puts 5 into x. Two equals signs ( **==** ) **compare** two values and give true or false: x == 5 asks "is x equal to 5?". Use == inside an if. In JavaScript and PHP, === compares the value and the type.'],
[/(print|show|output).{0,12}nothing|nothing (is )?(print|show|display|happen|appear)|no output|blank (screen|output|page)|why.{0,20}(not|n.t) (show|print|display)/,null]
];
const NOTHING=l=>'Common reasons your code shows nothing:\n1. You did not press **Run code**.\n2. There is no output command: '+({python:'**print()**',javascript:'**console.log()**',php:'**echo**',cpp:'**cout**',csharp:'**Console.WriteLine()**',html:'**text inside the body tags**'}[l]||'a print command')+'.\n3. The output line is inside an **if** that is false, or inside a **function** that you never **call**.\n4. A **loop** whose condition is false at the start never runs its body.\n5. A mistake earlier in the code stops the program, so read the Output box for an error message.\nPaste your code here and tell me what you expected, and I will look for the problem.';
const TOPICS=[
['rec',/\brecurs\w+/],
['cls',/\bclass(es)?\b|\bobjects?\b|\boop\b|\bconstructor\b|__init__|\binheritance\b/],
['err',/\b(try|catch|except|exceptions?|error handling|throw)\b|\bhandl\w+ (the |an? )?errors?\b|\berrors? handl\w+/],
['whl',/\bwhile\b|\binfinite\b/],
['forl',/\bloops?\b|\bfor[ -]?loops?\b|\biterat\w+|\brepeat\w*|\bforeach\b|\bfor each\b|\brange\(|\bfor\s*\(|\bfor \w+ in\b/],
['func',/\bfunctions?\b|\bdef\b|\breturn(s|ing)?\b|\bparameters?\b|\barguments?\b|\bcall(ing)? a\b/],
['dict',/\bdictionar(y|ies)\b|\bhash ?maps?\b|\bkey.?value\b|\bassociative\b|\bmaps?\b|\bjson\b|\bobject literal\b/],
['arr',/\barrays?\b|\blists?\b|\bvectors?\b|\bappend\b|\bpush\b|\bcollections?\b|\bindex(es)?\b/],
['str',/\bstrings?\b|\buppercase\b|\blowercase\b|\bsubstring\b|\bconcatenat\w*|\bjoin text\b|\blength of\b/],
['sw',/\bswitch\b|\bmatch[- ](case|statement)\b|\bcase statement\b/],
['cond',/\bif\b|\belse\b|\belif\b|\bconditions?\b|\bdecisions?\b|\bcompar\w+|\bboolean logic\b/],
['input',/\binputs?\b|\bkeyboard\b|\bask the user\b|\breadline\b|\bprompt\b|\bscanf\b|\buser types\b|\bread (a )?(value|number|name)\b/],
['math',/\bmath\b|\boperators?\b|\badd(ing)?\b|\bsubtract\w*|\bdivi(de|sion)\b|\bmultipl\w+|\bmodulo\b|\bremainder\b|\bpower\b|\bcalculat\w+|\barithmetic\b|\bplus\b|\bminus\b/],
['com',/\bcomments?\b/],
['print',/\bprint(ing)?\b|\bhello world\b|\becho\b|\bcout\b|\bconsole\.?log\b|\bwriteline\b|\b(show|display) (text|a message|something)\b/],
['variables',/\bvariables?\b|\bdata ?types?\b|\bdeclare\b|\bassign(ment)?\b|\binteger\b|\bfloat\b|\bdouble\b|\bboolean\b|\bconstants?\b/]];
const HTOPICS=[['page',/structure|skeleton|boilerplate|basic page|doctype|first (web ?)?page|html page|\bhead\b|\bbody\b/],['link',/\blinks?\b|\banchor\b|\bhref\b|<a\b/],['img',/\bimages?\b|\bimg\b|\bpictures?\b|\bphotos?\b/],['table',/\btables?\b/],['list',/\blists?\b|\bul\b|\bol\b|\bbullets?\b|\bnumbered\b/],['form',/\bforms?\b|\binputs?\b|\bbuttons?\b|\bcheckbox\b|\btext ?box\b|\bsubmit\b/],['flex',/\bflex(box)?\b|\bcenter\b|\bcentre\b|\balign\b|\blayout\b|\bgrid\b|\brow\b|\bcolumns?\b/],['box',/\bmargin\b|\bpadding\b|\bborder\b|\bbox model\b|\bspacing\b/],['sel',/\bclass(es)?\b|\bids?\b|\bselectors?\b/],['css',/\bcolou?rs?\b|\bfonts?\b|\bbackground\b|\bstyles?\b|\bcss\b|\btext-align\b|\bsize\b/],['com',/\bcomments?\b/],['text',/\bheadings?\b|\bparagraphs?\b|\bh[1-6]\b|\btext\b|\bbold\b|\bitalic\b|\bprint(ing)?\b|\boutput\b|\bdisplay\b|\bshow\b/]];
const DEBUGRX=/\b(wrong|error|bug|bugs|fix|fixing|broken|not working|doesn.?t work|does not work|debug|mistake|mistakes|crash|fail(s|ed|ing)?|check my|look at my|what.?s wrong)\b/;
const LIST='**printing, variables, input, math, if/else, switch, loops, functions, lists/arrays, dictionaries, strings, classes, comments and errors**';
const HLIST='**page structure, headings, links, images, lists, tables, forms, colors and fonts, classes and ids, the box model and flexbox**';
const render=(key,lang)=>{let l=lang,note='',t,code,notes;
if(lang==='html'){t=HT[key];return '**'+t.name+'**\n'+t.intro+'\n```html\n'+t.code+'\n```\n'+(t.tip?'Tip: '+t.tip:'')}
t=TOP[key];if(!t)return '';code=t.code[l];notes=t.notes&&t.notes[l];
return '**'+t.name+' in '+LN[l]+'**\n'+t.intro+'\n```'+l+'\n'+code+'\n```\n'+(notes?notes+' ':'')+(t.tip?'Tip: '+t.tip:'')};
const answer=(raw,ctx)=>{ctx=ctx||{};const text=String(raw||'').trim(),low=text.toLowerCase();
if(!text)return {text:'Type your question first.',lang:null};
const pc=pasted(text),prose=low.replace(/```[\s\S]*?```/g,' ').replace(pc?pc.toLowerCase():'\u0000',' '),said=detect(prose),code=pc||(ctx.code||'').trim();
let lang=said||(!pc&&ctx.lang)||guess(pc)||ctx.lang||ctx.last||null;
const out=ctx.output||'';
const un=prose.match(UNSUP);if(un&&!said)return {text:'I only know **Python**, **JavaScript**, **HTML/CSS**, **PHP**, **C++** and **C#**, so I cannot answer about '+un[1]+'. Ask the same question for one of these, for example "how do I declare a variable in C#?".',lang:null};
for(const [rx,msg] of SPECIAL)if(rx.test(prose)){return {text:msg===null?NOTHING(lang||'python'):msg,lang:said||null}}
const wm=prose.match(/what (is|are) (a |an )?(python|javascript|js|php|c\+\+|cpp|c#|csharp|html|css)\b/);
if(wm){const k=detect(wm[3]);return {text:WHAT[k]+'\nAsk me for an example, for example "how do I write a loop in '+LN[k]+'?"',lang:k}}
let top=null;const isHtml=lang==='html';
if(isHtml){for(const [k,r] of HTOPICS)if(r.test(prose)){top=k;break}}
if(!top)for(const [k,r] of TOPICS)if(r.test(prose)){top=k;break}
const wantsCheck=DEBUGRX.test(prose)&&!/\bhow (do|can|to)\b/.test(prose);
if(wantsCheck&&!code)return {text:'To check your code I need to read it.\nPaste your code here (or use the chat in the Playground, which sends your code for you) and tell me what you expected and what happened. I look for missing brackets, quotes, colons, semicolons and misspelled words.',lang:said||null};
if(code&&!top&&!wantsCheck&&/explain|what does|meaning|understand|line by line/.test(prose))return {text:'Explaining your own code line by line is a job for the full AI Assistant (it works when your teacher switches it on). Meanwhile, ask me about the idea you are unsure of, for example "what is a for loop?", and I will show a small example. You can also ask me to **check my code** for mistakes.',lang:said||null};
if(code&&(wantsCheck||!top)){const cl=said||ctx.lang||guess(code)||lang||'python';let tips=localTips(cl,code,out,false).split('\n');tips.shift();
const body=tips.join('\n');
const noFind=/No mistakes found|Press Run code first/.test(body);
return {text:noFind?'I read your **'+LN[cl]+'** code and found no typical mistake (missing brackets, quotes, colons, semicolons or misspelled keywords).\n'+(out?'Compare each line of your output with what you expect, and tell me which line is different.':'Run it, then tell me what you expected and what you got, and I will help you find the difference.'):'Here is what I found in your **'+LN[cl]+'** code:\n'+body,lang:said||(ctx.lang?null:cl)}}
if(top){let l=lang||'python',pre='';
if(l==='html'&&!HT[top]){if(top==='com'){return {text:render('com','html'),lang:'html'}}
if(top==='print'){return {text:render('text','html'),lang:'html'}}
pre='HTML and CSS only describe how a page looks, so they have no '+({cls:'classes',err:'try / catch',whl:'while loops',forl:'loops',func:'functions',dict:'dictionaries',arr:'lists',str:'string commands',sw:'switch',cond:'if / else',input:'keyboard input',math:'math operators',variables:'variables',com:'comments'}[top]||'such thing')+'. **JavaScript** adds this to a page, so here it is in JavaScript:\n';l='javascript'}
if(l==='html'&&HT[top])return {text:render(top,'html'),lang:said||null};
const r=render(top,l);let extra='';
if(!said&&!ctx.lang&&!ctx.last)extra='\n(I showed '+LN[l]+'. Add a language name to your question, for example "in C++", to see another one.)';
return {text:pre+r+extra,lang:said||null}}
return {text:'I am not sure about that one. The built-in tutor knows the basics: '+(isHtml?HLIST:LIST)+'. Try a question like "How do I write a for loop in C++?" or paste your code and I will check it for typical mistakes. The full AI Assistant can answer any question when your teacher switches it on.',lang:said||null}};
window.Tutor={answer,topics:TOP,html:HT,names:LN};
})();
