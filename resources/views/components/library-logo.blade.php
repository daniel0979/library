@props([
    'size' => 'md',
    'withText' => true,
])

@php
    $sizes = [
        'sm' => 'h-9 w-9',
        'md' => 'h-11 w-11',
        'lg' => 'h-14 w-14',
    ];
    $wrapperSize = $sizes[$size] ?? $sizes['md'];
@endphp

<div class="flex items-center gap-3">
    <div class="{{ $wrapperSize }} rounded-2xl bg-slate-900 logo-mark flex items-center justify-center">
        <svg viewBox="0 0 72 72" class="h-7 w-7 text-white" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M11 19C11 15.6863 13.6863 13 17 13H33V59H17C13.6863 59 11 56.3137 11 53V19Z" fill="currentColor"/>
            <path d="M39 13H55C58.3137 13 61 15.6863 61 19V53C61 56.3137 58.3137 59 55 59H39V13Z" fill="currentColor" opacity="0.78"/>
            <path d="M33 23H39V49H33V23Z" fill="white"/>
            <circle cx="51.5" cy="24.5" r="2.5" fill="white"/>
        </svg>
    </div>

    @if($withText)
        <div>
            <p class="font-bold text-lg leading-tight">AetherShelf Library</p>
            <p class="text-xs text-slate-500">Knowledge. Membership. Revenue.</p>
        </div>
    @endif
</div>
