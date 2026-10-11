<?php
require __DIR__ . '/_boot.php'; $id=admin_id($_GET['id'] ?? null,true); $category=['name'=>''];
if ($id) { $statement=db()->prepare('SELECT * FROM categories WHERE id=?'); $statement->execute([$id]); $category=$statement->fetch(); if (!$category) abort_request(404,'Category not found.'); }
$form=$_SESSION['_admin_category_form'] ?? []; unset($_SESSION['_admin_category_form']); $errors=[]; if (($form['id'] ?? null) === $id) { $category=array_replace($category,$form['old'] ?? []); $errors=$form['errors'] ?? []; }
$pageTitle=$id ? 'Edit category' : 'Add category'; require dirname(__DIR__, 2) . '/templates/admin_header.php';
?>
<form class="panel admin-form narrow" method="post" action="<?= e(url('/admin/actions/category-save.php')) ?>" novalidate><?= csrf_field() ?><?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?><div class="form-field"><label for="name">Category name</label><input id="name" name="name" required maxlength="100" value="<?= e($category['name']) ?>"><?php admin_error($errors,'name'); ?></div><div class="form-actions"><button class="button" type="submit">Save category</button><a class="button secondary" href="<?= e(url('/admin/categories.php')) ?>">Cancel</a></div></form>
<?php require dirname(__DIR__, 2) . '/templates/admin_footer.php'; ?>
