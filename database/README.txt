DATABASE FILES (version 48)

capstone_db.sql = the whole database in ONE clean file. It is SAFE:
  - it makes the database capstone_db and every table that is missing (25 tables)
  - it fills the lessons and the challenges only when those tables are empty
  - it never deletes accounts, classes, quizzes, code exams, grades or the email setup
  So it can be imported into a new computer (phpMyAdmin > Import) and also again into a database that is in use.
  Setup/install.php runs this same file.

schema.php = brings an older database up to date by itself, every time the website has a new version
  (it adds missing tables and columns, and removes the ones that are not used any more).
  A database that already has students never needs an import: just open the website.

To move a database WITH students and grades to another computer, use Export in phpMyAdmin.
