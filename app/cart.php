<?php
declare(strict_types=1);
function get_cart(): array
{
    return is_array($_SESSION['_cart'] ?? null) ? $_SESSION['_cart'] : [];
}
function clear_cart(): void
{
    unset($_SESSION['_cart'],$_SESSION['_pending_checkout']);
}
function remove_cart_item(int $id): void
{
    unset($_SESSION['_cart'][$id],$_SESSION['_pending_checkout']);
}
function update_cart_item(PDO $db,int $id,int $quantity): void
{
    if ($id<1 || $quantity<1 || $quantity>999) throw new ValidationException(['quantity'=>'Choose a quantity between 1 and 999.']);
    $product=find_product($db,$id);
    if (!$product || $quantity>(int)$product['stock_quantity']) throw new ValidationException(['quantity'=>'That arrangement or quantity is no longer available.']);
    if (!array_key_exists($id,get_cart()) && count(get_cart()) >=100) throw new ValidationException(['cart'=>'Your cart can contain at most 100 arrangements.']);
    $_SESSION['_cart'][$id]=$quantity;unset($_SESSION['_pending_checkout']);
}
function add_cart_item(PDO $db,int $id,int $quantity): void
{
    if ($quantity<1 || $quantity>999) throw new ValidationException(['quantity'=>'Choose a quantity between 1 and 999.']);
    update_cart_item($db,$id,(int)(get_cart()[$id] ?? 0)+$quantity);
}
function cart_items(PDO $db): array
{
    $items=[];
    foreach (get_cart() as $id=>$quantity) {
        $query=$db->prepare('SELECT p.*,c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=?');$query->execute([(int)$id]);$product=$query->fetch();
        if (!$product) continue;
        $product['quantity']=(int)$quantity;$product['line_total']=money_to_minor($product['price'])*(int)$quantity;
        $items[]=$product;
    }
    return $items;
}
function cart_summary(PDO $db): array
{
    $items=cart_items($db);$subtotal=array_sum(array_column($items,'line_total'));
    return ['items'=>$items,'subtotal'=>$subtotal,'total'=>$subtotal,'fee'=>0];
}
