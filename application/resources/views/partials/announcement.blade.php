<div class="announcement announcement-marquee" role="region" aria-label="Minitz announcements">
<div class="announcement-track">
@for($copy=0;$copy<2;$copy++)
<div class="announcement-group" @if($copy) aria-hidden="true" inert @endif>
@for($repeat=0;$repeat<4;$repeat++)
<span class="announcement-message" @if($repeat) aria-hidden="true" @endif>{{ $settings['announcement'] ?? 'Welcome to Minitz — a little kitchen magic.' }}<b aria-hidden="true">✳</b>{{ $settings['announcement_offer'] ?? 'Use code FIRST10 at checkout' }}<b aria-hidden="true">✳</b></span>
@endfor
</div>
@endfor
</div>
<button type="button" class="announcement-pause" aria-label="Pause announcements" aria-pressed="false">Ⅱ</button>
</div>
<script src="/assets/announcement.js?v=1" defer></script>
