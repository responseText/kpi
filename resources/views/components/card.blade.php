@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl bg-white shadow-sm ring-1 ring-slate-200']) }}>
    @if ($title || $subtitle || isset($actions))
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-3">
            <div>
                @if ($title)<h3 class="font-semibold text-slate-700">{{ $title }}</h3>@endif
                @if ($subtitle)<p class="text-xs text-slate-400">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div class="{{ $padding ?? 'p-5' }}">
        {{ $slot }}
    </div>
</div>
