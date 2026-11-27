@extends('layout')
@section('title','Forgot password — Minitz')
@section('content')<section class="section track-order-page"><h1>Forgot password?</h1><p>Enter the email address used for your customer account.</p><form action="/account/forgot-password" method="post" class="track-order-form">@csrf<label>Email address<input type="email" name="email" required maxlength="150" autocomplete="email" placeholder="e.g. you@example.com"></label><button class="button button-blue">Send reset link</button><a href="/account">Back to sign in</a></form></section>@endsection
