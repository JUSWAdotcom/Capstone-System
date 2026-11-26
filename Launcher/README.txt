CodeSmartAI-X LAUNCHER  (Windows + XAMPP)
=========================================

WHAT IT DOES
  One double-click starts Apache and MySQL in the background (no XAMPP Control Panel)
  and opens the website in your browser. No need to type localhost/capstone-system.

ONE TIME ONLY
  1. This "Launcher" folder must be inside your website folder, for example:
        C:\xampp\htdocs\capstone-system\Launcher
  2. Double-click "1 - Make desktop icon.bat".
     A CodeSmartAI-X icon (the blue logo) appears on your Desktop and in the Start menu.
     If Windows asks "Do you want to run this file?", click Run.

EVERY DAY
  Double-click the CodeSmartAI-X icon on the Desktop.
  - Apache and MySQL are off: they start (a few seconds), then the website opens.
  - They are already on: the website opens right away.

INSTALL JAVA, C++ AND C# (ONE TIME, needs internet)
  Double-click "Install languages.bat". It downloads Java (JDK 21), C++ (g++) and C# (.NET SDK)
  into C:\xampp\langs (about 700 MB, 5 to 15 minutes), tests each one, and shows the result.
  CodeSmartAI-X finds them there by itself: nothing to change in config.php, nothing to restart.
  Python, JavaScript and HTML / CSS run in the browser, and PHP comes with XAMPP, so all 8 languages work.
  Students never install anything: only the computer that runs CodeSmartAI-X needs this.

OPTIONAL
  Auto-start ON.bat        Start by itself every time Windows starts.
                           It asks if the website should also OPEN by itself.
  Auto-start OFF.bat       Turn auto-start off.
  Stop CodeSmartAI-X.bat   Turn Apache and MySQL off (also in the Start menu).
                           Shutting down the laptop is also fine.

IF SOMETHING GOES WRONG
  A message box says what failed and shows the last lines of the log.
  - "Apache did not start" / "Another program is using port 80":
    close the other program (IIS, Skype, VMware...) or change Apache's port in XAMPP.
  - "MySQL did not start": open the XAMPP Control Panel once, press Start on MySQL
    and look at the red message.
  - If you move the website folder, run "1 - Make desktop icon.bat" again.

HOSTING ONLINE
  Do not upload this folder to a hosting site. (It is blocked from the web anyway.)
