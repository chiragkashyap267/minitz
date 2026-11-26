@extends('layout')
@section('title','Track your order — Minitz')
@section('content')
<section class="section track-order-page"><span class="eyebrow">FROM OUR KITCHEN TO YOURS</span><h1>Track your order.</h1><p>Enter the AWB / tracking number shared with your shipment. We’ll take you to your courier’s tracking page.</p>
<form action="/track-order" method="post" class="track-order-form">@csrf
<label for="tracking-number">AWB / Tracking number<input id="tracking-number" name="tracking" value="{{ old('tracking') }}" placeholder="e.g. MZ123456789" maxlength="200" required autocomplete="off" spellcheck="false"></label>
<button class="button button-blue">Track your order</button>
<p class="small-copy">Tracking becomes available after dispatch. Looking for an order that hasn’t shipped? <a href="/account">View your orders</a> or <a href="/pages/contact">contact us</a>.</p>
</form></section>
@endsection
