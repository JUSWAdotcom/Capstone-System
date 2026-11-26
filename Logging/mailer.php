<?php
// Sending email (the verification code and the forgot-password code) with plain SMTP. No extra library is needed.
// The settings are filled in on the page Admin > Email setup (they are saved in the database).
// If nothing was saved there, the MAIL_HOST, MAIL_PORT, MAIL_SECURE, MAIL_USER, MAIL_PASS ... at the top of config.php are used.
// When there is no mail server at all, nothing is emailed: sign-up / password codes cannot be sent (SHOW_CODES_ON_SCREEN shows them, for testing only). The admin's step-2 code is shown on the screen so the admin can still log in and set up email.

// The keys of the settings table that belong to the email setup
function mail_keys() {
    return ['mail_host', 'mail_port', 'mail_secure', 'mail_user', 'mail_pass', 'mail_from_name', 'mail_check'];
}

// The mail settings as one list: HOST, PORT, SECURE, USER, PASS, FROM, FROM_NAME, CHECK.
// What was saved on the Email setup page wins. Otherwise the settings of config.php are used.
// Call mail_set(true) after saving, so the new values are read again.
function mail_set($reload = false) {
    static $set = null;
    if ($set !== null && !$reload) {
        return $set;
    }
    global $pdo;
    $saved = [];
    try {
        $q = $pdo->query("SELECT k,v FROM settings WHERE k IN ('" . implode("','", mail_keys()) . "')");
        foreach ($q as $r) {
            $saved[$r['k']] = (string)$r['v'];
        }
    } catch (Throwable $x) {
        $saved = []; // no settings table yet: use config.php
    }
    if (($saved['mail_host'] ?? '') !== '') {
        $set = [
            'HOST' => $saved['mail_host'],
            'PORT' => (int)($saved['mail_port'] ?? 587) ?: 587,
            'SECURE' => in_array($saved['mail_secure'] ?? 'tls', ['tls', 'ssl', ''], true) ? ($saved['mail_secure'] ?? 'tls') : 'tls',
            'USER' => $saved['mail_user'] ?? '',
            'PASS' => $saved['mail_pass'] ?? '',
            'FROM' => '',
            'FROM_NAME' => ($saved['mail_from_name'] ?? '') !== '' ? $saved['mail_from_name'] : 'CodeSmartAi-X',
            'CHECK' => ($saved['mail_check'] ?? '1') !== '0',
            'SOURCE' => 'page',
        ];
    } else {
        $set = [
            'HOST' => MAIL_HOST, 'PORT' => MAIL_PORT, 'SECURE' => MAIL_SECURE, 'USER' => MAIL_USER, 'PASS' => MAIL_PASS,
            'FROM' => MAIL_FROM, 'FROM_NAME' => MAIL_FROM_NAME, 'CHECK' => true,
            'SOURCE' => MAIL_HOST !== '' ? 'config' : 'none',
        ];
    }
    return $set;
}

// is email sending set up?
function mail_ready() {
    return mail_set()['HOST'] !== '';
}

// how the secure connection is checked: a real mail server (Gmail...) has a real certificate.
// XAMPP on Windows often has no list of trusted certificates set, so we point PHP to the one that comes with XAMPP.
function mail_tls_options($check = true) {
    $o = ['verify_peer' => $check, 'verify_peer_name' => $check];
    if ($check && (string)ini_get('openssl.cafile') === '') {
        foreach ([dirname(PHP_BINARY) . '/extras/ssl/cacert.pem', 'C:/xampp/php/extras/ssl/cacert.pem', 'C:/xampp/apache/bin/curl-ca-bundle.crt'] as $f) {
            if (@is_file($f)) {
                $o['cafile'] = $f;
                break;
            }
        }
    }
    return $o;
}

