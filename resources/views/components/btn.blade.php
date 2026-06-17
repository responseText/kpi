@props(['href' => null, 'variant' => 'primary', 'type' => 'button'])

@php
    $variants = [
        'primary' => 'bg-indigo-600 text-white hover:bg-indigo-700',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        'ghost' => 'text-slate-600 hover:bg-slate-100',
    ];
    $base = 'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1';
    $cls = $base . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</button>
@endif
