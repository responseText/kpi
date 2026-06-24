@php
    $m = $main ?? null;
    $mainStrategies = $categoryOptions
        ->map(fn ($o) => $o->subStrategy?->strategy)
        ->filter()
        ->unique('id')
        ->sortByDesc('year')
        ->values();
    $mainSubStrategies = $categoryOptions
        ->map(fn ($o) => $o->subStrategy)
        ->filter()
        ->unique('id')
        ->values();
@endphp

{{-- ===== Filter helper (ไม่ถูกบันทึก — ใช้เพื่อกรองรายการหมวด KPI ด้านล่างเท่านั้น) ===== --}}
<div class="mb-5 overflow-hidden rounded-xl ring-1 ring-slate-200">

    <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-4 py-2.5">
        <x-icon name="search" class="h-3.5 w-3.5 text-slate-400" />
        <span class="text-xs font-semibold text-slate-600">กรองเพื่อค้นหาหมวด KPI</span>
        <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-600">ไม่ถูกบันทึก</span>
    </div>

    <div class="grid grid-cols-1 gap-3 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- ปี --}}
        <div class="flex flex-col gap-1">
            <label for="main-year-filter" class="text-xs font-medium text-slate-500">ปี</label>
            <select id="main-year-filter"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-3 pr-8 text-sm transition hover:border-slate-300 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:ring-offset-0">
                <option value="">— ทุกปี —</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>

        {{-- ระดับตัวชี้วัด --}}
        <div class="flex flex-col gap-1">
            <label for="main-level-filter" class="text-xs font-medium text-slate-500">ระดับตัวชี้วัด</label>
            <select id="main-level-filter"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-3 pr-8 text-sm transition hover:border-slate-300 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:ring-offset-0">
                <option value="">— ทุกระดับ —</option>
                @foreach ($levelOptions as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- ยุทธศาสตร์ --}}
        <div class="flex flex-col gap-1">
            <label for="main-strategy-filter" class="text-xs font-medium text-slate-500">ยุทธศาสตร์</label>
            <select id="main-strategy-filter"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-3 pr-8 text-sm transition hover:border-slate-300 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:ring-offset-0">
                <option value="">— ทุกยุทธศาสตร์ —</option>
                @foreach ($mainStrategies as $st)
                    <option value="{{ $st->id }}"
                        data-level="{{ $st->level }}"
                        data-year="{{ $st->year }}">
                        [{{ $st->year }} · {{ $st->level_label }}] {{ $st->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- กลยุทธ์ --}}
        <div class="flex flex-col gap-1">
            <label for="main-substrategy-filter" class="text-xs font-medium text-slate-500">กลยุทธ์</label>
            <select id="main-substrategy-filter"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-3 pr-8 text-sm transition hover:border-slate-300 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:ring-offset-0">
                <option value="">— ทุกกลยุทธ์ —</option>
                @foreach ($mainSubStrategies as $sub)
                    <option value="{{ $sub->id }}"
                        data-level="{{ $sub->strategy?->level }}"
                        data-strategy="{{ $sub->strategy_id }}"
                        data-year="{{ $sub->strategy?->year }}">
                        {{ $sub->name }}
                    </option>
                @endforeach
            </select>
        </div>

    </div>
</div>

