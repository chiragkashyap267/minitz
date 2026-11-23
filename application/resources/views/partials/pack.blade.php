<div class="pack-stage pack-{{ $product->colour }}">
@if($product->image)<img class="product-photo" src="/{{ $product->image }}" alt="{{ $product->name }}" loading="lazy" width="360" height="400">@else
<div class="spice-scatter" aria-hidden="true">✳ · ✦ · ✳</div><div class="product-pack"><div class="pack-seal"></div><span class="pack-brand">minitz<span>✳</span></span><span class="pack-sub">A LITTLE KITCHEN MAGIC</span><strong>{{ $product->name }}</strong><span class="pack-flower" aria-hidden="true">✳</span><span class="pack-weight">{{ $product->weight }} <span>BLEND NO. {{ str_pad($product->id,2,'0',STR_PAD_LEFT) }}</span></span></div>
<span class="image-label">PACK DESIGN PLACEHOLDER</span>@endif
</div>