// a word in a mail header (a name or a subject) that may contain any letter
function mail_word($text) {
    $text = trim(preg_replace('/[\r\n]+/', ' ', (string)$text));
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

// reads the answer of the mail server (it can be several lines)
function smtp_reply($fp) {
    $all = '';
    while (($line = fgets($fp, 515)) !== false) {
        $all .= $line;
        if (strlen($line) < 4 || $line[3] !== '-') {
            break;
        }
    }
    return $all;
}

// sends one command (or only waits for the answer when $cmd is null) and checks the answer code
function smtp_ask($fp, $cmd, $good, &$err) {
    if ($cmd !== null) {
        fwrite($fp, $cmd . "\r\n");
    }
    $r = smtp_reply($fp);
    if (!in_array((int)substr($r, 0, 3), $good, true)) {
        $err = trim($r) !== '' ? trim($r) : 'The mail server did not answer.';
        return false;
    }
    return true;
}

// the talk with the mail server. Returns true when the server accepted the message.
function smtp_talk($fp, $c, $from, $to, $message, &$err) {
    $me = preg_replace('/[^A-Za-z0-9.-]/', '', (string)gethostname());
    if ($me === '') {
        $me = 'localhost';
    }
    if (!smtp_ask($fp, null, [220], $err)) return false;
    if (!smtp_ask($fp, 'EHLO ' . $me, [250], $err)) return false;
    if ($c['SECURE'] === 'tls') {
        if (!smtp_ask($fp, 'STARTTLS', [220], $err)) return false;
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $err = 'The secure (TLS) connection to the mail server failed.';
            return false;
        }
        if (!smtp_ask($fp, 'EHLO ' . $me, [250], $err)) return false;
    }
    if ($c['USER'] !== '') {
        if (!smtp_ask($fp, 'AUTH LOGIN', [334], $err)) return false;
        if (!smtp_ask($fp, base64_encode($c['USER']), [334], $err)) return false;
        if (!smtp_ask($fp, base64_encode(str_replace(' ', '', $c['PASS'])), [235], $err)) return false;
    }
    if (!smtp_ask($fp, 'MAIL FROM:<' . $from . '>', [250], $err)) return false;
    if (!smtp_ask($fp, 'RCPT TO:<' . $to . '>', [250, 251], $err)) return false;
    if (!smtp_ask($fp, 'DATA', [354], $err)) return false;
    if (!smtp_ask($fp, $message . "\r\n.", [250], $err)) return false; // the message is base64, so no line can start with a dot
    return true;
}

// sends an email: the plain text, and (version 45) a designed HTML version when $html is given.
// Returns true when it was accepted, otherwise false and $err says why ($err is for the error log and the admin, not for visitors)
function send_mail($to, $subject, $text, &$err = '', $html = null) {
    $err = '';
    if (!mail_ready()) {
        $err = 'Email is not set up (no mail server in Admin > Email setup, and MAIL_HOST in config.php is empty).';
        return false;
    }
    $c = mail_set();
    $from = $c['FROM'] !== '' ? $c['FROM'] : $c['USER'];
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n<>]/', $to)) {
        $err = 'The receiver address is not valid.';
        return false;
    }
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $err = 'The sender address is not an email address (check the Gmail address in Admin > Email setup, or MAIL_USER in config.php).';
        return false;
    }
    $head = 'From: ' . mail_word($c['FROM_NAME']) . ' <' . $from . ">\r\n"
        . 'To: <' . $to . ">\r\n"
        . 'Subject: ' . mail_word($subject) . "\r\n"
        . 'Date: ' . date('r') . "\r\n"
        . 'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . substr(strrchr($from, '@'), 1) . ">\r\n"
        . "MIME-Version: 1.0\r\n";
    if ($html === null) {
        $message = $head . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . rtrim(chunk_split(base64_encode($text), 76, "\r\n"));
    } else {
        // two versions of the same email: email apps show the HTML one, very old ones show the text
        $b = 'csx_' . bin2hex(random_bytes(8));
        $htmlpart = "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . rtrim(chunk_split(base64_encode($html), 76, "\r\n"));
        // version 46: the logo picture travels inside the email (cid:), so it shows even on a site that is not online
        $logo = __DIR__ . '/../assets/img/logo-email.png';
        if (strpos($html, 'cid:' . MAIL_LOGO_CID) !== false && is_file($logo)) {
            $r = 'csxr_' . bin2hex(random_bytes(8));
            $htmlpart = 'Content-Type: multipart/related; boundary="' . $r . "\"\r\n\r\n"
                . '--' . $r . "\r\n" . $htmlpart . "\r\n"
                . '--' . $r . "\r\nContent-Type: image/png; name=\"logo.png\"\r\nContent-Transfer-Encoding: base64\r\n"
                . 'Content-ID: <' . MAIL_LOGO_CID . ">\r\nContent-Disposition: inline; filename=\"logo.png\"\r\n\r\n"
                . rtrim(chunk_split(base64_encode(file_get_contents($logo)), 76, "\r\n")) . "\r\n"
                . '--' . $r . '--';
        }
        $message = $head . 'Content-Type: multipart/alternative; boundary="' . $b . "\"\r\n\r\n"
            . '--' . $b . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . rtrim(chunk_split(base64_encode($text), 76, "\r\n")) . "\r\n"
            . '--' . $b . "\r\n" . $htmlpart . "\r\n"
            . '--' . $b . '--';
    }

    $address = ($c['SECURE'] === 'ssl' ? 'ssl://' : 'tcp://') . $c['HOST'] . ':' . $c['PORT'];
    $context = stream_context_create(['ssl' => mail_tls_options($c['CHECK'])]);
    $fp = @stream_socket_client($address, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) {
        $err = 'Could not connect to ' . $c['HOST'] . ':' . $c['PORT'] . ' (' . $errstr . ').';
        mail_note($err);
        return false;
    }
    stream_set_timeout($fp, 15);
    $ok = smtp_talk($fp, $c, $from, $to, $message, $err);
    @fwrite($fp, "QUIT\r\n");
    @fclose($fp);
    mail_note($ok ? '' : $err); // version 47: the admin dashboard shows the last problem
    return $ok;
}


