@php
    $cat = $category ?? null;
    $catStrategies = $subStrategyOptions
        ->map(fn ($o) => $o->strategy)
        ->filter()
        ->unique('id')
        ->sortByDesc('year')
        ->values();
@endphp

{{-- ===== Filter helper (ไม่ถูกบันทึก — ใช้เพื่อกรองรายการกลยุทธ์ด้านล่างเท่านั้น) ===== --}}
<div class="mb-5 overflow-hidden rounded-xl ring-1 ring-slate-200">

    <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-4 py-2.5">
        <x-icon name="search" class="h-3.5 w-3.5 text-slate-400" />
        <span class="text-xs font-semibold text-slate-600">กรองเพื่อค้นหากลยุทธ์</span>
        <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-600">ไม่ถูกบันทึก</span>
    </div>

    <div class="grid grid-cols-1 gap-3 bg-white p-4 sm:grid-cols-3">

        {{-- ปี --}}
        <div class="flex flex-col gap-1">
            <label for="cat-year-filter" class="text-xs font-medium text-slate-500">ปี</label>
            <select id="cat-year-filter"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-3 pr-8 text-sm transition hover:border-slate-300 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:ring-offset-0">
                <option value="">— ทุกปี —</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>

        {{-- ระดับตัวชี้วัด --}}
        <div class="flex flex-col gap-1">
            <label for="cat-level-filter" class="text-xs font-medium text-slate-500">ระดับตัวชี้วัด</label>
            <select id="cat-level-filter"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-3 pr-8 text-sm transition hover:border-slate-300 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:ring-offset-0">
                <option value="">— ทุกระดับ —</option>
                @foreach ($levelOptions as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- ยุทธศาสตร์ --}}
        <div class="flex flex-col gap-1">
            <label for="cat-strategy-filter" class="text-xs font-medium text-slate-500">ยุทธศาสตร์</label>
            <select id="cat-strategy-filter"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-3 pr-8 text-sm transition hover:border-slate-300 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:ring-offset-0">
                <option value="">— ทุกยุทธศาสตร์ —</option>
                @foreach ($catStrategies as $st)
                    <option value="{{ $st->id }}"
                        data-level="{{ $st->level }}"
                        data-year="{{ $st->year }}">
                        [{{ $st->year }} · {{ $st->level_label }}] {{ $st->name }}
                    </option>
                @endforeach
            </select>
        </div>

    </div>
</div>

{{-- ===== ฟิลด์จริง ===== --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

    {{-- กลยุทธ์ (submitted) --}}
    <x-form.select name="sub_strategy_id" label="กลยุทธ์ (ไม่บังคับ)" help="ปล่อยว่างได้หากยังไม่ผูกกับกลยุทธ์">
        <option value="">— ไม่ผูกกลยุทธ์ —</option>
        @foreach ($subStrategyOptions as $opt)
            <option value="{{ $opt->id }}"
                data-level="{{ $opt->strategy?->level }}"
                data-strategy="{{ $opt->strategy?->id }}"
                data-year="{{ $opt->strategy?->year }}"
                @selected(old('sub_strategy_id', $cat->sub_strategy_id ?? '') == $opt->id)>
                [{{ $opt->strategy?->year }} · {{ $opt->strategy?->level_label }}] {{ $opt->strategy?->name }} › {{ $opt->name }}
            </option>
        @endforeach
    </x-form.select>

    <x-form.input name="code" label="รหัสหมวด KPI" :value="$cat->code ?? ''" placeholder="เช่น หมวด 1" />
</div>

<div class="mt-4">
    <x-form.input name="name" label="ชื่อหมวด KPI" :value="$cat->name ?? ''" :required="true" />
</div>

<div class="mt-4">
    <x-form.textarea name="description" label="รายละเอียด" :value="$cat->description ?? ''" />
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.input name="orderby" label="ลำดับการแสดง" type="number" :value="$cat->orderby ?? 0" />
    <x-form.select name="status" label="สถานะ" :required="true">
        <option value="enable" @selected(old('status', $cat->status ?? 'enable') === 'enable')>เปิดใช้งาน</option>
        <option value="disable" @selected(old('status', $cat->status ?? '') === 'disable')>ปิดใช้งาน</option>
    </x-form.select>
</div>

<div class="mt-6 flex items-center gap-2">
    <x-btn type="submit" variant="primary">บันทึก</x-btn>
    <x-btn :href="route('categories.index')" variant="secondary">ยกเลิก</x-btn>
</div>

@push('scripts')
<script>
(function () {
    const yearFilter     = document.getElementById('cat-year-filter');
    const levelFilter    = document.getElementById('cat-level-filter');
    const strategyFilter = document.getElementById('cat-strategy-filter');
    const sel            = document.getElementById('sub_strategy_id');
    if (!yearFilter || !levelFilter || !strategyFilter || !sel) return;

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
            const match = (!year || opt.dataset.year === year) &&
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
        let hideSelected = false;
        Array.from(sel.options).forEach(opt => {
            if (!opt.value) return;
            const match = (!year     || opt.dataset.year     === year) &&
                          (!level    || opt.dataset.level    === level) &&
                          (!strategy || opt.dataset.strategy === strategy);
            opt.hidden = opt.disabled = !match;
            if (!match && opt.selected) hideSelected = true;
        });
        if (hideSelected) sel.value = '';
    }

    // โหมดแก้ไข: ตั้งตัวกรองให้ตรงกับกลยุทธ์ที่ผูกอยู่
    const current = sel.options[sel.selectedIndex];
    if (current && current.value) {
        if (current.dataset.year)     yearFilter.value     = current.dataset.year;
        if (current.dataset.level)    levelFilter.value    = current.dataset.level;
        if (current.dataset.strategy) strategyFilter.value = current.dataset.strategy;
    }

    yearFilter.addEventListener('change', () => { applyLevels(); applyStrategies(); applySubStrategies(); });
    levelFilter.addEventListener('change', () => { applyStrategies(); applySubStrategies(); });
    strategyFilter.addEventListener('change', applySubStrategies);

    applyLevels();
    applyStrategies();
    applySubStrategies();
})();
</script>
@endpush
