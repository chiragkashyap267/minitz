<section class="team-section" id="team" aria-labelledby="team-heading">
<div class="team-intro"><span class="eyebrow">FOOD, MUSIC & THE PEOPLE IN BETWEEN</span><h2 id="team-heading">Meet the Minitz team.</h2><p>The people behind the blends, from the first recipe to the final pack.</p></div>
<div class="team-grid">@foreach(config('team',[]) as $member)
<article class="team-card"><img src="/{{ $member['photo'] }}" alt="{{ $member['name'] }}" width="375" height="399" loading="lazy"><div class="team-card-copy"><h3>{{ $member['name'] }}</h3><p class="team-role">{{ $member['role'] }}</p><p>{{ $member['bio'] }}</p></div></article>
@endforeach</div></section>
