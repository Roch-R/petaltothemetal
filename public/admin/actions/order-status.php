<?php
require dirname(__DIR__) . '/_boot.php'; require_post(); verify_csrf(); $id=admin_id($_POST['id'] ?? null); $status=is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
try { change_order_status(db(),$id,$status); flash('success','Order status updated.'); }
catch (ValidationException $error) { flash('error',$error->getMessage()); }
catch (InvalidArgumentException $error) { flash('error',$error->getMessage()); }
redirect('/admin/order.php?id='.$id);