// ---------- version 47: is the Gmail login right? (connects and logs in, sends nothing) ----------
// Returns '' when Gmail accepted the address + App password, otherwise the reason in plain words.
function mail_login_check() {
    if (!mail_ready()) {
        return 'Email is not set up.';
    }
    $c = mail_set();
    $address = ($c['SECURE'] === 'ssl' ? 'ssl://' : 'tcp://') . $c['HOST'] . ':' . $c['PORT'];
    $context = stream_context_create(['ssl' => mail_tls_options($c['CHECK'])]);
    $fp = @stream_socket_client($address, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) {
        return mail_problem('Could not connect to ' . $c['HOST'] . ':' . $c['PORT'] . ' (' . $errstr . ').');
    }
    stream_set_timeout($fp, 15);
    $err = '';
    $me = preg_replace('/[^A-Za-z0-9.-]/', '', (string)gethostname()) ?: 'localhost';
    $ok = smtp_ask($fp, null, [220], $err) && smtp_ask($fp, 'EHLO ' . $me, [250], $err);
    if ($ok && $c['SECURE'] === 'tls') {
        $ok = smtp_ask($fp, 'STARTTLS', [220], $err);
        if ($ok && !@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $err = 'The secure (TLS) connection to the mail server failed.';
            $ok = false;
        }
        $ok = $ok && smtp_ask($fp, 'EHLO ' . $me, [250], $err);
    }
    if ($ok && $c['USER'] !== '') {
        $ok = smtp_ask($fp, 'AUTH LOGIN', [334], $err) && smtp_ask($fp, base64_encode($c['USER']), [334], $err)
            && smtp_ask($fp, base64_encode(str_replace(' ', '', $c['PASS'])), [235], $err);
    }
    @fwrite($fp, "QUIT\r\n");
    @fclose($fp);
    mail_note($ok ? '' : $err);
    return $ok ? '' : mail_problem($err);
}

// the reason an email failed, in plain words
function mail_problem($err) {
    if (preg_match('/\b535\b|BadCredentials|not accepted/i', (string)$err)) {
        return 'Gmail did not accept the Gmail address and App password saved in Admin > Email. The App password is wrong, or it was made in a different Google account. Make a new App password in that same Gmail and save it again.';
    }
    if (preg_match('/connect|timed? ?out|getaddrinfo/i', (string)$err)) {
        return 'This computer could not reach Gmail. Check the internet connection.';
    }
    return 'The mail server said: ' . mb_substr(trim((string)$err), 0, 150);
}

