<?php
require dirname(__DIR__) . '/_boot.php'; require_post(); verify_csrf(); $id=admin_id($_POST['id'] ?? null);
try { archive_admin_product(db(),$id); flash('success','Arrangement archived. Existing order history is preserved.'); }
catch (InvalidArgumentException $error) { flash('error',$error->getMessage()); }
redirect('/admin/products.php');
