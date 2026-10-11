<?php
declare(strict_types=1);
function register_customer(PDO $db,array $input): int
{
    $name=trim(is_string($input['name'] ?? null) ? $input['name'] : '');
    $email=strtolower(trim(is_string($input['email'] ?? null) ? $input['email'] : ''));
    $password=is_string($input['password'] ?? null) ? $input['password'] : '';
    $errors=[];
    if ($name==='' || strlen($name)>100) $errors['name']='Enter your name (up to 100 characters).';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190) $errors['email']='Enter a valid email address.';
    if (strlen($password)<8 || strlen($password)>72) $errors['password']='Use a password between 8 and 72 characters.';
    if ($errors) throw new ValidationException($errors);
    try {
        $query=$db->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'customer')");
        $query->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
    } catch (PDOException $error) {
        if ((int)($error->errorInfo[1] ?? 0)===1062) throw new ValidationException(['email'=>'An account with this email already exists.']);
        throw $error;
    }
    return (int)$db->lastInsertId();
}
function authenticate_user(PDO $db,string $email,string $password): ?array
{
    $query=$db->prepare('SELECT id,name,email,password_hash,role FROM users WHERE email=?');$query->execute([strtolower(trim($email))]);$user=$query->fetch();
    if (!$user || !password_verify($password,$user['password_hash'])) return null;
    unset($user['password_hash']);return $user;
}
function current_user(): ?array
{
    $id=$_SESSION['user_id'] ?? null;
    if (!$id || !ctype_digit((string)$id)) return null;
    $query=db()->prepare('SELECT id,name,email,role FROM users WHERE id=?');$query->execute([(int)$id]);return $query->fetch() ?: null;
}
function require_customer(): array
{
    $user=current_user();
    if (!$user) { redirect('/login.php'); }
    if ($user['role']!=='customer') { http_response_code(403);exit('Customer access is required.'); }
    return $user;
}
function require_admin(): array
{
    $user=current_user();
    if (!$user) { redirect('/login.php'); }
    if ($user['role']!=='admin') { http_response_code(403);exit('Administrator access is required.'); }
    return $user;
}
