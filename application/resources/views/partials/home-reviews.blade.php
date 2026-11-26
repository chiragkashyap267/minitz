@if($homeReviews->isNotEmpty())
@php $loopReviews=$homeReviews; while($loopReviews->count()<4)$loopReviews=$loopReviews->concat($homeReviews); @endphp
<section class="section home-reviews" id="kitchen-reviews"><div class="section-top"><div><span class="eyebrow">{{ $settings['home_reviews_eyebrow'] }}</span><h2>{{ $settings['home_reviews_heading'] }}</h2></div><button type="button" class="text-link" data-reviews-pause aria-pressed="false" aria-controls="home-review-marquee">Pause reviews</button></div>
<div class="home-review-marquee" id="home-review-marquee" role="region" aria-label="Customer product reviews"><div class="home-review-track">
@for($copy=0;$copy<2;$copy++)<div class="home-review-group" @if($copy) aria-hidden="true" inert @endif>
@foreach($loopReviews as $index=>$review)
<article class="home-review-card" @if($index>=$homeReviews->count()) data-review-loop-copy aria-hidden="true" inert @endif>
<div class="home-review-person">@if($review->photo)<img src="/review-media/{{ $review->id }}" alt="Photo shared by {{ $review->display_name ?: $review->name }}" width="48" height="48" loading="lazy">@else<span class="review-initials" aria-hidden="true">{{ mz_initials($review->display_name ?: $review->name) }}</span>@endif<div><strong>{{ $review->display_name ?: $review->name }}</strong>@if($review->verified_purchase)<small>Verified purchase</small>@endif</div></div>
<div class="review-stars" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★',$review->rating) }}{{ str_repeat('☆',5-$review->rating) }}</div>
<blockquote>{{ $review->body }}</blockquote><a href="/shop/{{ $review->product_slug }}#product-reviews">{{ $review->product_name }} ↗</a>
</article>
@endforeach</div>@endfor
</div></div></section>
@endif
