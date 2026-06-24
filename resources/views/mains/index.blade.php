@php $user = auth()->user(); @endphp

<x-layouts.app title="KPI หลัก" header="KPI หลัก">

    {{-- ===== Filter Panel ===== --}}
    @php $hasFilters = $year || $level || $strategyId || $subStrategyId || $categoryId || $name; @endphp
    <div class="mb-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
            <div class="flex items-center gap-2">
                <x-icon name="search" class="h-4 w-4 text-slate-400" />
                <span class="text-sm font-semibold text-slate-600">กรองและค้นหา</span>
                @if ($hasFilters)
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-bold text-indigo-600">
                        {{ ($year ? 1 : 0) + ($level ? 1 : 0) + ($strategyId ? 1 : 0) + ($subStrategyId ? 1 : 0) + ($categoryId ? 1 : 0) + ($name ? 1 : 0) }}
                    </span>
                @endif
            </div>
            @if ($user->canManageIndicatorData('kpi.main', 'create'))
                <x-btn :href="route('mains.create')"><x-icon name="main" class="w-4 h-4" /> เพิ่ม KPI หลัก</x-btn>
            @endif
        </div>

        <form method="GET" class="divide-y divide-slate-100">

            {{-- Row 1: Hierarchy --}}
            <div class="px-5 py-4">
                <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-slate-400">ลำดับชั้นยุทธศาสตร์</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                    {{-- ยุทธศาสตร์ --}}
                    @php $active = !empty($strategyId); @endphp
                    <div class="flex flex-col gap-1">
                        <label class="flex items-center gap-1.5 text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">
                            <x-icon name="strategy" class="h-3.5 w-3.5" /> ยุทธศาสตร์
                        </label>
                        <select name="strategy_id" onchange="this.form.submit()"
                            class="w-full rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                            <option value="">ทั้งหมด</option>
                            @foreach ($strategyOptions as $s)
                                <option value="{{ $s->id }}" @selected($s->id == $strategyId)>
                                    [{{ $s->year }}] {{ $s->name }}
                                    @if($s->level) · {{ \App\Models\KpiStrategy::LEVELS[$s->level] ?? $s->level }} @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- กลยุทธ์ --}}
                    @php $active = !empty($subStrategyId); @endphp
                    <div class="flex flex-col gap-1">
                        <label class="flex items-center gap-1.5 text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">
                            <x-icon name="sub_strategy" class="h-3.5 w-3.5" /> กลยุทธ์
                        </label>
                        <select name="sub_strategy_id" onchange="this.form.submit()"
                            class="w-full rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                            <option value="">ทั้งหมด</option>
                            @foreach ($subStrategyOptions as $opt)
                                <option value="{{ $opt->id }}" @selected($subStrategyId == $opt->id)>{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- หมวด KPI --}}
                    @php $active = !empty($categoryId); @endphp
                    <div class="flex flex-col gap-1">
                        <label class="flex items-center gap-1.5 text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">
                            <x-icon name="category" class="h-3.5 w-3.5" /> หมวด KPI
                        </label>
                        <select name="category_id" onchange="this.form.submit()"
                            class="w-full rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                            <option value="">ทั้งหมด</option>
                            @foreach ($categoryOptions as $opt)
                                <option value="{{ $opt->id }}" @selected($categoryId == $opt->id)>{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>

            {{-- Row 2: Quick filters --}}
            <div class="flex flex-wrap items-end gap-3 px-5 py-4">

                {{-- ปี --}}
                @php $active = !empty($year); @endphp
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">ปี</label>
                    <select name="year" onchange="this.form.submit()"
                        class="rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                        <option value="">ทุกปี</option>
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ระดับ --}}
                @php $active = !empty($level); @endphp
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">ระดับ</label>
                    <select name="level" onchange="this.form.submit()"
                        class="rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                        <option value="">ทุกระดับ</option>
                        @foreach ($levelOptions as $code => $label)
                            <option value="{{ $code }}" @selected($code === $level)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ค้นหาชื่อ --}}
                @php $active = !empty($name); @endphp
                <div class="flex flex-col gap-1 flex-1 min-w-48">
                    <label class="text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">ค้นหา</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <x-icon name="search" class="h-4 w-4 {{ $active ? 'text-indigo-400' : 'text-slate-400' }}" />
                        </div>
                        <input type="text" name="name" value="{{ $name }}" placeholder="รหัสหรือชื่อ KPI หลัก"
                            class="w-full rounded-xl border py-2.5 pl-9 pr-3 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 placeholder:text-indigo-300 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 pb-0.5">
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                        <x-icon name="search" class="h-4 w-4" /> ค้นหา
                    </button>
                    @if ($hasFilters)
                        <a href="{{ route('mains.index') }}"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800">
                            <x-icon name="x_circle" class="h-4 w-4 text-slate-400" /> ล้างตัวกรอง
                        </a>
                    @endif
                </div>

            </div>

        </form>
    </div>

    {{-- ===== Table ===== --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">หมวด KPI</th>
                        <th class="px-5 py-3">ชื่อ KPI หลัก</th>
                        <th class="px-5 py-3 text-center">ตัวชี้วัด</th>
                        <th class="px-5 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($mains as $m)
                        @php
                            $stLevel = $m->category?->subStrategy?->strategy?->level ?? '';
                            $stYear = $m->category?->subStrategy?->strategy?->year;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-600">{{ $m->category?->name }}</td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ $m->name }}</div>
                                @if ($m->code)<div class="text-xs text-slate-400">{{ $m->code }}</div>@endif
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $m->indicators_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($user->canManageIndicatorData('kpi.main', 'edit', $stLevel, $stYear))
                                        <x-btn :href="route('mains.edit', $m)" variant="ghost">แก้ไข</x-btn>
                                    @endif
                                    @if ($user->canManageIndicatorData('kpi.main', 'delete', $stLevel, $stYear))
                                        <form method="POST" action="{{ route('mains.destroy', $m) }}" onsubmit="return confirm('ยืนยันลบ KPI หลัก นี้?')">
                                            @csrf @method('DELETE')
                                            <x-btn type="submit" variant="ghost" class="!text-red-600 hover:!bg-red-50">ลบ</x-btn>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">ยังไม่มีข้อมูล KPI หลัก</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $mains->withQueryString()->links() }}</div>
</x-layouts.app>
