<?php
function mz_money(int|float $paise):string {return '₹'.number_format($paise/100,($paise%100)?2:0);}

function mz_product_image(object $product):?string {
    return $product->image ?: (($product->slug ?? '') === 'everyday-garam-masala' ? 'assets/brand/garam-masala.png' : null);
}

function mz_on_sale(object $product):bool {
    return isset($product->compare_price) && (int)$product->compare_price > (int)$product->price;
}
