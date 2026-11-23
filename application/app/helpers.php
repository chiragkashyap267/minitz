<?php
function mz_money(int|float $paise):string {return '₹'.number_format($paise/100,($paise%100)?2:0);}
