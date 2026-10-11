<?php
require_once dirname(__DIR__, 2) . '/app/init.php'; require_post(); verify_csrf();
if (current_user()) { redirect('/shop.php'); }
$return = safe_return_route($_POST['return'] ?? '/shop.php');
$returnQuery = $return === '/shop.php' ? '' : '?return=' . rawurlencode($return);
$input = ['name' => is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '', 'email' => is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '', 'password' => is_string($_POST['password'] ?? null) ? $_POST['password'] : ''];
try { register_customer(db(), $input); flash('success', 'Your account is ready. Log in to find your flowers.'); redirect('/login.php' . $returnQuery); }
catch (ValidationException $error) { $_SESSION['_register_form'] = ['old'=>['name'=>$input['name'],'email'=>$input['email']], 'errors'=>$error->errors]; redirect('/register.php' . $returnQuery); }
catch (InvalidArgumentException $error) { $_SESSION['_register_form'] = ['old'=>['name'=>$input['name'],'email'=>$input['email']], 'errors'=>[$error->getMessage()]]; redirect('/register.php' . $returnQuery); }
