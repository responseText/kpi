@php
    $m = $main ?? null;
    // ตัวเลือกสำหรับกรอง — ดึงจากรายการหมวด KPI ที่ผู้ใช้มีสิทธิ์ (ตามระดับ+ปีที่รับผิดชอบ)
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

{{-- กรองหมวด KPI ตามระดับ → ยุทธศาสตร์ → กลยุทธ์ (สิทธิ์การจัดการสืบทอดตามสายนี้) --}}
<div class="mb-4 flex flex-wrap items-end gap-4">
    <div>
        <label for="main-level-filter" class="mb-1 block text-sm font-medium text-slate-700">ระดับตัวชี้วัด</label>
        <select id="main-level-filter"
            class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-44">
            <option value="">— ทุกระดับ —</option>
            @foreach ($levelOptions as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="main-strategy-filter" class="mb-1 block text-sm font-medium text-slate-700">ยุทธศาสตร์</label>
        <select id="main-strategy-filter"
            class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-64">
            <option value="">— ทุกยุทธศาสตร์ —</option>
            @foreach ($mainStrategies as $st)
                <option value="{{ $st->id }}" data-level="{{ $st->level }}">
                    [{{ $st->year }} · {{ $st->level_label }}] {{ $st->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="main-substrategy-filter" class="mb-1 block text-sm font-medium text-slate-700">กลยุทธ์</label>
        <select id="main-substrategy-filter"
            class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-64">
            <option value="">— ทุกกลยุทธ์ —</option>
            @foreach ($mainSubStrategies as $sub)
                <option value="{{ $sub->id }}" data-level="{{ $sub->strategy?->level }}" data-strategy="{{ $sub->strategy_id }}">
                    {{ $sub->name }}
                </option>
            @endforeach
        </select>
    </div>
    <p class="text-xs text-slate-400">เลือกระดับ/ยุทธศาสตร์/กลยุทธ์ เพื่อกรองรายการหมวด KPI ด้านล่าง</p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.select name="category_id" label="หมวด KPI" :required="true">
        <option value="">— เลือกหมวด KPI —</option>
        @foreach ($categoryOptions as $opt)
            <option value="{{ $opt->id }}"
                data-level="{{ $opt->subStrategy?->strategy?->level }}"
                data-strategy="{{ $opt->subStrategy?->strategy?->id }}"
                data-substrategy="{{ $opt->sub_strategy_id }}"
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
            const levelFilter    = document.getElementById('main-level-filter');
            const strategyFilter = document.getElementById('main-strategy-filter');
            const subFilter      = document.getElementById('main-substrategy-filter');
            const sel            = document.getElementById('category_id');
            if (!levelFilter || !strategyFilter || !subFilter || !sel) return;

            // กรอง "ยุทธศาสตร์" ตามระดับ
            function applyStrategies() {
                const level = levelFilter.value;
                Array.from(strategyFilter.options).forEach(opt => {
                    if (!opt.value) return;
                    const match = !level || opt.dataset.level === level;
                    opt.hidden = opt.disabled = !match;
                    if (!match && opt.selected) strategyFilter.value = '';
                });
            }

            // กรอง "กลยุทธ์" ตามระดับ + ยุทธศาสตร์
            function applySubStrategies() {
                const level    = levelFilter.value;
                const strategy = strategyFilter.value;
                Array.from(subFilter.options).forEach(opt => {
                    if (!opt.value) return;
                    const matchLevel    = !level    || opt.dataset.level    === level;
                    const matchStrategy = !strategy || opt.dataset.strategy === strategy;
                    const match = matchLevel && matchStrategy;
                    opt.hidden = opt.disabled = !match;
                    if (!match && opt.selected) subFilter.value = '';
                });
            }

            // กรอง "หมวด KPI" ตามระดับ + ยุทธศาสตร์ + กลยุทธ์
            function applyCategories() {
                const level    = levelFilter.value;
                const strategy = strategyFilter.value;
                const sub      = subFilter.value;
                let hideSelected = false;
                Array.from(sel.options).forEach(opt => {
                    if (!opt.value) return; // placeholder "— เลือกหมวด KPI —"
                    const matchLevel    = !level    || opt.dataset.level       === level;
                    const matchStrategy = !strategy || opt.dataset.strategy    === strategy;
                    const matchSub      = !sub      || opt.dataset.substrategy === sub;
                    const match = matchLevel && matchStrategy && matchSub;
                    opt.hidden = opt.disabled = !match;
                    if (!match && opt.selected) hideSelected = true;
                });
                if (hideSelected) sel.value = '';
            }

            // โหมดแก้ไข: ตั้งตัวกรองให้ตรงกับหมวด KPI ที่ผูกอยู่
            const current = sel.options[sel.selectedIndex];
            if (current && current.value) {
                if (current.dataset.level)       levelFilter.value    = current.dataset.level;
                if (current.dataset.strategy)    strategyFilter.value = current.dataset.strategy;
                if (current.dataset.substrategy) subFilter.value      = current.dataset.substrategy;
            }

            levelFilter.addEventListener('change', () => { applyStrategies(); applySubStrategies(); applyCategories(); });
            strategyFilter.addEventListener('change', () => { applySubStrategies(); applyCategories(); });
            subFilter.addEventListener('change', applyCategories);

            applyStrategies();
            applySubStrategies();
            applyCategories();
        })();
    </script>
@endpush
