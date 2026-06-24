@php use App\Models\KpiIndicator; $user = auth()->user(); @endphp

<x-layouts.app title="กำหนดค่าเป้าหมาย" header="กำหนดค่าเป้าหมาย">

    {{-- ===== Filter Panel ===== --}}
    @php $hasFilters = ($filters['level'] ?? null) || ($filters['year'] ?? null) || ($filters['search'] ?? null); @endphp
    <div class="mb-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
            <x-icon name="search" class="h-4 w-4 text-slate-400" />
            <span class="ml-2 text-sm font-semibold text-slate-600">กรองและค้นหา</span>
            @if ($hasFilters)
                <span class="ml-2 inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-bold text-indigo-600">
                    {{ (($filters['level'] ?? null) ? 1 : 0) + (($filters['year'] ?? null) ? 1 : 0) + (($filters['search'] ?? null) ? 1 : 0) }}
                </span>
            @endif
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 px-5 py-4">

            {{-- ระดับ --}}
            @php $active = !empty($filters['level']); @endphp
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">ระดับ</label>
                <select name="level" onchange="this.form.submit()"
                    class="rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                    <option value="">ทุกระดับ</option>
                    @foreach (KpiIndicator::LEVELS as $k => $v)
                        <option value="{{ $k }}" @selected(($filters['level'] ?? '') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            {{-- ปี --}}
            @php $active = !empty($filters['year']); @endphp
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">ปี</label>
                <select name="year" onchange="this.form.submit()"
                    class="rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                    <option value="">ทุกปี</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected(($filters['year'] ?? '') == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            {{-- ค้นหา --}}
            @php $active = !empty($filters['search']); @endphp
            <div class="flex flex-col gap-1 flex-1 min-w-48">
                <label class="text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">ค้นหา</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <x-icon name="search" class="h-4 w-4 {{ $active ? 'text-indigo-400' : 'text-slate-400' }}" />
                    </div>
                    <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ชื่อหรือรหัสตัวชี้วัด"
                        class="w-full rounded-xl border py-2.5 pl-10 pr-3 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 placeholder:text-indigo-300 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 pb-0.5">
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                    <x-icon name="search" class="h-4 w-4" /> ค้นหา
                </button>
                @if ($hasFilters)
                    <a href="{{ route('targets.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800">
                        <x-icon name="x_circle" class="h-4 w-4 text-slate-400" /> ล้างตัวกรอง
                    </a>
                @endif
            </div>

        </form>
    </div>

    {{-- ===== Table ===== --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ตัวชี้วัด</th>
                        <th class="px-5 py-3">ระดับ</th>
                        <th class="px-5 py-3">รูปแบบ</th>
                        <th class="px-5 py-3 text-center">ช่วงที่ตั้งเป้าแล้ว</th>
                        <th class="px-5 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($indicators as $ind)
                        @php
                            $total = $ind->targets->count();
                            $defined = $ind->definedTargetsCount();
                            $noTarget = $defined === 0;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="font-medium text-slate-800">{{ $ind->name }}</div>
                                    @if ($noTarget)
                                        <span title="ตัวชี้วัดนี้ยังไม่ได้กำหนดค่าเป้าหมาย"
                                            class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> ยังไม่ได้กำหนดค่าเป้าหมาย
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-400">{{ $ind->main?->category?->name }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $ind->level_label }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $ind->period_type === 'quarterly' ? 'รายไตรมาส' : 'รายปี' }}</td>
                            <td class="px-5 py-3 text-center {{ $defined < $total ? 'font-medium text-amber-600' : 'text-slate-600' }}">{{ $defined }}/{{ $total }} ช่วง</td>
                            <td class="px-5 py-3 text-right">
                                @if ($user->canManageIndicatorData('kpi.target', 'edit', $ind->level, $ind->year))
                                    <x-btn :href="route('targets.edit', $ind)" variant="ghost"><x-icon name="target" class="w-4 h-4" /> กำหนดเป้าหมาย</x-btn>
                                @else
                                    <span class="text-xs text-slate-400">ดูอย่างเดียว</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">ยังไม่มีตัวชี้วัด</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $indicators->links() }}</div>
</x-layouts.app>
