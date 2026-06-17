@props(['status' => 'pending'])

@php
    $map = [
        'pass' => ['ผ่าน', 'bg-emerald-100 text-emerald-700 ring-emerald-200'],
        'fail' => ['ไม่ผ่าน', 'bg-red-100 text-red-700 ring-red-200'],
        'pending' => ['รอบันทึก', 'bg-slate-100 text-slate-600 ring-slate-200'],
    ];
    [$label, $classes] = $map[$status] ?? $map['pending'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset $classes"]) }}>
    {{ $label }}
</span>
