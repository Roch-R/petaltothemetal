<?php
require_once dirname(__DIR__, 2) . '/app/init.php'; require_post(); verify_csrf();
$email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : ''; $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$return = safe_return_route($_POST['return'] ?? '/shop.php');
$user = authenticate_user(db(), $email, $password);
if (!$user) { $_SESSION['_login_form'] = ['email'=>$email,'error'=>'That email and password combination did not match. Please try again.']; redirect('/login.php' . ($return === '/shop.php' ? '' : '?return=' . rawurlencode($return))); }
session_regenerate_id(true); clear_cart(); unset($_SESSION['_pending_checkout'], $_SESSION['_recent_checkout'], $_SESSION['_login_return']); $_SESSION['user_id'] = (int) $user['id'];
flash('success', 'Welcome back, ' . $user['name'] . '.'); redirect($user['role'] === 'admin' ? '/admin/index.php' : $return);