// remembers the last email problem (for the admin dashboard), or forgets it after an email worked
function mail_note($err) {
    global $pdo;
    try {
        $pdo->prepare("DELETE FROM settings WHERE k='mail_error'")->execute();
        if ($err !== '') {
            $kind = preg_match('/\b535\b|BadCredentials|not accepted/i', $err) ? 'login' : (preg_match('/connect|timed? ?out|getaddrinfo/i', $err) ? 'connect' : 'other');
            $pdo->prepare("INSERT INTO settings(k,v) VALUES('mail_error',?)")->execute([$kind . '|' . date('M j, g:i A')]);
        }
    } catch (Throwable $x) {
    }
}


// ===================== THE EMAIL DESIGN (version 45, logo picture since version 46) =====================
const MAIL_LOGO_CID = 'logo@codesmartaix';
// Every email looks the same: a dark header with the CodeSmartAI-X name, a white card, and a footer.
// Only tables and inline styles are used, because that is what Gmail, Outlook and phone email apps understand.

// the full web address of the site, for buttons in the email (for example https://yourschool.edu/capstone-system/)
function mail_site_url() {
    $host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https' : 'http') . '://' . $host . BASE;
}

// $title: the big heading. $body: HTML that is already safe. $preview: the short line email apps show in the inbox list.
function mail_layout($title, $body, $preview = '') {
    $f = "font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
    return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="color-scheme" content="light only"><title>' . htmlspecialchars($title) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#eef1f5;">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . htmlspecialchars($preview) . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef1f5;"><tr><td align="center" style="padding:28px 12px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background:#ffffff;border:1px solid #dde2ea;border-radius:12px;' . $f . ';color:#1f2328;">'
        // header
        . '<tr><td style="background:#0d1117;border-radius:12px 12px 0 0;padding:18px 28px;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>'
        . '<td style="padding:0;line-height:0;"><img src="cid:' . MAIL_LOGO_CID . '" width="34" height="34" alt="" style="display:block;border:0;width:34px;height:34px;"></td>'
        . '<td style="padding-left:10px;font:700 18px/1 Consolas,\'Courier New\',monospace;color:#e6edf3;">CodeSmart<span style="color:#79b8ff;">AI</span>-X</td>'
        . '</tr></table></td></tr>'
        // body
        . '<tr><td style="padding:30px 28px 8px;font-size:15px;line-height:1.6;">'
        . '<h1 style="margin:0 0 14px;font-size:22px;line-height:1.3;color:#0d1117;">' . htmlspecialchars($title) . '</h1>'
        . $body
        . '</td></tr>'
        // footer
        . '<tr><td style="padding:18px 28px 22px;border-top:1px solid #eef1f5;font-size:12px;line-height:1.6;color:#6a737d;">'
        . 'CodeSmartAI-X &middot; Learn. Practice. Code.<br>This is an automatic email. Please do not reply to it.'
        . '</td></tr></table>'
        . '</td></tr></table></body></html>';
}

// the big code box (the 6 digits stay together when copied)
function mail_code_box($code) {
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:6px 0 18px;"><tr>'
        . '<td align="center" style="background:#f6f8fa;border:1px solid #d0d7de;border-radius:10px;padding:18px 10px 18px 20px;'
        . 'font:700 34px/1 Consolas,\'Courier New\',monospace;letter-spacing:10px;color:#0d1117;">' . htmlspecialchars($code) . '</td></tr></table>';
}

// a button (a link that looks like a button)
function mail_button($label, $url) {
    return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 20px;"><tr>'
        . '<td style="background:#2563eb;border-radius:8px;"><a href="' . htmlspecialchars($url) . '" style="display:inline-block;padding:12px 24px;'
        . "font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;\">"
        . htmlspecialchars($label) . '</a></td></tr></table>';
}

