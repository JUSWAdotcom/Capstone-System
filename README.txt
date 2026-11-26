CodeSmartAi-X  (version 48, built on version 25)

VERSION 48  (full double check: about 300 automatic checks of every way to use the system)
  Checked from start to end on a test copy that uses the real 25 tables: sign up (student and teacher), the email code
  (wrong code, new code, change email, cancel, 30 minutes, 5 tries, two people with the same ID), approval and decline,
  log in (wrong password, lock after 5, email instead of ID, spaces, capitals), log out, forgot password, change password,
  admin step 2 (also with a broken Gmail App password), admin idle log out, who may open which page, classes (make, edit,
  new join code, join, leave, remove, archive, delete), quizzes (paste questions, times, take, confirm, result, tab
  leaving, deadline submit), code exams (make, run, submit, time up, grade, picture of the output), playground files,
  running every language, challenges, lessons, AI assistant, notifications, grades, new school year and undo, every
  admin page, every link of every role, and phone size. Fixed:
  - A WHOLE CLASS ON ONE INTERNET CONNECTION: when the site is on the internet, all students of a school share one
    IP address. The limits for one computer were too small for a class (only 10 sign-up codes and 50 wrong passwords
    per 15 minutes for the whole school, and 5 wrong CLASS codes blocked every sign-up of the school for 15 minutes).
    Now: 100 codes, 300 wrong passwords, 50 wrong class codes per network (Logging/security.php IP_ limits), wrong
    class codes are counted per student too and no longer block sign-ups. The limits for ONE account stay strict.
  - The Student ID box of the dashboard ("Set your Student ID") and Admin > Security had a check that new Chrome and
    Edge reject (console error, the check was skipped). Fixed.
  - Error messages after a redirect (for example "This class has quizzes, so it cannot be deleted", "the deadline must
    be after the time it opens", "Too many wrong codes, so no account was made") were shown in GREEN like a success.
    They are red now. The dashboard now says when a Student ID could not be saved (already used, wrong format).
  - Python without internet showed a browser error ("Failed to execute importScripts..."). Now it explains that Python
    downloads itself into the browser the first time, so that computer needs the internet once.
  - Pressing Run again within 2 seconds (PHP, C++, C#, Java) gave "Please wait a moment between runs". Now it waits that
    moment and runs. While a program runs, the other pages of that student no longer wait for it.
  - The log in box says "Student ID / Teacher ID" (teachers and the admin use the same box), "Forgot password" too.
  - SAFETY OF THE CODE RUNNER (found by an attack test): C++, Java and C# run as real programs on the computer that runs
    CodeSmartAI-X, so a student could read config.php (the AI key), delete files or shut the computer down. Now a program that
    starts other programs (system(), Runtime.exec, Process.Start...), uses the Windows system, includes files from other folders or
    opens files outside its own folder (C:\..., ../, xampp, config.php) is NOT run, with a clear message. Normal programs,
    system("pause") / system("cls") and simple file names like "data.txt" still work (checked with all 400 C++ / C# programs of
    the lessons and challenges: none is blocked). PHP was already locked in its own folder. It is a basic guard, not a perfect
    sandbox: keep backups of the server computer; on a public website these languages stay off (PUBLIC_SITE).
  - The AI Assistant no longer shows which AI company or model answers (no "Gemini", "GPT" or "Claude" on any page):
    it is always called "AI Assistant", the counter says "30 AI questions left today", and if the AI is busy it says
    "The AI Assistant is busy right now" (no model names or Google error text). The AI is also told to introduce itself as
    "the CodeSmartAI X AI Assistant" if a student asks who it is. Only config.php and this README still name the AI service.
  - C# compile errors are short now: "Program.cs(1,39): error CS1002: ; expected" (before: the long hidden temporary folder
    path, and every error twice). Checked on the group laptop with .NET SDK 10: Hello, lists, classes, errors, safety block.
  - MANY STUDENTS AT THE SAME MOMENT: logging in no longer locks the wrong-password table for everybody (it is only cleaned when
    needed), and a submitted quiz is saved in one step (score and all answers together, all or nothing).
  - Attack test: SQL injection in every form and page address, scripts typed into names / classes / quizzes / code / feedback,
    forms sent from other websites (all 30 form pages refuse them), opening other people's work by changing the id, signing up as
    "admin", a GIF with hidden PHP, SVG and fake pictures, a stolen session id after log out: all refused.
  - Edge (laptop and full HD screens), Android phones, iPhone (no full screen there: the quiz still works and records only
    leaving the tab) and iPad: log in by tapping, the menu, every page without sideways scrolling, quiz, Run and code exam.

VERSION 47
  - ALL 8 LANGUAGES RUN: Java, C++ and C# need a program on the computer that runs CodeSmartAI-X (Python,
    JavaScript and HTML / CSS run in the browser, PHP comes with XAMPP). New: Launcher\Install languages.bat
    downloads Java (JDK 21), C++ (g++, WinLibs MinGW-w64) and C# (.NET SDK, LTS) into C:\xampp\langs, tests them
    and shows the result. The system finds them there by itself (config.php: JAVA 'java', GPP 'g++', DOTNET
    'dotnet', DOTNET_TFM 'auto'). C++ programs are built with -static so they run without extra DLLs.
    If a language is still missing, the OUTPUT says so in plain words instead of "'java' is not recognized".
    Students never install anything.
  - NEW FILE / RENAME / DELETE in the Playground (and the web project exam) open a designed dialog instead of the
    browser's "localhost says" box: language buttons (Python, JavaScript, HTML, CSS, PHP, C++, C#, Java) fill in
    the file type, the file name is checked while typing, and errors show inside the dialog. Upload, Download and
    the challenge "Start again" use the same dialogs.
  - CHANGE PASSWORD needs an email code first: "Send me a code" emails a 6-digit code (15 minutes, 5 tries, a new
    one once a minute). Then the student types the code, the old password and the new password twice.
    Without email set up, students see that email is not set up yet; the admin sees the code on the screen
    (so the admin can still change the first password and then set up Admin > Email).
  - FULL VIEW of OUTPUT / PREVIEW (the expand button at the top right of the output): it no longer uses the browser's
    full screen. In a quiz or code exam the page is already in full screen, so the old button LEFT full screen and the
    student got "Left the page". Now the output / preview fills the editor window and the title bar with the timer,
    Run and Submit stays on top. "Exit full view" (or Esc) goes back. Outside exams (Playground, challenges) it also
    asks the browser for full screen. Checked in every language (Python, JavaScript, HTML, web project, PHP, C++,
    C#, Java), the Playground, challenges, phone size and the quiz.
  - DATABASE CLEAN-UP: things that are not used any more are removed. An existing database is cleaned BY ITSELF the
    first time a page of version 47 opens (nothing to import): the table schema_migrations, the columns
    users.extra_years, quizzes.years and coding_exams.years (irregular students join classes since version 41), and
    old integrity records of blocked actions (copy, paste, right-click: they cannot happen since version 45).
    The database folder now has ONE clean file, database/capstone_db.sql (same as capstone_db_v47.sql), instead of
    the 13 step files and the old fresh copy. It is SAFE: it makes only what is missing and fills lessons and
    challenges only into empty tables. It never deletes accounts, classes, grades or the email setup, so it can be
    imported again at any time (no 'Table already exists' error). Setup/install.php runs the same file. Setup/install.php imports it when the
    database is empty. 25 tables.
  - SIGN UP: the account is made ONLY after the emailed code is typed. Before, the account was saved at once and waited
    as "not verified". Now the form waits in the session, a code is emailed, and "Verify and create my account" makes it.
    No code = no account. The code works 30 minutes (5 tries, a new one once a minute); the email can be changed and the
    sign-up cancelled on the same page. When email is not set up, the sign-up page says so and makes no account.
    Old unfinished sign-ups are removed by themselves (and when they try to log in they are told to sign up again).
  - ADMIN STEP 2 without email: the code is on its own line (it covered the text), and the page no longer says
    "sent to ..." when nothing was sent. The Email page and the admin dashboard no longer say "students see the code on
    the page" (that stopped in version 45).
  - SIGN-UP FORM: "I am a" (Student / Teacher) is the first box. Students see Year level; teachers see Teacher ID and
    Teacher code instead (a teacher never has to pick a year). After a mistake, the name, ID, email, role and year stay
    filled in (only the passwords and codes are typed again).
  - EMAIL THAT DOES NOT WORK is now found at once: Save in Admin > Email logs in to Gmail and says "Gmail accepted" or,
    in red, "BUT IT DOES NOT WORK" with the reason (for example a wrong App password). The admin dashboard shows a red
    warning while the email fails. The admin is never locked out: when the step-2 code (or the change-password code)
    cannot be emailed, it is shown on the screen with the reason. A student who signs up while the email is broken is
    told that no account was made.
  - IMPORTANT: importing a database that drops the tables also deletes the email setup (Admin > Email is saved in the
    database). database/capstone_db.sql never drops them any more.
  - Press Ctrl+F5 once in the browser after copying (files end in ?v=47.2).


VERSION 46
  - SIGN UP: there is a second box "Type the password again". If the two passwords are not the same, the account is
    not made and the student sees "The two passwords are not the same".
  - FORGOT PASSWORD: on the "Your code" step there is a "Send a new code" button (for a code that did not arrive or
    expired). It emails a new code to the same Gmail; the old code stops working. It can be used once a minute.
  - EMAILS: the CodeSmartAI-X logo picture is inside every email (top left), and the school name is removed. The
    bottom of each email says "CodeSmartAI-X - Learn. Practice. Code."
  - ONE EYE in password boxes: Microsoft Edge added its own eye button next to ours. Edge's eye is now hidden, so
    there is only one.
  - CODE COLORS in every module lesson: the code examples are colored like Visual Studio Code for Python, JavaScript,
    HTML (with its CSS and JavaScript), CSS, PHP, C++, C# and Java. The "Output" boxes stay plain.
  - QUIZ AND CODE EXAM FULL SCREEN: while a quiz or code exam is running, the side menu and top bar are hidden, so
    only the exam shows on the screen. They come back when the work is submitted.
  - LIGHT MODE: text boxes are white with a darker border, placeholder text is darker, and cards, tables and
    buttons have clearer edges. The Playground / Challenge / Code exam editor stays dark in light mode so the code
    is easy to read.
  - ONE-CLICK LAUNCHER (Windows): open the "Launcher" folder and double-click "1 - Make desktop icon.bat" once.
    After that, the CodeSmartAI-X icon on the Desktop starts Apache and MySQL in the background and opens the
    website. No XAMPP Control Panel and no typing localhost. See Launcher\README.txt.
  - After copying this version, press Ctrl+F5 once in the browser so it loads the new design (files end in ?v=46).
  - The database is the same as version 44 (capstone_db_v44.sql). Nothing to import again.


VERSION 45
  - ANTI-CHEAT: things that are BLOCKED (copy, cut, paste, drag and drop, right-click, shortcuts, the Alt and Windows
    keys) are no longer recorded or counted: they cannot happen, so there is nothing to report. The student only sees a
    short "turned off" message. Only what a web page cannot block is recorded for the teacher: leaving the tab or window,
    and leaving full screen (3 times submits the work). Teachers see this as "Left the page". Old records of blocked
    actions are ignored.
  - CODES ARE ONLY EMAILED: the sign-up (verify email) code and the forgot-password code are never shown on the page any
    more. Email MUST be set up (Admin > Email: codesmartaix@gmail.com + its Google App password), otherwise new students
    see "we cannot email your code yet". For testing on your own computer only, set SHOW_CODES_ON_SCREEN=true in
    config.php. (The admin's step-2 code is still shown on the screen until email is set up, so the admin can log in
    to set it up.)
  - DESIGNED EMAILS: verification code, password reset code, admin login code, "account approved" (with a Log in
    button) and the admin's test email are sent as designed HTML emails (with a plain text copy for old email apps).
  - CHALLENGES: when a student finishes EVERY challenge of a language, a "Reset <language> challenges" button appears.
    The student decides: after confirming, the progress and the saved fixes of that language start again from zero.
  - REMOVED: the waving robot on the main page, and "Fill all module lessons with the AI" on the teacher dashboard
    (Modules/fill_lessons.php; the lessons are already written).
  - NEW LOOK: calmer colors (no neon or glow), one blue for buttons, a cleaner light mode (white cards, clear edges),
    and buttons that line up: same height, text in the middle, full-width buttons on the log in / sign up / forgot /
    verify / change password pages, even buttons on the class cards. The logo is blue.
  - The database is the same as version 44 (capstone_db_v44.sql). Nothing to import again.


VERSION 44
  - CHALLENGES open in the editor like Visual Studio Code (problem and hint on the left, Run, OUTPUT / PREVIEW).
    Run checks the output (or the HTML page) against the goal and shows "Correct! Challenge solved" or "Not yet".
    The work on each challenge saves by itself; Reset code starts again from the buggy code.
    (Fixed: in version 42 and 43 the work on a challenge was not saved because of a mistake in save_work.php.)
  - The EYE button (Student ID, email, passwords) shows the hidden text ONLY WHILE it is held down (mouse, finger or
    the Space key). When it is released it goes back to *****. The monkey icon is gone.
  - The admin email is the group's Gmail codesmartaix@gmail.com (ADMIN_EMAIL). An old admin account with the example
    email is moved to it automatically. The step-2 admin code is emailed there once Email is set up:
    Admin > Email: Gmail address codesmartaix@gmail.com + the App password of that Google account
    (myaccount.google.com/apppasswords, 2-Step Verification must be on). Until then the code is shown on the screen.


VERSION 43
  AFTER A CODE EXAM IS SUBMITTED (student: Code Exam > View, or the exam title in My grades)
    - The student sees the submitted code in the editor (read only), can run it again, and reads the score and the
      teacher's feedback.
    - After the deadline (so classmates who are still taking it cannot get answers): "AI feedback, tips and tricks"
      (buttons: feedback on my code / tips to make it better / what I did wrong) and "Open in Playground", which copies
      the work into the student's Playground files (for example web-project-index.html) to keep improving it.
      The submitted exam itself never changes.
  PICTURE OF THE EXPECTED OUTPUT (teacher, code exams)
    - On "Create code exam" or on the grading page (Picture of the expected output) the teacher can add one picture,
      for example a screenshot of the finished web page. Students see it beside the problem.
    - Safety: only real pictures (PNG, JPG, GIF, WEBP, up to 5 MB); documents, programs, videos and fake pictures are
      blocked. Every picture is opened and saved again as a clean new picture (hidden data removed). With an AI key
      (Gemini or GPT) the AI looks at each picture and BLOCKS nudity, sexual content, violence, drugs and other content
      not appropriate for school. Pictures are stored in the uploads folder, which the browser cannot open directly;
      only the teacher, the admin and the students of that class can see them. The admin sees every picture
      (Admin > Quizzes & exams, "Checked by the AI" or "Not checked") and can remove it.
  ADMIN ACCOUNT SECURITY (Admin > Security)
    - Step 2: after the right admin password, a 6-digit code is sent to the admin EMAIL (ADMIN_TWO_STEP). A leaked
      password alone cannot open the admin area. Set up Email and put a REAL admin email on the Security page; until
      then the code is shown on the screen (test mode).
    - Change the admin login ID from "admin" to something hard to guess (Security page, needs the current password).
    - 5 wrong passwords lock the account for 15 minutes; every admin login, wrong password and wrong code is written in
      the sign-in log (time, computer, browser). The admin dashboard warns about wrong admin logins of the last 24 hours.
    - The admin is logged out after ADMIN_IDLE_MINUTES (30) without activity.
    - Optional ADMIN_ALLOWED_IPS: the admin can only log in from the school's own computers.
    - If the password leaked: change it (Change password) and every other device is logged out at once.
    - Locked out because the admin email is wrong? Someone with the server files sets ADMIN_TWO_STEP=false in
      config.php, logs in, fixes the email, and turns it back on.


VERSION 42
  PLAYGROUND = an editor like Visual Studio Code with the student's OWN FILES
    - The student first makes a file with + next to EXPLORER (hello.py, loops.js, index.html, style.css, app.php,
      main.cpp, Program.cs, Main.java). Typing saves INTO THAT FILE automatically ("Saved 2:45 PM" at the bottom).
      A file is only made when the student presses + (or Upload), so the database does not fill up with copies:
      each save updates the same row of saved_files. Up to 60 files of 100 KB per student.
    - Run (or Ctrl+Enter) runs the open file by its type. HTML, CSS, JavaScript and PHP files work together like a
      small website (a form can send data to a .php file). Close tabs with x, rename a file with the pencil (or a
      double-click), delete it with x in EXPLORER, Upload / Download to move a file from or to the computer.
    - Older saved files get a proper name with the file type once (for example "my loop" becomes my-loop.py), and the
      code from the old one-box-per-language playground becomes files like my-python.py.
    - Challenges (fix the bug) still open in the simple editor with Hint and Reset.
  LEAVE A CLASS: every class card of a student has a Leave button (and the class page too), for a student who joined
    the wrong subject. Finished work stays in the grades.
  GRADES (teacher) = a gradebook: one row per student, one column per quiz / code exam with the TITLE on top and the
    SCORE in the cell (Missed in red, Not graded, Not yet). Filters: Class, Show (quizzes and code exams / quizzes only /
    code exams only) and Students (all / only students who missed something). The last column lists WHAT each student
    missed. Click a title to open its answers or to grade.


VERSION 41: CLASSES, START TIMES + DEADLINES, AND A CODE EDITOR LIKE VISUAL STUDIO CODE
  CLASSES (new menu item "Classes")
    - A teacher makes a class for each subject and section (for example "IT 102 Computer Programming 2 - BSIT 1-A")
      and gets a 6-letter JOIN CODE. Students open Classes, type the code and press Join.
    - Every quiz and code exam belongs to ONE class, so only the students of that class get it and are notified.
    - IRREGULAR students simply join the classes of all the subjects they take (1st year and 2nd year subjects alike).
      The old "irregular / extra year levels" boxes and the "Who can take this? (year levels)" boxes were removed.
      The year level of a student is now only information.
    - The teacher dashboard has a "Viewing" box to look at one class at a time, so the work of different year levels
      and sections is not mixed. Grades are also shown class by class.
    - A class page shows the join code, its quizzes and code exams (how many finished / missed) and its students.
      Archive a class at the end of the semester (students cannot join it any more, results stay).
  START TIME AND DEADLINE (every quiz and code exam)
    - Students cannot open it before the start time ("Not open yet", with a live countdown).
    - Between the start and the deadline it is "Open" with "Due ... 2h 15m left". At the deadline a quiz or exam that is
      still open on the screen is submitted by itself.
    - Submitted = "Finished" in GREEN. Not submitted by the deadline = "Missed" in RED.
    - The code exam timer = the time limit from when the student opened it, but never past the deadline.
    - The teacher can change the class, the start time or the deadline on the results page, or press "End now".
      The old Close / Open again buttons were removed (End now and the deadline do that job).
    - TIMEZONE in config.php is 'Asia/Manila'. PHP and MySQL both use it, so the times are the school's times.
  CODE EXAM EDITOR (like Visual Studio Code)
    - PROBLEM and EXPLORER (files) on the left, tabs, line numbers, colours, Run (or Ctrl+Enter), OUTPUT and PREVIEW.
    - The work is also kept on the student's computer while the exam is open (in case the browser closes).
    - New language "Web project": index.html + style.css + script.js and, if the teacher ticks it, a PHP back end
      (process.php). Students can add files with +. In PREVIEW, a form or link to another file of the project opens it,
      and PHP files run on the server with the form data ($_POST / $_GET).
    - New language "Java" (console programs). It needs the Java JDK 11 or newer on the computer that runs the system.
      Set JAVA in config.php, for example 'C:\\Program Files\\Java\\jdk-21\\bin\\java.exe'.
  TEACHER: FULL SCREEN VIEWER
    - On the grading page press "Open the submissions in full screen" (or Open on one student). You see the student's
      code in the editor and the output or the web page. The square button shows only the output / page in full screen.
      Use the arrows to go student by student; "Save + next" saves the grade and opens the next student.

VERSION 40: the dashboards no longer repeat the menu. The big Module/Quiz/Exam/... tiles in the middle are gone
  (the left menu already has them). Instead: students see TO DO (new quizzes and code exams with a Take quiz / Open exam
  button) and MY PROGRESS; teachers see TO GRADE (code exam answers with a Grade button). The left menu shows small red
  numbers: new quizzes / code exams (students), answers to grade (teachers), accounts to approve (teachers and admin).

NEW LOOK IN VERSION 39
  After log in, every role (student, teacher, admin) has a LEFT MENU with icons, like the school portal.
  The top bar shows the logo, the bell (students) and the person's name; click the name for Mode (dark/light),
  Change password and Log out. Log out is also at the bottom of the left menu. The page content uses the full width.
  On a phone the left menu hides; the menu button (three lines) at the top left opens it.
  The main page (before log in) did not change.


WHAT IS IN THE FOLDER  (every part of the system has its own folder)
  config.php   the settings (database, teacher code, AI key...) and the shared functions. You edit this one.
  index.php    the start page: sends you to the login page or to your dashboard
  Logging/     login.php (the MAIN PAGE with the Log in pop-up), register.php (sign up), verify.php (type the code that was emailed),
               logout.php, reset.php (forgot password), password.php (change password), ping.php (keeps the login alive during exams),
               security.php (login lock, password rules, reset code, email verification code), mailer.php (sends the emails)
  Admin/       admin.php (admin dashboard + announcements), users.php (all students and teachers), content.php (all quizzes and exams),
               email.php (EMAIL SETUP: type the Gmail + App password, send a test email)
  Dashboard/   dashboard.php (student and teacher dashboards), notifications.php (the bell),
               approve.php (APPROVALS: teachers and the admin approve new accounts)
  Modules/     learn.php (modules and lessons; all lessons are already written)
  Challenges/  challenges.php (the challenge lists), challenge_solve.php (saves a solved challenge)
  Playground/  playground.php, run_code.php, save_work.php, files.php (My files)
  Assistant/   assistant.php (AI Assistant page), ai_chat.php (answers the chat)
  Quiz/        quizzes.php, quiz_new.php (teacher), quiz.php (student), quiz_result.php, results.php (teacher), log_event.php
  Exam/        exams.php, exam_new.php (teacher), exam.php (student), exam_results.php (teacher grading), exam_event.php
  Grades/      grades.php, promote.php (teacher: start a new school year, move students up)
  Setup/       install.php (open once to set up the database, then delete it)
  database/    SQL files 01 to 13 (install.php runs them for you) and schema.php (adds missing tables and columns by itself)
  assets/      style.css, landing.css + landing.js (the main page and the falling letters of the sign up / verify / forgot password pages), JavaScript and the pictures (assets/img = logo, robot.svg + one image per language)
  data/        fallback.json (built-in lesson text used when a lesson is empty),
               html_challenges.json (the 200 HTML/CSS challenges)
  PUBLISH.txt  HOW TO MAKE THE SYSTEM PUBLIC (school network, tunnel, web hosting) + the security checklist
  The address of a page is the folder + the file, for example http://localhost/capstone-system/Logging/login.php
  (just open http://localhost/capstone-system/ and it takes you to the right page).

FIRST SETUP
  1. Copy this folder to C:\xampp\htdocs\capstone-system
  2. Start Apache and MySQL in XAMPP
  3. Open http://localhost/capstone-system/Setup/install.php once, then delete the Setup folder (or just Setup/install.php)
  4. Open http://localhost/capstone-system/
  5. ADMIN: log in with the ID  admin  and the password  Admin-Start-2026  (the pop-up on the main page). The system makes you choose
     your own admin password right away (see ADMIN below).
  6. EMAIL: until you fill in the MAIL_ settings in config.php the site is in TEST MODE (the verification code is shown on the page
     instead of being emailed). See EMAIL VERIFICATION below to switch real emails on.

WHAT IS NEW IN VERSION 30
  The Email page now says which Gmail it logs in with and how many characters the saved password has, and warns when the password
  does not have the shape of a Gmail App password (always exactly 16 small letters). The password itself is never shown. This
  catches the most common mistake: typing the normal Gmail password, or the App password of another Gmail, which Google answers
  with "535 Username and Password not accepted".

WHAT WAS NEW IN VERSION 29
  1. EMAIL SETUP PAGE (Admin menu > Email). The admin types the Gmail address and its App password, presses Save and "Send test email".
     No file editing. If Gmail says no, the page shows the real answer and what to do (wrong App password, no internet,
     certificate error in XAMPP). The settings are saved in the database (table settings, keys mail_...), so the database does not
     change shape. What is saved on the page wins over the MAIL_ settings of config.php; "Remove the saved email setup" goes back to them.
  2. The admin dashboard reminds the admin (Needs attention) while email is not set up, and the verify page's Test mode text now
     says where to fix it.
  3. The mail connection now finds the certificate list that comes with XAMPP (php\extras\ssl\cacert.pem) by itself.

WHAT WAS NEW IN VERSION 28  (a clean-up of version 27, nothing was added to the database)
  1. The sign up, verify email and forgot password pages now have the same falling letters, header and light/dark button as the
     main page, so the whole entrance looks the same.
  2. The name in the top bar now reads CodeSmartAi-X (there was a gap between CodeSmart, Ai and -X on every page).
  3. Removed what nothing uses any more: the old log in picture (assets/img/hero.svg) and its two style rules, and the double
     heading "Verify your email" on the verify page.
  Test mode (the code shown on the verify page while MAIL_HOST is empty) is still there on purpose: it is the only way to try
  sign up on a computer without an email account. It disappears by itself when you fill in the MAIL_ settings.

WHAT WAS NEW IN VERSION 27
  1. A new MAIN PAGE (Logging/login.php, also what you see at http://localhost/capstone-system/): animated CODESMART AI-X title, a short
     introduction, a waving robot and falling letters. The Log in button (top right) opens the log in pop-up.
  2. EMAIL VERIFICATION: a new account must type a 6-digit code that is emailed to it before it can log in.
  3. IRREGULAR STUDENTS: students who take subjects of more than one year level (for example 1st year + some 2nd year subjects).
  4. ADMIN: one admin account with its own dashboard (users, quizzes and exams of all teachers, school year, announcements).
  Built on version 25. The extras of version 26 (CSV export, copy/delete exam, teacher password reset, late flag) are NOT in this version.

UPDATING FROM AN OLDER VERSION (your accounts and grades stay)
  1. Copy your old config.php somewhere safe (your AI key and paths are in it)
  2. Delete the WHOLE old capstone-system folder first (version 23 moved every page into a folder, so old pages
     left lying around in the main folder would be confusing), then unzip this one in its place
  3. Open config.php and paste your AI key / paths back in if you changed them. In version 20 the database
     name, user and password are at the top of config.php (DB_NAME, DB_USER, DB_PASS) - copy your values there.
  4. Open http://localhost/capstone-system/Setup/install.php once. It only adds what is missing
     (never import the .sql files by hand into a database that already has data).
  5. Delete Setup/install.php
  If you forget step 4 the site repairs itself: the missing columns and tables, the 200 HTML/CSS challenges
  (data/html_challenges.json) and the removal of the old "Quick start" modules are done by database/schema.php the
  next time any page opens. Setup/install.php is still the safest way to set up a brand new database.
  The links saved inside old notifications are updated to the new folders by themselves.
  Version 27 adds by itself: the column users.email_verified (every existing account counts as verified), users.extra_years
  (irregular students), the table email_codes and the admin account (ID admin, see ADMIN). Your accounts, quizzes and grades stay.
  If your folder is nested (htdocs\capstone-system-v20\capstone-system)
  the address is http://localhost/capstone-system-v23/capstone-system/Setup/install.php

MAIN PAGE  (new in version 27)
  Logging/login.php is now the front page. It has the animated title CODESMART AI-X (every letter falls from the top and a glow runs
  through the letters), a typing line (PHP, C++, C#, HTML/CSS, JavaScript, Python), a short text that explains what the system is,
  a cute robot that waves, and letters falling in the background. The Log in button in the top right (and the big one under
  the text) opens the log in form as a pop-up. Esc, the X, or a click outside closes it. A wrong password, a new account or a
  new password opens the pop-up by itself with the message. A sun/moon button switches light and dark.
  It also works without JavaScript (the Log in link then opens the pop-up from the server), and with "reduce motion" turned on in the
  computer nothing falls or moves. To change the text edit Logging/login.php; the picture is assets/img/robot.svg; colors and speed
  are in assets/landing.css and assets/landing.js. Every "Log in" link on the other pages opens the same pop-up.

ACCOUNTS
  Students sign up with Full name, Student ID, Email and password. They log in with the Student ID.
  A new account must also VERIFY THE EMAIL before the first log in (see EMAIL VERIFICATION below).
  The Student ID "admin" and the admin email can never be used to sign up.
  Teachers sign up with the teacher code in config.php (TEACHER_CODE, now Wcc-teacher1).
  Optional: STUDENT_CODE in config.php = a class code students must type to sign up.
  APPROVAL OF NEW ACCOUNTS (new in version 37, APPROVE_NEW_ACCOUNTS=true in config.php):
  After a new person verifies the email, the account WAITS. It cannot log in until it is approved on the Approvals page.
    - A teacher approves students (a teacher knows who is in the class). The admin approves students and teachers.
    - Decline = the waiting account is deleted, so the real owner of that Student ID or email can still sign up.
    - The person gets an email when the account is approved (when email is set up). Approvals shows who approved whom.
    - The teacher dashboard and the admin dashboard show how many accounts are waiting; the menu shows the number too.
    - Accounts that existed before version 37 count as approved. No list from the school is needed.
  Optional: ALLOWED_EMAIL_DOMAINS in config.php, for example 'worldciti.edu.ph' = only school emails can sign up.
  Passwords: 8 to 72 characters, very easy ones (12345678, password, the Student ID...) are refused.
  Students also choose their YEAR LEVEL (1st to 4th year) when they sign up (see YEAR LEVELS below).

SETTINGS (top right) - Dark / Light mode, who is signed in, Change password and Log out.
  Log out is a button inside Settings. During a quiz or code exam Settings only has the mode, so nobody
  can leave by accident.

CHANGE PASSWORD  (Settings > Change password)
  Way 1: type the OLD password, then the new one twice.
  Way 2: "I do not remember my old password" > "Use my Student ID and email". The student types their Student ID and
         the email they signed up with. If the two belong to the SAME account, a 6-digit code is made. The student types the code
         and a new password. The code works for 15 minutes.
         - When EMAIL is set up (MAIL_HOST in config.php) the code is EMAILED to the account and is not shown on the page.
           This proves the person can read that mailbox, so it also counts as verifying the email.
         - In TEST MODE (no MAIL_HOST) the code is shown on the page, as in the older versions. Then the real check is only the
           Student ID + email pair, so tell students to keep both private.
  "Forgot password?" in the log in pop-up does the same without logging in.
  After a change, any other device that was logged in is logged out. Old accounts that were made
  before Student IDs existed cannot use this (they log in with their email and set an ID on the dashboard).
  The admin password is never reset this way (see ADMIN).

LOGIN SAFETY
  5 wrong passwords lock that account for 15 minutes ("Forgot password?" still works and unlocks it).
  Verification codes: 5 wrong codes make the code useless (ask for a new one), at most 3 codes per account and 10 per computer
  in 15 minutes (so nobody can use the sign up form to flood an inbox).
  50 wrong tries from one computer lock that computer. 5 wrong Student ID + email tries on the Forgot password
  page lock that Student ID for 15 minutes. Teacher / class code guesses are limited too.

STUDENT DASHBOARD
  Student Info on top. The Student ID and the email show ***** by default (they are the key for "Forgot password",
  so they stay private if someone looks at the screen or takes a screenshot). Press the eye icon to see one; press it
  again to hide it. They also hide again by themselves after 15 seconds and every time the page is opened.
  The teacher dashboard does the same for the Teacher ID and email.
  Then the tiles with a picture each (Module, Challenges, Playground, AI Assistant, Quiz,
  Code Exam, Grades; the pictures are assets/img/ic-*.png, replace a file to change a picture),
  quiz average, challenge percent, and progress for PHP, C++, C#, HTML/CSS, JavaScript and Python.

TEACHER DASHBOARD (different from the student one)
  Teacher Info on top, then three dashboards:
    Quiz dashboard       create multiple choice quizzes, see every student result (scored automatically)
    Code Exam dashboard  create coding exams (language, minutes, points), read the code, give score + feedback
    Grades dashboard     every student: quiz average, code exam average, challenges solved, integrity flags
  When a teacher creates a quiz or a code exam, the teacher ticks which YEAR LEVELS may take it (see YEAR LEVELS below).

QUIZZES (multiple choice, graded automatically)
  Teacher: Quiz dashboard > Create quiz. Paste your questions (1. question / A. B. C. D. / Answer: B) and press
  "Add these questions", or add them one by one. Beside every question is an Answer key (A, B, C or D).
  Save: every student of the ticked year levels is notified and the quiz is on their Quiz page. When a student submits, the answers are
  compared with your key and the score is calculated right away, nobody checks by hand. The student sees the
  score and which answers were right; you see every score, the average and how many got each question right.
  Untick "Show the correct answers to students" if you do not want them to see the key.
YEAR LEVELS  (new in version 24: 1st, 2nd, 3rd and 4th year; when the school year ends see NEW SCHOOL YEAR below)
  Problem it solves: a teacher posts an exam for 1st year and the 2nd, 3rd and 4th year students must not see it.
  STUDENTS
    - Choose the year level on the sign up page (required for students, teachers do not have one).
    - Accounts made before version 24 have no year yet. The dashboard shows "Choose your year level" once. After it is
      saved the student cannot change it (so nobody can switch year to peek at another year's exam). Only the teacher can.
    - Until the year is set, the student only sees quizzes and exams that the teacher made for ALL years.
  TEACHER
    - Create quiz / Create code exam have four boxes: 1st year, 2nd year, 3rd year, 4th year (all ticked at the start).
      Tick only the years that should take it. At least one year must be ticked.
    - Only students of the ticked years: get the notification (bell and pop-up), see it in their Quiz / Code Exam list,
      count it on their dashboard, and can open it. Another year that types the address of the quiz or exam gets
      "Not for your year" and nothing is started, nothing is saved.
    - Change it later: open the quiz (View results) or the exam (Open and grade). The box "This quiz is for" has the
      same four tick boxes and a Save years button. Years you ADD are notified. Years you REMOVE lose the quiz/exam from
      their list, but a student who already started it can still finish it and keeps the result.
    - Quiz and Code Exam dashboards have a "For" column (All years, 1st year, 1st year, 3rd year...).
      The code exam grading page lists only the students of those years (plus anybody who already started it).
    - Grades dashboard: Year level column for every student, the number of students per year, a "Show" list to see
      only one year, and a list + Save button on each row to correct a student's year level.
    - Quizzes and exams made before version 24 are for ALL years, so nothing disappears after the update.
  HONEST LIMIT: the year level is what the student typed when signing up. The system cannot know the real year, so a
  student could choose a wrong year. The teacher can see the year in Grades and correct it (and STUDENT_CODE keeps strangers
  from signing up at all). For a graded exam, check the Year column when you grade.

NEW SCHOOL YEAR  (new in version 25: a 1st year student becomes a 2nd year student next year)
  Teacher: Grades dashboard > "Start a new school year" (also on the teacher dashboard). Use it ONCE at the end of the school year.
  FIRST TIME: the page asks for the school year that is running now (example 2026-2027). Quizzes and code exams you made so
  far are marked with it, and so is everything you make later (you see "School year 2026-2027" in the lists and in Grades).
  AT THE END OF THE SCHOOL YEAR: open the page. It lists every student with a year level, "Now" and "After".
    - Every student that stays ticked moves up one year: 1st -> 2nd -> 3rd -> 4th. UNTICK the students who repeat the year
      (they stay where they are). "Tick all / Untick all" buttons help with a big class.
    - 4th year students become GRADUATED (year level 5). They keep their account, grades and old results, but they get no new
      quizzes or exams, no notifications, and cannot open new work by typing the address. Untick the box to keep them in 4th year.
    - Students with no year level yet, and students who already graduated, are not changed.
    - "Close the quizzes and code exams of the last school year" (ticked at the start): nobody can start them, and nobody can
      go on with an unfinished one. Results that are already finished stay on the students' pages. New students do not get
      last year's exams. You can press "Open again" on any quiz or exam (Quiz dashboard / Code Exam dashboard).
    - Type the new school year (example 2027-2028), tick "I checked the list" and press the button. It must come AFTER the
      current school year, so it cannot be done twice by mistake, and the form is folded away after you used it.
    - Every promoted student gets a notification ("You are now in 2nd year").
    - UNDO: right after, the same page has an Undo button (shown for the last change). It puts the students back, opens the
      quizzes/exams it closed and sets the old school year again. A student whose year you changed by hand in Grades after the
      promotion is not touched by Undo.
    - The history of the last changes is shown at the bottom of the page.
  What stays correct after a promotion:
    - The year level a student had when he or she did a quiz or exam is saved with that attempt. The teacher's result pages
      (Year column) show the year level WHEN it was taken, not the new one. Old attempts are saved this way at the promotion.
    - Grades dashboard: "Quiz and exam scores of [school year]" list. Choose 2026-2027 to see only that school year's quizzes and
      exams in the Quiz/Exam columns (All school years is the default). Students see the school year next to their own results.
    - Grades dashboard: the list in each row can set any student to 1st, 2nd, 3rd, 4th year or Graduated by hand (late
      enrollees, students who were promoted by mistake, transferees).
    - New students sign up with their year level (1st year) as before.
  Closed by hand: on the Quiz and Code Exam dashboards the button "Close" stops new starts (a student who already started may
  finish). "Open again" opens it. The label "Closed" is shown.
  Note: the school year and the students are shared by all teachers (one list of students), so any teacher can start the new
  school year. Decide in your department who presses the button.

EMAIL VERIFICATION  (new in version 27)
  WHAT THE STUDENT SEES
    1. Sign up (name, Student ID, email, year, password). The account is made but it CANNOT log in yet.
    2. The page "Verify your email" opens and a 6-digit code is sent to the email (the page shows it as ma***@gmail.com).
    3. The student types the code. The code works for 30 minutes. 5 wrong codes make it useless. "Send a new code" makes a new
       one (the old one stops working). "Wrong email? Change it" fixes a mistyped email and sends the code there.
    4. After the right code the log in pop-up opens with "Your email is verified". Now the log in works.
    If a student types the right password before verifying, the same verify page opens (a new code is sent only when the old one
    is not valid any more). Teachers must verify their email too. The admin does not.
  RULES THAT KEEP IT FAIR
    - Accounts that existed before version 27 count as verified, nobody is locked out by the update.
    - An account that never verified has no data and cannot log in, so it does NOT block its Student ID or email. If someone
      signs up again with the same Student ID or email, the new sign up replaces the unverified one (this fixes typos and stops
      somebody from "parking" another student's ID). A VERIFIED account always keeps its ID and email.
    - Wrong password, unverified or not, always gives the same plain message ("ID or password is incorrect.").
    - If the email cannot be sent (mail server down, wrong password in config.php) the account is still made, the student
      sees "We could not send the email right now" and can press "Send a new code" later. The real reason is written
      to the PHP error log (XAMPP: C:\xampp\apache\logs\error.log), never shown to students.
  FOR THE ADMIN
    - Admin > Users: a yellow "Email not verified" label, the button "Mark email as verified" (use it when a student cannot get
      the email, only when you know the email belongs to that student), and the list "Email: Not verified yet".
    - Admin dashboard > Needs attention shows how many accounts are waiting.
  HOW TO SEND REAL EMAILS - THE EASY WAY (new in version 29): log in as admin > menu "Email"
    1. Make a Gmail for the system, turn ON 2-Step Verification, make an App password (16 letters, NOT the Gmail password).
    2. Type the Gmail and the App password on the Email page and press Save, then "Send test email" to your own address.
    3. If the test fails the page prints the answer of the mail server. After it works, new students get their code by email and the
       Test mode box is gone. The App password is saved in the database and is never shown again (leave the box empty to keep it).
    On a PUBLIC site (PUBLIC_SITE=true) the MAIL_ settings in config.php must still be filled in once, because the site refuses to
    open without them (see PUBLISH.txt); the Email page can change them later.
  HOW TO SEND REAL EMAILS - THE OLD WAY (config.php, the box "EMAIL")
    XAMPP cannot send email by itself, so the system talks to a mail account (SMTP). Example with Gmail:
      1. Use a Gmail account for the school/project (not your personal one). Turn ON 2-Step Verification in the Google Account.
      2. Google Account > Security > 2-Step Verification > App passwords. Make one (16 letters). It is NOT your Gmail password.
      3. In config.php:
           const MAIL_HOST='smtp.gmail.com';   const MAIL_PORT=587;   const MAIL_SECURE='tls';
           const MAIL_USER='yourschool@gmail.com';   const MAIL_PASS='abcd efgh ijkl mnop';   (spaces are ignored)
         MAIL_FROM can stay empty (the sender is MAIL_USER). MAIL_FROM_NAME is the name students see.
      4. Sign up with a real email of yours and check the Inbox (and Spam). That is the only real test.
    Other mail services work the same way with their own SMTP host, port and password (port 465 + MAIL_SECURE='ssl' is also supported).
    A free Gmail account can only send a limited number of emails per day (around 500), enough for a class.
  TEST MODE (no email set up: the Email page is empty and MAIL_HOST in config.php is empty, the default)
    Nothing is emailed. The verify page shows the code in a box ("Test mode") so you can try everything on your own computer with no
    email set up. NEVER leave it like this on a public site, anybody could skip the check: with PUBLIC_SITE = true the site refuses to
    open until MAIL_HOST is filled in.
  HONEST LIMITS
    - I tested the email sending against a fake mail server on my side (plain, STARTTLS on port 587 and SSL on port 465): the message,
      the code, the login to the server and the error cases all work. I could NOT test it against real Gmail or another real mail
      service from here, so do step 4 once yourself. If it fails, look at the PHP error log: the server's own answer is written there.
    - Emails can land in the Spam folder, especially from a new Gmail account. The verify page tells students to look there.
    - The check proves that the student can read that mailbox. It does not prove that the person is the real student of that Student ID.

IRREGULAR STUDENTS  (new in version 27)
  An irregular student takes subjects of more than one year level (for example a 1st year student who also takes 2nd year
  subjects, or a 3rd year student who still has a 2nd year subject to repeat).
  HOW IT IS MANAGED: every student still has ONE own year level (what the student chose when signing up), and an irregular student
  also gets EXTRA YEARS. The teacher (Grades dashboard) or the admin (Admin > Users) opens "Irregular student?" on the student's row,
  ticks the other year levels the student takes subjects of, and presses Save.
  Then the student:
    - sees, can open, and is notified about the quizzes and code exams of the own year AND of every extra year,
    - sees "1st year (irregular: also 2nd year subjects)" on the dashboard and in the quiz / exam lists,
    - is told in the bell ("You can now take quizzes and exams of the 1st year (irregular: also 2nd year subjects).").
  A quiz/exam that the teacher made for 3rd year only is still NOT open for that student (not an extra year of the student).
  Teachers see the irregular label and "Irregular: N" in Grades (list "Show > Irregular students"), the code exam grading page lists
  the irregular students of the year too, the admin dashboard counts them.
  School year change: the own year moves up, the extra years stay. If the new own year is the same as an extra year, the extra is
  just ignored (the student is no longer shown as irregular). Undo puts the own year back and the extras are still there.
  Graduated students do not get new work, extras or not. Results keep the OWN year of the student at that time.
  HONEST LIMIT: it works by YEAR LEVEL, not by SUBJECT. The system does not know which subject belongs to which class: the teacher
  still decides which year levels each quiz/exam is for. An irregular student therefore also sees the other quizzes and exams
  made for the extra year, even for subjects he or she does not take. The exact version (classes/subjects with a list of enrolled
  students) would need a new subjects table and an enrollment page for every subject: ask if you want that as the next step.

ADMIN  (new in version 27)
  There is exactly ONE admin account. It is made by the system (config.php ADMIN_ID = 'admin').
  LOG IN: Student ID  admin  and the password ADMIN_START_PASSWORD from config.php (Admin-Start-2026). The first time, the system sends
    the admin to "Change password" and nothing else opens until a new password is chosen (the first password is written in this README, so
    everybody who has the files knows it). With PUBLIC_SITE = true the site refuses to open while ADMIN_START_PASSWORD is the default.
  FORGOT THE ADMIN PASSWORD: the admin cannot use "Forgot password" (nobody can reset it with an on-screen or emailed code). Open phpMyAdmin,
    table users, delete the row with role admin, then open Setup/install.php (or any page in a new browser): the admin is made again
    with ADMIN_START_PASSWORD. (Nothing else is lost, the admin owns no data.)
  NOBODY CAN SIGN UP AS ADMIN: the ID admin, the admin email and the role are refused on the sign up page.
  WHAT THE ADMIN CONTROLS
    Admin dashboard   numbers (students, irregular students, teachers, quizzes, code exams, to grade, integrity flags), students per year
                      level, "Needs attention" (students without a year, exams waiting to be graded, school year not set, unverified accounts, email not set up),
                      and the newest accounts.
    Users             every student and teacher (search by name, ID or email; filter by role, year level, irregular, email not verified):
                      set a student's year level and EXTRA years (irregular), give a TEMPORARY PASSWORD (shown once, the person is
                      told in the bell if a student; works until they change it), unlock a locked login, mark an email as verified,
                      delete an account. A teacher who still owns quizzes or exams cannot be deleted (close or delete them first).
                      The admin account itself is never shown here and cannot be changed or deleted.
    Quizzes & exams   every quiz and code exam of every teacher with the teacher's name: close, open again, or delete (results go with it).
    School year       the same page as the teachers have (start a new school year, undo).
    Email             (new in version 29) the Email setup page: Gmail + App password, "Send test email", real error messages, remove the setup.
    Announcement      one message (200 letters) to all students or to one year level. It appears as a pop-up and stays in the bell. Irregular
                      students get the announcement of every year they take subjects of. Graduated students are not told. Teachers have no
                      bell in this system, so announcements are for students only.
  The admin does NOT take quizzes, use the playground or the AI: those pages send the admin back to the admin dashboard, and a student or
  teacher who types an Admin/ address is sent away. The admin sees Student IDs and emails (needed for support), so keep the admin password private.

NOTIFICATIONS
  Students get a pop-up and a bell (top right) when a teacher sets a quiz or a code exam for their year level, and when an exam is graded.

EXAMS AND QUIZZES (protected)
  The student reads the rules, then presses Start (full screen). Timer is set by the teacher and runs on the server.
  Blocked and recorded for the teacher: copy, paste, cut, right-click, Ctrl shortcuts, the Alt key, the Windows key,
  leaving full screen, switching tab or window. Leaving 3 times submits the work by itself.
  Alt+Tab: a web page CANNOT fully block it, Windows handles it. The system asks the browser to capture the keys
  (Keyboard Lock in full screen; Chrome / Edge may ask permission and it can only capture some keys). Whatever
  still gets through is recorded and counted. Test it on your own computer before an important exam.
  When the student presses Submit, a box asks "Are you sure you want to submit?" (Yes, submit / No, go back).
  After submitting, everything is released: Alt and the keys work again, and full screen is closed.
  The page keeps the login alive while the exam is open. The AI Assistant is off in exams.
  A web page cannot stop a second device or a second desktop, so important exams are still best supervised.

PLAYGROUND
  Run Python, JavaScript, HTML/CSS (in the browser) and PHP, C++, C# (on the server, see config.php).
  On a PUBLIC site (PUBLIC_SITE = true) PHP, C++ and C# are not run on the server for safety, see PUBLISH.txt.
  Save work (kept in your account), Save as file (downloads a real file), Open file, My files.
  AI Assistant: a chat box under the Output box. Tick "Send my code with my
  question" and ask "check my code" or "why does my code print nothing?". The answer uses the language and the
  code that are in the editor.

AI ASSISTANT (chat page + Playground chat)
  Students open AI Assistant (dashboard tile or "AI Help" in the top menu), or use the chat under the Playground
  editor. It works in two modes and always works:
    BUILT-IN TUTOR (no key, no internet, no cost): answers common questions for Python, JavaScript, HTML/CSS,
      PHP, C++ and C# (printing, variables, input, math, if/else, switch, loops, functions, lists/arrays,
      dictionaries, strings, classes, comments, errors; HTML/CSS page structure, links, images, lists, tables,
      forms, colors, classes/ids, box model, flexbox) with short basic examples, and checks pasted code for typical
      mistakes (missing colon, bracket, quote, semicolon, $ in PHP, misspelled HTML tags...). It cannot answer
      every possible question and cannot explain your code line by line. It says so honestly.
    CLAUDE (needs an Anthropic API key): answers any programming question, in simple words, with basic code.
  GPT (OpenAI, paid but cheap; needs an OpenAI API key):
    1. At https://platform.openai.com go to Billing and add a little credit, then API keys > Create new secret key.
    2. Open config.php and paste it:   const OPENAI_KEY='sk-...';   (OPENAI_MODEL='gpt-6-luna' by default)
    3. Save. GPT is used first; if GPT fails and GEMINI_KEY is filled in, Gemini answers; then the built-in tutor.
  GEMINI (needs a Google Gemini API key; free tier, or paid if you turn on billing in AI Studio):
    1. Go to https://aistudio.google.com, sign in with Google, click "Get API key" and copy the key.
    2. Open config.php and paste it:   const GEMINI_KEY='your-key';
       (Optional) const GEMINI_MODEL='gemini-3.5-flash-lite'; and GEMINI_BACKUP_MODELS are tried when a model is busy.
    3. Save config.php. The chat now says "Ask Gemini" and the teacher dashboard shows "Gemini is ON".
    The free tier has request limits for the whole site. When a limit is reached, the built-in tutor answers
    instead. If GEMINI_KEY is filled in, Gemini is used even if AI_KEY is also filled in.
  To switch Claude on:
    1. Go to https://console.anthropic.com, sign in, create an API key (a long text that starts with sk-ant-).
       This is separate from a claude.ai chat subscription. The API is pay as you go, so add a little credit.
    2. Open config.php and paste the key:   const AI_KEY='sk-ant-...';
       (Optional) const AI_MODEL='claude-haiku-5-5';  is the cheap, fast one. Use 'claude-sonnet-5-5' for
       smarter answers (costs more).   const AI_DAILY_LIMIT=30;  is how many Claude questions one student can ask
       per day, change it to control your cost.
    3. Save config.php. Nothing else to restart.
  The key stays on the server (students never see it). If Claude does not answer (wrong key, no internet, no
  credit) the built-in tutor answers instead and the chat says so. The teacher dashboard shows "Claude is ON"
  or "Claude is OFF".
  The AI is locked while a student has a code exam open (the Assistant page and the Playground chat both show a
  lock). A web page cannot stop a student from using another device, so exams are still best supervised.

MODULES
  Easy / Medium / Hard / Advanced for each of the 6 languages. "Quick start" modules are removed.
  Each module has links to W3Schools and the official docs for extra reading.

CHALLENGES
  50 per level for every language (200 per language). HTML/CSS challenges are checked in the browser.
  If HTML/CSS ever shows "0 of 0", just reload any page: the 200 challenges are put back automatically.