{{-- ===== ฟิลด์จริง ===== --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

    {{-- หมวด KPI (submitted) --}}
    <x-form.select name="category_id" label="หมวด KPI" :required="true">
        <option value="">— เลือกหมวด KPI —</option>
        @foreach ($categoryOptions as $opt)
            <option value="{{ $opt->id }}"
                data-level="{{ $opt->subStrategy?->strategy?->level }}"
                data-strategy="{{ $opt->subStrategy?->strategy?->id }}"
                data-substrategy="{{ $opt->sub_strategy_id }}"
                data-year="{{ $opt->subStrategy?->strategy?->year }}"
                @selected(old('category_id', $m->category_id ?? '') == $opt->id)>
                @if ($opt->subStrategy?->strategy)[{{ $opt->subStrategy->strategy->year }} · {{ $opt->subStrategy->strategy->level_label }}] {{ $opt->subStrategy->name }} › @endif{{ $opt->code ? '['.$opt->code.'] ' : '' }}{{ $opt->name }}
            </option>
        @endforeach
    </x-form.select>

    <x-form.input name="code" label="รหัส KPI หลัก" :value="$m->code ?? ''" placeholder="เช่น KPI 1" />
</div>

<div class="mt-4">
    <x-form.input name="name" label="ชื่อ KPI หลัก" :value="$m->name ?? ''" :required="true" />
</div>

<div class="mt-4">
    <x-form.textarea name="description" label="รายละเอียด" :value="$m->description ?? ''" />
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.input name="orderby" label="ลำดับการแสดง" type="number" :value="$m->orderby ?? 0" />
    <x-form.select name="status" label="สถานะ" :required="true">
        <option value="enable" @selected(old('status', $m->status ?? 'enable') === 'enable')>เปิดใช้งาน</option>
        <option value="disable" @selected(old('status', $m->status ?? '') === 'disable')>ปิดใช้งาน</option>
    </x-form.select>
</div>

<div class="mt-6 flex items-center gap-2">
    <x-btn type="submit" variant="primary">บันทึก</x-btn>
    <x-btn :href="route('mains.index')" variant="secondary">ยกเลิก</x-btn>
</div>

@push('scripts')
<script>
(function () {
    const yearFilter     = document.getElementById('main-year-filter');
    const levelFilter    = document.getElementById('main-level-filter');
    const strategyFilter = document.getElementById('main-strategy-filter');
    const subFilter      = document.getElementById('main-substrategy-filter');
    const sel            = document.getElementById('category_id');
    if (!yearFilter || !levelFilter || !strategyFilter || !subFilter || !sel) return;

    // กรองระดับ: แสดงเฉพาะระดับที่มียุทธศาสตร์ในปีที่เลือก
    function applyLevels() {
        const year = yearFilter.value;
        const availableLevels = new Set();
        Array.from(strategyFilter.options).forEach(opt => {
            if (!opt.value) return;
            if (!year || opt.dataset.year === year) availableLevels.add(opt.dataset.level);
        });
        Array.from(levelFilter.options).forEach(opt => {
            if (!opt.value) return;
            const match = !year || availableLevels.has(opt.value);
            opt.hidden = opt.disabled = !match;
            if (!match && opt.selected) levelFilter.value = '';
        });
    }

    // กรองยุทธศาสตร์: ตาม ปี + ระดับ
    function applyStrategies() {
        const year  = yearFilter.value;
        const level = levelFilter.value;
        Array.from(strategyFilter.options).forEach(opt => {
            if (!opt.value) return;
            const match = (!year  || opt.dataset.year  === year) &&
                          (!level || opt.dataset.level === level);
            opt.hidden = opt.disabled = !match;
            if (!match && opt.selected) strategyFilter.value = '';
        });
    }

    // กรองกลยุทธ์: ตาม ปี + ระดับ + ยุทธศาสตร์
    function applySubStrategies() {
        const year     = yearFilter.value;
        const level    = levelFilter.value;
        const strategy = strategyFilter.value;
        Array.from(subFilter.options).forEach(opt => {
            if (!opt.value) return;
            const match = (!year     || opt.dataset.year     === year) &&
                          (!level    || opt.dataset.level    === level) &&
                          (!strategy || opt.dataset.strategy === strategy);
            opt.hidden = opt.disabled = !match;
            if (!match && opt.selected) subFilter.value = '';
        });
    }

    // กรองหมวด KPI: ตาม ปี + ระดับ + ยุทธศาสตร์ + กลยุทธ์
    function applyCategories() {
        const year     = yearFilter.value;
        const level    = levelFilter.value;
        const strategy = strategyFilter.value;
        const sub      = subFilter.value;
        let hideSelected = false;
        Array.from(sel.options).forEach(opt => {
            if (!opt.value) return;
            const match = (!year     || opt.dataset.year        === year) &&
                          (!level    || opt.dataset.level       === level) &&
                          (!strategy || opt.dataset.strategy    === strategy) &&
                          (!sub      || opt.dataset.substrategy === sub);
            opt.hidden = opt.disabled = !match;
            if (!match && opt.selected) hideSelected = true;
        });
        if (hideSelected) sel.value = '';
    }

    // โหมดแก้ไข: ตั้งตัวกรองให้ตรงกับหมวด KPI ที่ผูกอยู่
    const current = sel.options[sel.selectedIndex];
    if (current && current.value) {
        if (current.dataset.year)        yearFilter.value     = current.dataset.year;
        if (current.dataset.level)       levelFilter.value    = current.dataset.level;
        if (current.dataset.strategy)    strategyFilter.value = current.dataset.strategy;
        if (current.dataset.substrategy) subFilter.value      = current.dataset.substrategy;
    }

    yearFilter.addEventListener('change',     () => { applyLevels(); applyStrategies(); applySubStrategies(); applyCategories(); });
    levelFilter.addEventListener('change',    () => { applyStrategies(); applySubStrategies(); applyCategories(); });
    strategyFilter.addEventListener('change', () => { applySubStrategies(); applyCategories(); });
    subFilter.addEventListener('change',      applyCategories);

    applyLevels();
    applyStrategies();
    applySubStrategies();
    applyCategories();
})();
</script>
@endpush
