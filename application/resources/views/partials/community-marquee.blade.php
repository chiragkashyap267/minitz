@php
$marqueePhotos=$communityPhotos;
while($marqueePhotos->count()<3)$marqueePhotos=$marqueePhotos->concat($communityPhotos);
@endphp
<div class="community-marquee-panel">
<div class="community-photo-marquee" data-community-marquee role="region" aria-label="Food photos from your kitchens">
<div class="community-photo-track">
@for($copy=0;$copy<2;$copy++)<div class="community-photo-group" @if($copy) aria-hidden="true" inert @endif>
@foreach($marqueePhotos as $index=>$photo)<figure class="community-photo-card" @if($index>=$communityPhotos->count()) data-loop-copy aria-hidden="true" inert @endif><a href="/{{ $photo->image }}" target="_blank" rel="noopener"><img src="/{{ $photo->image }}" alt="{{ $photo->title }}" width="480" height="480" loading="lazy"></a><figcaption>{{ $photo->title }}</figcaption></figure>@endforeach
</div>@endfor
</div></div>
<div class="community-marquee-controls"><button type="button" class="text-link" data-community-pause aria-pressed="false">Pause photos</button><a class="text-link" href="/community">See all food moments ↗</a></div>
</div>