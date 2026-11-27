<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="@yield('description','A little kitchen inspiration from Minitz. Explore spice blends, recipes and the joy of cooking.')">
@stack('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','Minitz — a little kitchen magic')</title>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/customer-help.css?v=1"><script src="/assets/customer-help.js?v=1" defer></script><link rel="stylesheet" href="/assets/newsletter.css?v=2"><script src="/assets/newsletter.js?v=1" defer></script><link rel="stylesheet" href="/assets/intro.css?v=1"><script src="/assets/intro.js?v=1"></script>
<link rel="stylesheet" href="/assets/account-menu.css?v=1"><script src="/assets/account-menu.js?v=1" defer></script><link rel="stylesheet" href="/assets/store.css?v=2"><link rel="stylesheet" href="/assets/announcement.css?v=1"><link rel="stylesheet" href="/assets/forms.css?v=1"><script src="/assets/variants.js?v=1" defer></script>
<link rel="stylesheet" href="/assets/experience.css?v=2">
<link rel="stylesheet" href="/assets/paper-story.css?v=6">
<link rel="stylesheet" href="/assets/contact.css?v=1"><link rel="stylesheet" href="/assets/community.css?v=3"><link rel="stylesheet" href="/assets/home-reviews.css?v=1"><link rel="stylesheet" href="/assets/blogs.css?v=2"><link rel="stylesheet" href="/assets/product-experience.css?v=9"><link rel="stylesheet" href="/assets/product-detail.css?v=4"><link rel="stylesheet" href="/assets/variants.css?v=1"><link rel="stylesheet" href="/assets/need-help.css?v=1"><script src="/assets/product-experience.js?v=1" defer></script>
<script src="/assets/paper-story.js?v=3" defer></script>
<script src="/assets/store.js?v=2" defer></script><script src="/assets/community.js?v=2" defer></script>
<script src="/assets/experience.js?v=3" defer></script>
</head>
<body class="@yield('bodyClass')">
@include('partials.brand-intro')
<a class="skip" href="#main">Skip to content</a>
@include('partials.announcement')
<header class="site-header"><a class="wordmark" href="/" aria-label="Minitz home"><img class="brand-logo" src="/assets/brand/logo.png" width="157" height="58" alt="Minitz"></a><nav class="main-nav" id="main-nav" aria-label="Main navigation"><a href="/shop" @class(['active'=>request()->is('shop*')])>Shop now</a><a href="/recipes" @class(['active'=>request()->is('recipes*')])>The kitchen</a><a href="/blogs">Food stories</a><a href="/pages/about">Our story</a><a href="/pages/contact">Say hello</a></nav><div class="nav-actions">@include('partials.account-menu')<a class="icon-button cart-trigger" href="/cart" data-open-cart aria-label="Open cart" aria-haspopup="dialog" aria-controls="cart-drawer">@include('partials.icon',['icon'=>'cart'])<span class="cart-badge" data-cart-count @if(!$cartCount) hidden @endif>{{ $cartCount }}</span></a><button class="menu-toggle" aria-controls="main-nav" aria-expanded="false" aria-label="Toggle menu">@include('partials.icon',['icon'=>'menu'])</button></div></header>
@if(session('success'))<div class="toast" role="status">{{ session('success') }}<button type="button" aria-label="Dismiss message" data-dismiss>×</button></div>@endif
@if(session('error'))<div class="notice error" role="alert">{{ session('error') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert"><strong>A couple of details need a second look.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<main id="main">@yield('content')</main>
<section class="bottom-invite"><span class="eyebrow">{{ $settings['invite_eyebrow'] }}</span><h2>{{ $settings['invite_heading'] }}<br>{{ $settings['invite_heading_accent'] }}<span class="spark">✳</span></h2><a class="button button-dark" href="/shop">Shop now</a></section>
<footer class="site-footer"><div class="footer-top"><div><a class="wordmark" href="/"><img class="brand-logo" src="/assets/brand/logo.png" width="157" height="58" alt="Minitz"></a><p>A little help. A lot of you.<br>Cooking made fun for everyone.</p></div><div><h3>Explore</h3><a href="/shop">The blends</a><a href="/recipes">Recipes & inspiration</a><a href="/blogs">Food stories</a><a href="/pages/about">Our story</a><a href="/pages/contact">Contact</a><a href="/newsletter" data-open-newsletter>Join our newsletter</a></div><div><h3>The useful bits</h3><a href="/pages/shipping">Shipping & delivery</a><a href="/pages/returns">Returns & refunds</a><a href="/pages/privacy">Privacy policy</a><a href="/pages/terms">Terms & conditions</a><a href="/pages/payment">Payment terms</a></div><div><h3>Find us around</h3>@foreach(['instagram'=>'Instagram','facebook'=>'Facebook','youtube'=>'YouTube'] as $key=>$label)@if(!empty($settings[$key]))<a href="{{ $settings[$key] }}" target="_blank" rel="noopener noreferrer">{{ $label }}</a>@else<span class="footer-muted">{{ $label }} · coming soon</span>@endif @endforeach</div></div><div class="footer-bottom"><span>© {{ date('Y') }} Minitz. A little kitchen magic.</span><span>Made for everyday cooks</span></div></footer>
<dialog id="cart-drawer" class="cart-drawer" aria-labelledby="cart-drawer-title">@include('partials.cart-drawer-content',$cartSummary)</dialog>
<dialog id="video-dialog" class="video-dialog" aria-labelledby="video-dialog-title"><div class="dialog-heading"><h2 id="video-dialog-title">From a real kitchen</h2><button type="button" class="icon-button" data-close aria-label="Close video">@include('partials.icon',['icon'=>'close'])</button></div><video controls playsinline preload="none" hidden></video><div class="video-coming-soon"><span>✳</span><h3>Your kitchen stories, coming soon.</h3><p>This is a video placeholder. Real clips will take their place.</p></div></dialog>
@include('partials.customer-help')
@include('partials.newsletter-popup')
@stack('scripts')
</body></html>
