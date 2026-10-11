<?php
require dirname(__DIR__) . '/_boot.php'; require_post(); verify_csrf(); $id=admin_id($_POST['id'] ?? null);
try { delete_admin_category(db(),$id); flash('success','Unused category deleted.'); }
catch (InvalidArgumentException $error) { flash('error',$error->getMessage()); }
catch (PDOException $error) { if ((int)($error->errorInfo[1] ?? 0)===1451) flash('error','This category contains products and cannot be deleted.'); else throw $error; }
redirect('/admin/categories.php');
