@php
$tvId=$settings['tv_video_id']??'';
$validTv=(bool)preg_match('/^[A-Za-z0-9_-]{11}$/D',$tvId);
$showTv=($settings['tv_enabled']??'0')==='1'&&$validTv;
@endphp
<div class="admin-tv-grid">
<form class="admin-panel editor-form" method="post" action="/admin/tv">@csrf
<h2>Stream setup</h2>
<p><span class="status {{ $showTv?'live':'muted' }}">{{ $showTv?'Broadcast shown on website':'Kitchen story shown on website' }}</span></p>
<div class="form-grid">
<label class="full">Stream title<input name="tv_title" maxlength="160" value="{{ old('tv_title',$settings['tv_title']??'') }}" placeholder="e.g. Sunday cooking with Anupam & Dilip"></label>
<label class="full">YouTube live stream link<input type="url" name="tv_video_url" maxlength="500" value="{{ old('tv_video_url',$settings['tv_video_url']??'') }}" placeholder="https://www.youtube.com/live/..."><small>Paste the video’s Share link. Channel links and stream keys are not needed.</small></label>
<label class="checkbox full"><input type="hidden" name="tv_enabled" value="0"><input type="checkbox" name="tv_enabled" value="1" @checked(old('tv_enabled',$settings['tv_enabled']??'0')==='1')> Show this stream on the website</label>
</div>
<button class="button button-blue">Save stream settings</button>
<p class="small-copy">Untick and save to return to the kitchen story. After a live stream ends, leave it enabled to show its replay if available on YouTube.</p>
</form>
<section class="admin-panel editor-form"><h2>Start a broadcast</h2>
<ol class="admin-tv-steps"><li>Open YouTube Studio and create or schedule a live stream.</li><li>Choose Public or Unlisted visibility and allow embedding.</li><li>Copy its Share link, paste it here and save with the checkbox enabled.</li><li>Start the stream on YouTube. Visitors can watch it inside Minitz TV.</li></ol>
<a class="button button-outline" href="https://studio.youtube.com/" target="_blank" rel="noopener">Open YouTube Studio ↗</a>
<p class="small-copy">First-time live access can take up to 24 hours. The YouTube mobile app requires at least 50 subscribers; a computer webcam or encoder is another option.</p>
<p class="small-copy">This panel publishes the player; your camera and live broadcast are controlled on YouTube. It does not detect whether a stream is currently live.</p>
<a class="text-link" href="https://support.google.com/youtube/answer/2474026" target="_blank" rel="noopener">YouTube setup guide ↗</a>
</section>
</div>
@if($validTv)
<section class="admin-panel editor-form admin-tv-preview"><h2>Saved stream preview</h2><p>{{ $settings['tv_title']?:'Minitz TV broadcast' }}</p>
<details class="admin-extra"><summary>Open YouTube player preview</summary><iframe loading="lazy" src="https://www.youtube-nocookie.com/embed/{{ $tvId }}" title="Saved Minitz TV stream preview" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></details>
<a class="text-link" href="/#minitz-tv" target="_blank" rel="noopener">View website ↗</a>
</section>
@endif
