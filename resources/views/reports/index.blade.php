@php use App\Models\KpiIndicator; $grouped = $indicators->groupBy('level'); @endphp

<x-layouts.app title="รายงานสรุปผล" header="รายงานสรุปผลตัวชี้วัด">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3 print:hidden">
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <div>
                <label class="block text-xs text-slate-500">ปี</label>
                <select name="year" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm">
                    @forelse ($years as $y)
                        <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                    @empty
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforelse
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500">ระดับ</label>
                <select name="level" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm">
                    <option value="">ทุกระดับ</option>
                    @foreach (KpiIndicator::LEVELS as $k => $v)
                        <option value="{{ $k }}" @selected($level === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <x-btn onclick="window.print()" variant="secondary"><x-icon name="report" class="w-4 h-4" /> พิมพ์รายงาน</x-btn>
    </div>

    {{-- สรุป --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach (KpiIndicator::LEVELS as $key => $name)
            @php $s = $summary[$key]; $pct = $s['total'] ? round($s['pass'] / $s['total'] * 100) : 0; @endphp
            <x-card>
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-700">{{ $name }}</h3>
                    <span class="text-sm text-slate-400">{{ $s['total'] }} ตัวชี้วัด</span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full bg-emerald-500" style="width: {{ $pct }}%"></div>
                </div>
                <div class="mt-2 flex justify-between text-xs text-slate-500">
                    <span class="text-emerald-600">ผ่าน {{ $s['pass'] }}</span>
                    <span class="text-red-600">ไม่ผ่าน {{ $s['fail'] }}</span>
                    <span>รอ {{ $s['pending'] }}</span>
                </div>
            </x-card>
        @endforeach
    </div>

    {{-- ตารางรายละเอียด --}}
    @foreach (KpiIndicator::LEVELS as $levelKey => $levelName)
        @php $items = $grouped->get($levelKey, collect()); @endphp
        @if ($items->isNotEmpty())
            <x-card :title="$levelName" class="mb-5">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">ตัวชี้วัด</th>
                                <th class="px-3 py-2">ยุทธศาสตร์</th>
                                <th class="px-3 py-2">ผลแต่ละช่วง</th>
                                <th class="px-3 py-2 text-center">สรุป</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($items as $ind)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $ind->name }}</td>
                                    <td class="px-3 py-2 text-slate-500">{{ $ind->subStrategy?->strategy?->name }}</td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($ind->targets as $t)
                                                @php $rs = $t->result?->pass_status ?? 'pending'; @endphp
                                                <span class="rounded px-1.5 py-0.5 text-[11px] {{ $rs === 'pass' ? 'bg-emerald-100 text-emerald-700' : ($rs === 'fail' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-500') }}">
                                                    {{ $t->period_label }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-center"><x-status-badge :status="$statuses[$ind->id] ?? 'pending'" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif
    @endforeach

    @if ($indicators->isEmpty())
        <x-card><p class="py-10 text-center text-slate-400">ไม่มีข้อมูลในเงื่อนไขที่เลือก</p></x-card>
    @endif
</x-layouts.app>
