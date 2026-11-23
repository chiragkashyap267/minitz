<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@if($icon==='account')<circle cx="12" cy="8" r="3.5"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/>
@elseif($icon==='cart')<path d="M2 3h2l2.5 12h12L21 7H5"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>
@elseif($icon==='close')<path d="m6 6 12 12M18 6 6 18"/>
@elseif($icon==='tag')<path d="M3 3h8l10 10-8 8L3 11Z"/><circle cx="7.5" cy="7.5" r="1"/>
@elseif($icon==='play')<path d="m9 5 11 7-11 7Z"/>
@else<path d="M4 6h16M4 12h16M4 18h16"/>
@endif
</svg>
