@extends('layout')
@section('title','Kitchen notes — Minitz')
@section('content')<section class="section newsletter-page"><span class="eyebrow">FROM THE MINITZ KITCHEN</span><h1>{{ $settings['newsletter_heading'] }}</h1><p>{{ $settings['newsletter_text'] }}</p>@include('partials.newsletter-form',['source'=>'page'])</section>@endsection
