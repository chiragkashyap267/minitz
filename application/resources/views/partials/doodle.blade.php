<svg class="kitchen-doodle doodle-{{ $kind??'herb' }} {{ $class??'' }}" viewBox="0 0 150 150" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
<g class="doodle-shape">
@if(($kind??'herb')==='chilli')<path d="M115 32C76 34 99 100 29 118c58 9 100-25 86-86Z"/><path d="M115 33c-5-10-4-19 4-24M108 40l16 10M58 107l20-13"/>
@elseif(($kind??'herb')==='bowl')<path d="M18 65h114c-5 37-23 54-57 54S24 102 18 65ZM16 64c4-13 114-13 118 0M61 121h29M69 39c-18-16 11-17-1-34M90 41c-15-15 12-19-1-31"/><path d="m112 57 22-39"/>
@elseif(($kind??'herb')==='star')<path d="m75 10 9 43 31-30-16 42 44 10-44 9 18 42-33-27-9 42-10-42-35 28 19-43-42-9 42-10-19-42 35 30Z"/>
@else<path d="M34 133C88 92 65 47 112 15M67 95C31 94 26 73 29 56c31 3 43 16 38 39ZM82 65c33 6 48-6 51-29-26-5-48 7-51 29ZM93 41C64 38 64 19 68 9c21 1 29 14 25 32Z"/>
@endif
</g>
</svg>