// the small grey line at the end of the message
function mail_small($text) {
    return '<p style="margin:0 0 16px;font-size:13px;line-height:1.6;color:#57606a;">' . $text . '</p>';
}

// the 3 code emails: 'signup' (verify the email), 'reset' (forgot password), 'admin' (admin log in, step 2)
// returns ['subject' => ..., 'text' => ..., 'html' => ...]
function mail_code_message($kind, $name, $code) {
    $name = trim((string)$name) !== '' ? trim((string)$name) : 'there';
    $hi = '<p style="margin:0 0 12px;">Hi ' . htmlspecialchars($name) . ',</p>';
    $never = 'Never share this code with anyone. Teachers and the CodeSmartAI-X team will never ask you for it.';
    if ($kind === 'reset') {
        $subject = 'Your CodeSmartAI-X password reset code';
        $title = 'Reset your password';
        $lead = 'We got a request to reset the password of your CodeSmartAI-X account. Type this code on the website to choose a new password:';
        $mins = 15;
        $ignore = 'If you did not ask for this, you can ignore this email. Your password was not changed.';
    } elseif ($kind === 'change') {
        $subject = 'Your CodeSmartAI-X code to change your password';
        $title = 'Change your password';
        $lead = 'You asked to change the password of your CodeSmartAI-X account. Type this code on the Change password page:';
        $mins = 15;
        $ignore = 'If this was not you, someone may be using your account: log in, change your password, and tell your teacher.';
    } elseif ($kind === 'admin') {
        $subject = 'Your CodeSmartAI-X admin login code';
        $title = 'Admin login code';
        $lead = 'Someone typed the right admin password. To finish logging in, type this code on the website:';
        $mins = 30;
        $ignore = 'If this was not you, change the admin password right away: someone knows it.';
    } else {
        $subject = 'Your CodeSmartAI-X verification code';
        $title = 'Verify your email';
        $lead = 'Welcome to CodeSmartAI-X! Type this code on the website to finish creating your account:';
        $mins = 30;
        $ignore = 'If you did not sign up, you can ignore this email.';
    }
    $html = mail_layout($title,
        $hi . '<p style="margin:0 0 14px;">' . htmlspecialchars($lead) . '</p>'
        . mail_code_box($code)
        . '<p style="margin:0 0 16px;">The code works for <b>' . $mins . ' minutes</b>.</p>'
        . mail_small(htmlspecialchars($ignore) . '<br>' . htmlspecialchars($never)),
        'Your code is ' . $code . '. It works for ' . $mins . ' minutes.');
    $text = 'Hi ' . $name . ",\n\n" . $lead . "\n\n    " . $code . "\n\nThe code works for " . $mins . " minutes.\n" . $ignore . "\n" . $never . "\n\nCodeSmartAI-X - Learn. Practice. Code.\n";
    return ['subject' => $subject, 'text' => $text, 'html' => $html];
}

// the email that says an account was approved
function mail_approved_message($name, $role, $sid) {
    $what = $role === 'teacher' ? 'Teacher ID' : 'Student ID';
    $url = mail_site_url() . 'Logging/login.php?open=1';
    $html = mail_layout('Your account is approved',
        '<p style="margin:0 0 12px;">Hi ' . htmlspecialchars($name) . ',</p>'
        . '<p style="margin:0 0 14px;">Good news: your CodeSmartAI-X account was approved. You can now log in with your ' . $what
        . ' <b>' . htmlspecialchars($sid) . '</b> and your password.</p>'
        . mail_button('Log in to CodeSmartAI-X', $url)
        . mail_small('If the button does not work, copy this link into your browser:<br><a href="' . htmlspecialchars($url) . '" style="color:#2563eb;">' . htmlspecialchars($url) . '</a>'),
        'You can now log in with your ' . $what . '.');
    $text = 'Hi ' . $name . ",\n\nYour CodeSmartAI-X account was approved. You can now log in with your " . $what . ' (' . $sid . ") and your password.\n\n" . $url . "\n\nCodeSmartAI-X - Learn. Practice. Code.\n";
    return ['subject' => 'Your CodeSmartAI-X account is approved', 'text' => $text, 'html' => $html];
}

