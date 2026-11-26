<?php
require __DIR__.'/../config.php';
require __DIR__.'/security.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $ip = my_ip();
    $name = trim($_POST['name'] ?? '');
    $sid = trim($_POST['sid'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? ''; // version 46: the password is typed twice
    $teacher_code = $_POST['code'] ?? '';
    $class_code = $_POST['cls'] ?? '';
    $year = (int)($_POST['year'] ?? 0);
    if (($_POST['role'] ?? '') === 'teacher') {
        $role = 'teacher';
    } else {
        $role = 'student';
    }

    $problem = password_problem($password, $sid, $email);

    if ($name == '' || strlen($name) > 100 || !preg_match('/^[A-Za-z0-9._-]{3,30}$/', $sid) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $error = 'Enter your full name, a Student ID (3-30 letters, numbers, dot, dash or underscore) and a valid email.';
    } elseif ($role === 'student' && ($year < 1 || $year > 4)) {
        $error = 'Choose your year level (1st to 4th year). Are you a teacher? Choose "Teacher" in "I am a" at the top of the form.';
    } elseif (strcasecmp($sid, ADMIN_ID) === 0 || strcasecmp($email, ADMIN_EMAIL) === 0) {
        $error = 'That Student ID or email is already registered. If it is yours and you did not sign up, tell your teacher or the admin.';
    } elseif (!email_domain_ok($email)) {
        $error = 'Please use your school email address (' . ALLOWED_EMAIL_DOMAINS . ').';
    } elseif ($password !== $password2) {
        $error = 'The two passwords are not the same. Please type the same password in both boxes.';
    } elseif ($problem != '') {
        $error = $problem;
    } elseif ($role === 'teacher' && wrong_tries('tcode', $ip) >= 5) {
        // version 48: only the teacher code is counted here (wrong CLASS codes of students no longer block sign-ups)
        $error = 'Too many wrong teacher codes. Please wait 15 minutes and try again.';
    } elseif ($role === 'teacher' && !hash_equals(TEACHER_CODE, $teacher_code)) {
        add_wrong_try('tcode', $ip);
        $error = 'The teacher code is incorrect.';
    } elseif ($role === 'student' && STUDENT_CODE !== '' && wrong_tries('scode', $ip) >= IP_CODE_FAILS) {
        $error = 'Too many wrong codes. Please wait 15 minutes and try again.';
    } elseif ($role === 'student' && STUDENT_CODE !== '' && !hash_equals(STUDENT_CODE, $class_code)) {
        add_wrong_try('scode', $ip);
        $error = 'The class code is incorrect. Ask your teacher for it.';
    } elseif (signup_taken($sid, $email)) {
        $error = 'That Student ID or email is already registered. If it is yours and you did not sign up, tell your teacher or the admin.';
    } else {
        // version 47: NO account is saved yet. The form waits in the session and a code is emailed;
        // the account is made on the verify page, only when the right code is typed.
        $p = ['name' => $name, 'sid' => $sid, 'email' => $email, 'year' => $role === 'student' ? $year : null, 'role' => $role,
              'hash' => password_hash($password, PASSWORD_DEFAULT), 'approved' => APPROVE_NEW_ACCOUNTS ? 0 : 1];
        $sent = signup_send_code($p);
        if ($sent !== '') {
            $error = $sent;
        } else {
            session_regenerate_id(true);
            $_SESSION['signup'] = $p;
            header('Location: '.BASE.'Logging/verify.php');
            exit;
        }
    }
}

head('Create account');
flash(); // for example: the sign-up was cancelled, or the 30 minutes for the code are over
if ($error) {
    echo '<p class="err">' . e($error) . '</p>';
}
?>
<?php
// version 47: "I am a" comes first, and only the boxes of that role are shown (a teacher never sees the year level).
// After an error the typed name, ID, email, role and year stay in the form (passwords and codes are typed again).
$was = fn($k) => e((string)($_POST[$k] ?? ''));
$isTeacher = ($_POST['role'] ?? '') === 'teacher';
$yr = (int)($_POST['year'] ?? 0);
?>
<form method="post" class="card auth" id="signup">
<input type="hidden" name="t" value="<?= csrf() ?>">
<label>I am a<select name="role" id="su-role"><option value="student">Student</option><option value="teacher"<?= $isTeacher ? ' selected' : '' ?>>Teacher</option></select></label>
<label>Full name<input name="name" required maxlength="100" autocomplete="name" value="<?= $was('name') ?>"></label>
<label><span id="su-idl"><?= $isTeacher ? 'Teacher ID' : 'Student ID' ?></span><input name="sid" required maxlength="30" autocomplete="username" value="<?= $was('sid') ?>"></label>
<label>Email<input type="email" name="email" required maxlength="100" autocomplete="email" value="<?= $was('email') ?>"></label>
<label class="su-student"<?= $isTeacher ? ' hidden' : '' ?>>Year level<select name="year"><option value="">Choose your year</option><?php foreach ([1 => '1st year', 2 => '2nd year', 3 => '3rd year', 4 => '4th year'] as $n => $t) { echo '<option value="' . $n . '"' . ($yr === $n ? ' selected' : '') . '>' . $t . '</option>'; } ?></select><small>After you log in, join your classes with the class codes from your teachers (irregular students join every subject they take).</small></label>
<?= pw('Password (8+ characters)', 8, 'password', 'new-password') ?>
<?= pw('Type the password again', 8, 'password2', 'new-password') ?>
<?php if (STUDENT_CODE !== '') { ?>
<label class="su-student"<?= $isTeacher ? ' hidden' : '' ?>>Class code (from your teacher)<input name="cls" autocomplete="off"></label>
<?php } ?>
<label class="su-teacher"<?= $isTeacher ? '' : ' hidden' ?>>Teacher code (from the admin)<input name="code" autocomplete="off"></label>
<p><small>We email you a 6-digit code. Your account is made only after you type that code.<?php if (APPROVE_NEW_ACCOUNTS) { ?> Then your teacher (or the admin, for teacher accounts) approves it, and you can log in.<?php } ?></small></p>
<button>Create account</button>
</form>
<script>
(()=>{const r=document.getElementById('su-role');if(!r)return;
const set=()=>{const t=r.value==='teacher';document.querySelectorAll('#signup .su-student').forEach(e=>e.hidden=t);document.querySelectorAll('#signup .su-teacher').forEach(e=>e.hidden=!t);
document.getElementById('su-idl').textContent=t?'Teacher ID':'Student ID';const y=document.querySelector('#signup select[name=year]');if(y)y.required=!t;const c=document.querySelector('#signup input[name=code]');if(c)c.required=t};
r.addEventListener('change',set);set()})();
</script>
<?php foot();
