<?php
require dirname(__DIR__) . '/_boot.php'; require_post(); verify_csrf(); $id=admin_id($_POST['id'] ?? null,true); $input=admin_old($_POST); $name=$input['name'] ?? '';
try { save_admin_category(db(),(string)$name,$id); flash('success','Category saved.'); redirect('/admin/categories.php'); }
catch (InvalidArgumentException $error) { $message=$error->getMessage(); }
catch (PDOException $error) { if ((int)($error->errorInfo[1] ?? 0)===1062) $message='That category name already exists.'; else throw $error; }
$_SESSION['_admin_category_form']=['id'=>$id,'old'=>$input,'errors'=>['name'=>$message]]; redirect('/admin/category-edit.php' . ($id ? '?id='.$id : ''));
