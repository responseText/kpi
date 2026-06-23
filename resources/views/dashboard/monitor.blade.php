@php
    use App\Models\KpiIndicator;
    $grouped = $indicators->groupBy('level');
    $statusRing = ['pass' => 'ring-emerald-400', 'fail' => 'ring-red-400', 'pending' => 'ring-slate-300'];
    $statusBg = ['pass' => 'bg-emerald-500', 'fail' => 'bg-red-500', 'pending' => 'bg-slate-400'];
    $refresh = (int) request()->integer('refresh', 60);
@endphp
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="{{ $refresh }}">
    <title>Monitor — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-900 text-slate-100 antialiased">
<div class="min-h-full p-4 sm:p-6 2xl:p-10">

    {{-- Header --}}
    <header class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white sm:text-3xl 2xl:text-4xl">ตัวชี้วัดผลงาน · รพ.ทองแสนขัน</h1>
            <p class="text-slate-400 sm:text-lg">ปี {{ $year }} · ปรับปรุง {{ \App\Services\PeriodCalculator::thaiDate(now()) }} {{ now()->format('H:i') }} น.</p>
        </div>
        <div class="flex gap-3 text-center">
            @foreach (['pass' => ['ผ่าน','text-emerald-400'], 'fail' => ['ไม่ผ่าน','text-red-400'], 'pending' => ['รอบันทึก','text-slate-300']] as $k => $meta)
                @php $tot = array_sum(array_column($summary, $k)); @endphp
                <div class="rounded-2xl bg-white/5 px-5 py-3 ring-1 ring-white/10">
                    <div class="text-3xl font-bold {{ $meta[1] }} 2xl:text-5xl">{{ $tot }}</div>
                    <div class="text-xs text-slate-400 2xl:text-base">{{ $meta[0] }}</div>
                </div>
            @endforeach
        </div>
    </header>

    {{-- Levels --}}
    <div class="space-y-8">
        @foreach (KpiIndicator::LEVELS as $levelKey => $levelName)
            @php $items = $grouped->get($levelKey, collect()); @endphp
            @if ($items->isNotEmpty())
                <section>
                    <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold text-indigo-300 2xl:text-2xl">
                        <span class="h-2 w-2 rounded-full bg-indigo-400"></span> {{ $levelName }}
                        <span class="text-sm text-slate-500">({{ $items->count() }} ตัวชี้วัด)</span>
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                        @foreach ($items as $ind)
                            @php $st = $statuses[$ind->id] ?? 'pending'; @endphp
                            <div class="rounded-2xl bg-white/5 p-4 ring-2 {{ $statusRing[$st] }} 2xl:p-6">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="font-semibold leading-snug text-white 2xl:text-xl">{{ $ind->name }}</h3>
                                    <span class="h-3 w-3 shrink-0 rounded-full {{ $statusBg[$st] }} mt-1"></span>
                                </div>
                                <p class="mt-1 text-xs text-slate-400 2xl:text-sm">{{ $ind->main?->category?->name }}</p>

                                {{-- เป้าหมาย/ผลแต่ละช่วง --}}
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach ($ind->targets as $t)
                                        @php $rs = $t->result?->pass_status ?? 'pending'; @endphp
                                        <span class="rounded-md px-2 py-1 text-[11px] font-medium ring-1 ring-inset
                                            {{ $rs === 'pass' ? 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30'
                                             : ($rs === 'fail' ? 'bg-red-500/15 text-red-300 ring-red-500/30'
                                             : 'bg-slate-500/15 text-slate-300 ring-slate-500/30') }}"
                                            title="{{ $t->thai_range }}">
                                            {{ $t->period_label }}:
                                            {{ $t->result?->result_value !== null ? rtrim(rtrim((string) $t->result->result_value, '0'), '.') : '—' }}{{ $ind->unit ? ' '.$ind->unit : '' }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach

        @if ($indicators->isEmpty())
            <div class="rounded-2xl bg-white/5 py-20 text-center text-slate-400 ring-1 ring-white/10">ยังไม่มีข้อมูลตัวชี้วัดในปีนี้</div>
        @endif
    </div>
</div>
</body>
</html>
