@php
    use App\Models\KpiIndicator;
    $user = auth()->user();
    $hasFilters = array_filter([
        $filters['year'] ?? null,
        $filters['level'] ?? null,
        $filters['strategy_id'] ?? null,
        $filters['sub_strategy_id'] ?? null,
        $filters['category_id'] ?? null,
        $filters['kpi_main_id'] ?? null,
        $filters['search'] ?? null,
    ]);
@endphp

<x-layouts.app title="ตัวชี้วัด" header="ตัวชี้วัด">

    {{-- ===== Filter Panel ===== --}}
    <div class="mb-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">

        {{-- Panel header --}}
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
            <div class="flex items-center gap-2">
                <x-icon name="search" class="h-4 w-4 text-slate-400" />
                <span class="text-sm font-semibold text-slate-600">กรองและค้นหา</span>
                @if ($hasFilters)
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-bold text-indigo-600">
                        {{ count($hasFilters) }}
                    </span>
                @endif
            </div>
            @if ($user->canManageIndicatorData('kpi.indicator', 'create'))
                <x-btn :href="route('indicators.create')">
                    <x-icon name="indicator" class="w-4 h-4" /> เพิ่มตัวชี้วัด
                </x-btn>
            @endif
        </div>

        <form method="GET" id="indicator-filter-form" class="divide-y divide-slate-100">

            {{-- Row 1 — Primary filters: ปี + ระดับ (cascade ลงสู่ hierarchy ด้านล่าง) --}}
            <div class="flex flex-wrap items-end gap-3 px-5 py-4">
                <p class="w-full text-[10px] font-semibold uppercase tracking-widest text-slate-400">ตัวกรองหลัก</p>

                {{-- ปี --}}
                @php $active = !empty($filters['year']); @endphp
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">ปี</label>
                    <select name="year" onchange="cascadeFilter('year')"
                        class="rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                        <option value="">ทุกปี</option>
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected(($filters['year'] ?? '') == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- ระดับ --}}
                @php $active = !empty($filters['level']); @endphp
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">ระดับตัวชี้วัด</label>
                    <select name="level" onchange="cascadeFilter('level')"
                        class="rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                        <option value="">ทุกระดับ</option>
                        @foreach (KpiIndicator::LEVELS as $k => $v)
                            <option value="{{ $k }}" @selected(($filters['level'] ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($filters['year'] || $filters['level'])
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-transparent select-none">x</label>
                        <p class="py-2.5 text-xs text-indigo-500">
                            <x-icon name="strategy" class="inline h-3.5 w-3.5" />
                            กรองลำดับชั้นด้านล่างตามปี/ระดับที่เลือก
                        </p>
                    </div>
                @endif
            </div>

            {{-- Row 2 — Hierarchy: ยุทธศาสตร์ → กลยุทธ์ → หมวด KPI → KPI หลัก --}}
            <div class="px-5 py-4">
                <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-slate-400">ลำดับชั้นยุทธศาสตร์</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">

                    {{-- ยุทธศาสตร์ --}}
                    @php $active = !empty($filters['strategy_id']); @endphp
                    <div class="flex flex-col gap-1">
                        <label class="flex items-center gap-1.5 text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">
                            <x-icon name="strategy" class="h-3.5 w-3.5" /> ยุทธศาสตร์
                        </label>
                        <select name="strategy_id" onchange="cascadeFilter('strategy_id')"
                            class="w-full rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                            <option value="">ทั้งหมด</option>
                            @foreach ($strategies as $s)
                                <option value="{{ $s->id }}" @selected(($filters['strategy_id'] ?? '') == $s->id)>
                                    [{{ $s->year }}] {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- กลยุทธ์ --}}
                    @php $active = !empty($filters['sub_strategy_id']); @endphp
                    <div class="flex flex-col gap-1">
                        <label class="flex items-center gap-1.5 text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">
                            <x-icon name="sub_strategy" class="h-3.5 w-3.5" /> กลยุทธ์
                        </label>
                        <select name="sub_strategy_id" onchange="cascadeFilter('sub_strategy_id')"
                            class="w-full rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                            <option value="">ทั้งหมด</option>
                            @foreach ($subStrategies as $ss)
                                <option value="{{ $ss->id }}" @selected(($filters['sub_strategy_id'] ?? '') == $ss->id)>
                                    {{ $ss->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- หมวด KPI --}}
                    @php $active = !empty($filters['category_id']); @endphp
                    <div class="flex flex-col gap-1">
                        <label class="flex items-center gap-1.5 text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">
                            <x-icon name="category" class="h-3.5 w-3.5" /> หมวด KPI
                        </label>
                        <select name="category_id" onchange="cascadeFilter('category_id')"
                            class="w-full rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                            <option value="">ทั้งหมด</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(($filters['category_id'] ?? '') == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- KPI หลัก --}}
                    @php $active = !empty($filters['kpi_main_id']); @endphp
                    <div class="flex flex-col gap-1">
                        <label class="flex items-center gap-1.5 text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">
                            <x-icon name="main" class="h-3.5 w-3.5" /> KPI หลัก
                        </label>
                        <select name="kpi_main_id" onchange="cascadeFilter('kpi_main_id')"
                            class="w-full rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                            <option value="">ทั้งหมด</option>
                            @foreach ($mains as $m)
                                <option value="{{ $m->id }}" @selected(($filters['kpi_main_id'] ?? '') == $m->id)>
                                    {{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>

            {{-- Row 3 — Search + actions --}}
            <div class="flex flex-wrap items-end gap-3 px-5 py-4">

                {{-- Search --}}
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
                        <a href="{{ route('indicators.index') }}"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-1">
                            <x-icon name="x_circle" class="h-4 w-4 text-slate-400" /> ล้างตัวกรอง
                        </a>
                    @endif
                </div>

            </div>

        </form>
    </div>

    <script>
    function cascadeFilter(changed) {
        const form = document.getElementById('indicator-filter-form');
        // ลำดับ cascade: year และ level ต่างกัน (ไม่ล้างกันเอง) แต่ทั้งคู่ล้าง hierarchy ด้านล่าง
        const hierarchyFields = ['strategy_id', 'sub_strategy_id', 'category_id', 'kpi_main_id'];

        if (changed === 'year' || changed === 'level') {
            // ปีหรือระดับเปลี่ยน → ล้าง hierarchy ทั้งหมด (strategy ขึ้นไปอาจไม่มีในปี/ระดับใหม่)
            hierarchyFields.forEach(name => {
                const el = form.querySelector(`[name="${name}"]`);
                if (el) el.value = '';
            });
        } else {
            // hierarchy field เปลี่ยน → ล้างเฉพาะ field ที่อยู่ต่ำกว่าในลำดับชั้น
            const idx = hierarchyFields.indexOf(changed);
            hierarchyFields.slice(idx + 1).forEach(name => {
                const el = form.querySelector(`[name="${name}"]`);
                if (el) el.value = '';
            });
        }

        form.submit();
    }
    </script>

    {{-- ===== Table ===== --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ตัวชี้วัด</th>
                        <th class="px-5 py-3">ระดับ</th>
                        <th class="px-5 py-3">ปี/รูปแบบ</th>
                        <th class="px-5 py-3">ประเภทการวัด</th>
                        <th class="px-5 py-3">ผู้รับผิดชอบ</th>
                        <th class="px-5 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($indicators as $ind)
                        @php
                            $mt      = $ind->measurement_type;
                            $mtMeta  = $mt ? (\App\Support\MeasurementType::META[$mt] ?? null) : null;
                            $mtGroup = $mtMeta['group'] ?? null;
                            $mtColor = match ($mtGroup) {
                                'quantity'   => 'bg-sky-100 text-sky-700',
                                'quality'    => 'bg-violet-100 text-violet-700',
                                'time'       => 'bg-orange-100 text-orange-700',
                                'cost'       => 'bg-rose-100 text-rose-700',
                                'efficiency' => 'bg-emerald-100 text-emerald-700',
                                default      => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50 {{ !$mt ? 'bg-amber-50/40' : '' }}">
                            <td class="px-5 py-3">
                                <a href="{{ route('indicators.show', $ind) }}" class="font-medium text-indigo-700 hover:underline">{{ $ind->name }}</a>
                                <div class="mt-0.5 text-xs text-slate-400">{{ $ind->main?->category?->name }} <p>  &nbsp;&nbsp; &nbsp;›  &nbsp;{{ $ind->main?->name }}</p></div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $ind->level_label }}</td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $ind->year_type === 'fiscal' ? 'งบ' : 'พ.ศ.' }} {{ $ind->year }}
                                <span class="text-slate-400">· {{ $ind->period_type === 'quarterly' ? 'ไตรมาส' : 'รายปี' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if ($mt)
                                    <span class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-medium {{ $mtColor }} text-center">
                                        {{ $ind->measurement_type_label }}
                                    </span>
                                    <div class="mt-0.5 text-[10px] text-slate-400">{{ $ind->measurement_type_group_label }}</div>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-lg border border-amber-300 bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                        </svg>
                                        ยังไม่กำหนด
                                    </span>
                                    @if ($user->canManageIndicatorData('kpi.indicator', 'edit', $ind->level, $ind->year))
                                        <a href="{{ route('indicators.edit', $ind) }}" class="mt-0.5 block text-[10px] text-amber-600 hover:underline">กำหนดเลย →</a>
                                    @endif
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                @foreach ($ind->owners->take(2) as $o)
                                    <span class="mr-1 inline-block rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $o->display_name }}</span>
                                @endforeach
                                @if ($ind->owners->count() > 2)<span class="text-xs text-slate-400">+{{ $ind->owners->count() - 2 }}</span>@endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <x-btn :href="route('indicators.show', $ind)" variant="ghost">ดู</x-btn>
                                    @if ($user->canManageIndicatorData('kpi.indicator', 'edit', $ind->level, $ind->year))
                                        <x-btn :href="route('indicators.edit', $ind)" variant="ghost">แก้ไข</x-btn>
                                    @endif
                                    @if ($user->canManageIndicatorData('kpi.indicator', 'delete', $ind->level, $ind->year))
                                        <form method="POST" action="{{ route('indicators.destroy', $ind) }}" onsubmit="return confirm('ยืนยันลบตัวชี้วัดนี้?')">
                                            @csrf @method('DELETE')
                                            <x-btn type="submit" variant="ghost" class="!text-red-600 hover:!bg-red-50">ลบ</x-btn>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">ยังไม่มีข้อมูลตัวชี้วัด</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $indicators->links() }}</div>
</x-layouts.app>
