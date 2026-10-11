<?php
require dirname(__DIR__) . '/_boot.php'; require_post(); verify_csrf(); $id=admin_id($_POST['id'] ?? null,true); $input=admin_old($_POST); $errors=validate_admin_product(db(),$input);
if (!$errors) {
    try { save_admin_product(db(),$input,$id); flash('success','Arrangement saved.'); redirect('/admin/products.php'); }
    catch (InvalidArgumentException $error) { $errors['form']=$error->getMessage(); }
    catch (PDOException $error) { if (in_array((int)($error->errorInfo[1] ?? 0),[1452,1062],true)) $errors['form']='The catalog changed while you were editing. Please review the fields.'; else throw $error; }
}
$_SESSION['_admin_product_form']=['id'=>$id,'old'=>$input,'errors'=>$errors]; redirect('/admin/product-edit.php' . ($id ? '?id='.$id : ''));
