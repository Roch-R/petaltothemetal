<?php
require_once dirname(__DIR__) . '/app/init.php';
require_post(); verify_csrf();
$_SESSION = [];
if (ini_get('session.use_cookies')) { $cookie = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']); }
session_destroy(); redirect('/login.php');
