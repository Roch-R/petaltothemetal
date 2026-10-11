<?php
declare(strict_types=1);
function list_categories(PDO $db): array
{
    return $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
}
function find_product(PDO $db, int $id): ?array
{
    $query=$db->prepare('SELECT p.*,c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.is_active=1');
    $query->execute([$id]);return $query->fetch() ?: null;
}
function list_products(PDO $db,array $filters=[]): array
{
    $sql='SELECT p.*,c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.is_active=1';$params=[];
    $category=$filters['category'] ?? $filters['category_id'] ?? '';
    if (ctype_digit((string)$category) && (int)$category > 0) {$sql.=' AND p.category_id=?';$params[]=(int)$category;}
    $search=trim((string)($filters['q'] ?? $filters['search'] ?? ''));
    if ($search!=='') {$sql.=' AND (p.name LIKE ? OR p.description LIKE ?)';$term='%'.str_replace(['!','%','_'],['!!','!%','!_'],$search).'%';$sql=str_replace('LIKE ?','LIKE ? ESCAPE \'!\'',$sql);$params[]=$term;$params[]=$term;}
    $sorts=['featured'=>'p.id ASC','newest'=>'p.created_at DESC,p.id DESC','price_asc'=>'p.price ASC,p.id ASC','price_desc'=>'p.price DESC,p.id ASC','name'=>'p.name ASC,p.id ASC'];
    $sql.=' ORDER BY '.($sorts[$filters['sort'] ?? 'featured'] ?? $sorts['featured']);
    $query=$db->prepare($sql);$query->execute($params);return $query->fetchAll();
}
