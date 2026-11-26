<div class="account-menu" data-account-menu>
<button type="button" class="icon-button account-link" aria-label="Account options" aria-expanded="false" aria-controls="account-dropdown" data-account-toggle>@include('partials.icon',['icon'=>'account'])</button>
<div class="account-dropdown" id="account-dropdown" hidden>
<a href="/account">{{ $customer ? 'My account / Orders' : 'Login / Sign up' }}</a>
<a href="/track-order">Track your order</a>
</div>
</div>
