<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="A little kitchen inspiration from Minitz. Explore spice blends, recipes and the joy of cooking.">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','Minitz — a little kitchen magic')</title>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/store.css?v=2">
<script src="/assets/store.js?v=2" defer></script>
</head>
<body class="@yield('bodyClass')">
<a class="skip" href="#main">Skip to content</a>
<div class="announcement">{{ $settings['announcement'] ?? 'A little help from Minitz. Dinner is still yours.' }} <span>Made for your kind of cooking</span></div>
<header class="site-header"><a class="wordmark" href="/" aria-label="Minitz home">minitz<span>✳</span></a><nav class="main-nav" id="main-nav" aria-label="Main navigation"><a href="/shop" @class(['active'=>request()->is('shop*')])>The blends</a><a href="/recipes" @class(['active'=>request()->is('recipes*')])>The kitchen</a><a href="/pages/about">Our story</a><a href="/pages/contact">Say hello</a></nav><div class="nav-actions"><a class="bag-link" href="/cart">Bag <span>{{ $cartCount }}</span></a><button class="menu-toggle" aria-controls="main-nav" aria-expanded="false" aria-label="Toggle menu">☰</button></div></header>
@if(session('success'))<div class="toast" role="status">{{ session('success') }}<button type="button" aria-label="Dismiss message" data-dismiss>×</button></div>@endif
@if(session('error'))<div class="notice error" role="alert">{{ session('error') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert"><strong>A couple of details need a second look.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<main id="main">@yield('content')</main>
<section class="bottom-invite"><span class="eyebrow">GOOD FOOD STARTS WITH A LITTLE CURIOSITY</span><h2>See you in<br>the kitchen<span class="spark">✳</span></h2><a class="button button-dark" href="/shop">Find your flavour</a></section>
<footer class="site-footer"><div class="footer-top"><div><a class="wordmark" href="/">minitz<span>✳</span></a><p>A little help. A lot of you.<br>Cooking made fun for everyone.</p></div><div><h3>Explore</h3><a href="/shop">The blends</a><a href="/recipes">Recipes & inspiration</a><a href="/pages/about">Our story</a><a href="/pages/contact">Contact</a></div><div><h3>The useful bits</h3><a href="/pages/shipping">Shipping & delivery</a><a href="/pages/returns">Returns & refunds</a><a href="/pages/privacy">Privacy policy</a><a href="/pages/terms">Terms & conditions</a><a href="/pages/payment">Payment terms</a></div><div><h3>Find us around</h3>@foreach(['instagram'=>'Instagram','facebook'=>'Facebook','youtube'=>'YouTube'] as $key=>$label)@if(!empty($settings[$key]))<a href="{{ $settings[$key] }}" target="_blank" rel="noopener noreferrer">{{ $label }}</a>@else<span class="footer-muted">{{ $label }} · coming soon</span>@endif @endforeach</div></div><div class="footer-bottom"><span>© {{ date('Y') }} Minitz. A little kitchen magic.</span><span>{{ $demo ? 'Preview store · sample content and prices' : 'Made for everyday cooks' }}</span></div></footer>
@stack('scripts')
</body></html>
