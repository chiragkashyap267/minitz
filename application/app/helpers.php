<?php
function mz_money(int|float $paise):string {return '₹'.number_format($paise/100,($paise%100)?2:0);}

function mz_product_image(object $product):?string {
    return $product->image ?: (($product->slug ?? '') === 'everyday-garam-masala' ? 'assets/brand/garam-masala.png' : null);
}

function mz_on_sale(object $product):bool {
    return isset($product->compare_price) && (int)$product->compare_price > (int)$product->price;
}

function mz_placeholder(string $name,string $context=''):string {
 if($context==='partials/product-editor'&&$name==='name')return 'e.g. Artisanal Garam Masala';
 if($context==='partials/recipe-editor'&&$name==='title')return 'e.g. Sunday chicken curry';
 if($context==='product'&&$name==='body')return 'e.g. Lovely aroma. I used this in our Sunday curry and enjoyed the flavour.';
 if($context==='partials/video-editor'&&$name==='title')return 'e.g. Sunday at our kitchen table';
 if(str_starts_with($name,'quantities['))return '1';
 if(str_starts_with($name,'policy_'))return 'Example: your business-approved '.str_replace('_',' ',substr($name,7)).' policy.';
 $examples=config('placeholders',[]);if(isset($examples[$name]))return $examples[$name];
 foreach(config('content',[]) as $fields)if(isset($fields[$name]))return 'e.g. '.mb_substr(preg_replace('/\s+/u',' ',$fields[$name][1]),0,180);
 return 'e.g. '.ucfirst(str_replace('_',' ',$name));
}
function mz_initials(string $name):string {
 $parts=preg_split('/\s+/u',trim($name),-1,PREG_SPLIT_NO_EMPTY);if(!$parts)return 'M';
 return mb_strtoupper(mb_substr($parts[0],0,1).(count($parts)>1?mb_substr($parts[count($parts)-1],0,1):mb_substr($parts[0],1,1)));
}
