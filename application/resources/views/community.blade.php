@extends('layout')
@section('title','Made by you — Minitz')
@section('content')
<section class="page-hero"><h1>Made by you.</h1><p>Real food, from your kitchens.</p><a class="button button-blue" href="/#made-by-you">Share your food photo</a></section>
<section class="section">@if($communityPhotos->isNotEmpty())@include('partials.community-cards')<div class="pagination">{{ $communityPhotos->links('partials.pagination') }}</div>@else<p>Your food moment could be the first.</p>@endif</section>
@endsection
