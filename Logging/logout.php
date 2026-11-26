<?php
require __DIR__.'/../config.php';

// only the Log out button (a form) logs out. Opening this page by a link does nothing.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $c = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
    }
    session_destroy();
    header('Location: '.BASE.'Logging/login.php');
    exit;
}

if (isset($_SESSION['user'])) {
    header('Location: '.BASE.'Dashboard/dashboard.php');
} else {
    header('Location: '.BASE.'Logging/login.php');
}
