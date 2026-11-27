<form class="website-settings-form" method="post" action="/admin/content" enctype="multipart/form-data">
@csrf
<section class="admin-panel website-settings-card"><h2>Announcement bar</h2><p class="small-copy">The scrolling messages at the top of your website.</p><div class="form-grid">
<label>Welcome message<input name="announcement" value="{{ old('announcement',$settings['announcement']) }}" maxlength="200" required placeholder="e.g. Welcome to Minitz — a little kitchen magic."></label>
<label>Offer message<input name="announcement_offer" value="{{ old('announcement_offer',$settings['announcement_offer']) }}" maxlength="200" placeholder="e.g. Use code FIRST10 at checkout"><small>Leave blank if there is no offer. Manage the actual discount in Coupons.</small></label>
</div></section>
<section class="admin-panel website-settings-card"><h2>Main homepage banner</h2><p class="small-copy">The first image and message visitors see.</p><div class="website-banner-editor">
@php $banner=$settings['hero_image']==='assets/artwork-02.webp'?'assets/brand/kitchen-tasting.webp':$settings['hero_image']; @endphp
<div class="website-banner-preview"><img src="/{{ $banner }}" alt="Current homepage banner" width="600" height="320"><span>Current banner</span></div>
<div class="form-grid"><label class="full">Banner heading<input name="hero_heading" value="{{ old('hero_heading',$settings['hero_heading']) }}" maxlength="100" required placeholder="e.g. Kya banayein?"></label><label class="full">Short description<textarea name="hero_text" rows="3" maxlength="500" placeholder="e.g. A little kitchen magic for everyday cooking.">{{ old('hero_text',$settings['hero_text']) }}</textarea></label><label class="full">Change banner image<input type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG or WebP · Up to 2 MB. Leave empty to keep this banner.</small></label></div>
</div></section>
<section class="admin-panel website-settings-card"><h2>Contact details</h2><p class="small-copy">Used in Say hello, Need help and the WhatsApp button.</p><div class="form-grid">
<label>Support email<input type="email" name="support_email" value="{{ old('support_email',$settings['support_email']) }}" maxlength="150" placeholder="e.g. customercare@minitz.in"></label>
<label>Phone number<input name="support_phone" value="{{ old('support_phone',$settings['support_phone']) }}" maxlength="30" placeholder="e.g. +91 98765 43210"></label>
<label>WhatsApp number<input name="whatsapp" value="{{ old('whatsapp',$settings['whatsapp']) }}" inputmode="numeric" maxlength="15" placeholder="e.g. 919876543210"><small>Country code + number, without spaces or +.</small></label>
</div>
<details class="admin-extra"><summary>Social media links</summary><div class="form-grid">@foreach(['instagram'=>'Instagram','facebook'=>'Facebook','youtube'=>'YouTube'] as $key=>$label)<label>{{ $label }}<input type="url" name="{{ $key }}" value="{{ old($key,$settings[$key]) }}" maxlength="300" placeholder="https://{{ $key }}.com/your-page"></label>@endforeach</div></details>
</section>
<div class="website-settings-save"><button class="button button-blue">Save changes</button><a class="text-link" href="/" target="_blank" rel="noopener">View website</a></div>
</form>
