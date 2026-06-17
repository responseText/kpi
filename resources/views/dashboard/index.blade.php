@php use App\Models\KpiIndicator; @endphp

<x-layouts.app title="แดชบอร์ด" header="แดชบอร์ดตัวชี้วัด">
    {{-- ตัวกรองปี + ลิงก์ Monitor --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-slate-600">ปี</label>
            <select name="year" onchange="this.form.submit()"
                class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @forelse ($years as $y)
                    <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                @empty
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforelse
            </select>
        </form>

        <a href="{{ route('monitor', ['year' => $year]) }}" target="_blank"
           class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-3 py-2 text-sm font-medium text-white hover:bg-slate-900">
            <x-icon name="dashboard" class="w-4 h-4" /> เปิดโหมด Monitor (ทีวี)
        </a>
    </div>

    {{-- การ์ดสรุปตามระดับ --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach (KpiIndicator::LEVELS as $key => $levelName)
            @php $s = $summary[$key]; @endphp
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-700">{{ $levelName }}</h3>
                    <span class="text-2xl font-bold text-slate-800">{{ $s['total'] }}</span>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-emerald-50 py-2">
                        <div class="text-lg font-bold text-emerald-600">{{ $s['pass'] }}</div>
                        <div class="text-[11px] text-emerald-700">ผ่าน</div>
                    </div>
                    <div class="rounded-lg bg-red-50 py-2">
                        <div class="text-lg font-bold text-red-600">{{ $s['fail'] }}</div>
                        <div class="text-[11px] text-red-700">ไม่ผ่าน</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 py-2">
                        <div class="text-lg font-bold text-slate-500">{{ $s['pending'] }}</div>
                        <div class="text-[11px] text-slate-500">รอบันทึก</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- กราฟภาพรวม --}}
    @php
        $totPass = array_sum(array_column($summary, 'pass'));
        $totFail = array_sum(array_column($summary, 'fail'));
        $totPending = array_sum(array_column($summary, 'pending'));
        $totAll = $totPass + $totFail + $totPending;
    @endphp
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-card title="ภาพรวมสถานะ" class="lg:col-span-1">
            <div class="relative mx-auto" style="max-width: 240px;">
                <canvas id="overviewChart" data-pass="{{ $totPass }}" data-fail="{{ $totFail }}" data-pending="{{ $totPending }}"></canvas>
            </div>
            <div class="mt-4 flex justify-center gap-4 text-xs">
                <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-emerald-500"></span> ผ่าน {{ $totPass }}</span>
                <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-red-500"></span> ไม่ผ่าน {{ $totFail }}</span>
                <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-slate-400"></span> รอ {{ $totPending }}</span>
            </div>
        </x-card>

        <x-card title="สัดส่วนรายระดับ" class="lg:col-span-2">
            <canvas id="levelChart"
                data-labels="{{ json_encode(array_values(\App\Models\KpiIndicator::LEVELS), JSON_UNESCAPED_UNICODE) }}"
                data-pass="{{ json_encode(array_column($summary, 'pass')) }}"
                data-fail="{{ json_encode(array_column($summary, 'fail')) }}"
                data-pending="{{ json_encode(array_column($summary, 'pending')) }}"
                style="max-height: 240px;"></canvas>
        </x-card>
    </div>

    @if ($totAll > 0)
    <script>
        window.addEventListener('load', () => {
            if (!window.Chart) return;
            const ov = document.getElementById('overviewChart');
            new Chart(ov, {
                type: 'doughnut',
                data: { labels: ['ผ่าน', 'ไม่ผ่าน', 'รอบันทึก'],
                    datasets: [{ data: [ov.dataset.pass, ov.dataset.fail, ov.dataset.pending],
                        backgroundColor: ['#10b981', '#ef4444', '#94a3b8'], borderWidth: 0 }] },
                options: { plugins: { legend: { display: false } }, cutout: '65%' }
            });
            const lv = document.getElementById('levelChart');
            new Chart(lv, {
                type: 'bar',
                data: { labels: JSON.parse(lv.dataset.labels),
                    datasets: [
                        { label: 'ผ่าน', data: JSON.parse(lv.dataset.pass), backgroundColor: '#10b981' },
                        { label: 'ไม่ผ่าน', data: JSON.parse(lv.dataset.fail), backgroundColor: '#ef4444' },
                        { label: 'รอบันทึก', data: JSON.parse(lv.dataset.pending), backgroundColor: '#94a3b8' },
                    ] },
                options: { responsive: true, maintainAspectRatio: false,
                    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } },
                    plugins: { legend: { position: 'bottom' } } }
            });
        });
    </script>
    @endif

    {{-- ตารางตัวชี้วัด --}}
    <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-5 py-3">
            <h3 class="font-semibold text-slate-700">รายการตัวชี้วัด ปี {{ $year }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ตัวชี้วัด</th>
                        <th class="px-5 py-3">ระดับ</th>
                        <th class="px-5 py-3">ยุทธศาสตร์</th>
                        <th class="px-5 py-3">รูปแบบ</th>
                        <th class="px-5 py-3 text-center">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($indicators as $ind)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ $ind->name }}</div>
                                @if ($ind->code)<div class="text-xs text-slate-400">{{ $ind->code }}</div>@endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $ind->level_label }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $ind->subStrategy?->strategy?->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $ind->year_type_label }} · {{ $ind->period_type_label }}</td>
                            <td class="px-5 py-3 text-center"><x-status-badge :status="$statuses[$ind->id] ?? 'pending'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">ยังไม่มีตัวชี้วัดในปีนี้</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
