@php
    $cat = $category ?? null;
    // ตัวเลือกยุทธศาสตร์สำหรับกรอง — ดึงจากรายการกลยุทธ์ที่ผู้ใช้มีสิทธิ์ (ตามระดับ+ปีที่รับผิดชอบ)
    $catStrategies = $subStrategyOptions
        ->map(fn ($o) => $o->strategy)
        ->filter()
        ->unique('id')
        ->sortByDesc('year')
        ->values();
@endphp

{{-- กรองกลยุทธ์ตามระดับ + ยุทธศาสตร์ (สิทธิ์การจัดการสืบทอดจากระดับ→ยุทธศาสตร์→กลยุทธ์) --}}
<div class="mb-4 flex flex-wrap items-end gap-4">
    <div>
        <label for="cat-level-filter" class="mb-1 block text-sm font-medium text-slate-700">ระดับตัวชี้วัด</label>
        <select id="cat-level-filter"
            class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-48">
            <option value="">— ทุกระดับ —</option>
            @foreach ($levelOptions as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="cat-strategy-filter" class="mb-1 block text-sm font-medium text-slate-700">ยุทธศาสตร์</label>
        <select id="cat-strategy-filter"
            class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-72">
            <option value="">— ทุกยุทธศาสตร์ —</option>
            @foreach ($catStrategies as $st)
                <option value="{{ $st->id }}" data-level="{{ $st->level }}">
                    [{{ $st->year }} · {{ $st->level_label }}] {{ $st->name }}
                </option>
            @endforeach
        </select>
    </div>
    <p class="text-xs text-slate-400">เลือกระดับ/ยุทธศาสตร์ เพื่อกรองรายการกลยุทธ์ด้านล่าง</p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.select name="sub_strategy_id" label="กลยุทธ์ (ไม่บังคับ)" help="ปล่อยว่างได้หากยังไม่ผูกกับกลยุทธ์">
        <option value="">— ไม่ผูกกลยุทธ์ —</option>
        @foreach ($subStrategyOptions as $opt)
            <option value="{{ $opt->id }}"
                data-level="{{ $opt->strategy?->level }}"
                data-strategy="{{ $opt->strategy?->id }}"
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
            const levelFilter    = document.getElementById('cat-level-filter');
            const strategyFilter = document.getElementById('cat-strategy-filter');
            const sel            = document.getElementById('sub_strategy_id');
            if (!levelFilter || !strategyFilter || !sel) return;

            // กรองรายการ "ยุทธศาสตร์" ตามระดับที่เลือก
            function applyStrategies() {
                const level = levelFilter.value;
                Array.from(strategyFilter.options).forEach(opt => {
                    if (!opt.value) return;
                    const match = !level || opt.dataset.level === level;
                    opt.hidden = opt.disabled = !match;
                    if (!match && opt.selected) strategyFilter.value = '';
                });
            }

            // กรองรายการ "กลยุทธ์" ตามระดับ + ยุทธศาสตร์ที่เลือก
            function applySubStrategies() {
                const level    = levelFilter.value;
                const strategy = strategyFilter.value;
                let hideSelected = false;
                Array.from(sel.options).forEach(opt => {
                    if (!opt.value) return; // placeholder "— ไม่ผูกกลยุทธ์ —"
                    const matchLevel    = !level    || opt.dataset.level    === level;
                    const matchStrategy = !strategy || opt.dataset.strategy === strategy;
                    const match = matchLevel && matchStrategy;
                    opt.hidden = opt.disabled = !match;
                    if (!match && opt.selected) hideSelected = true;
                });
                if (hideSelected) sel.value = '';
            }

            // โหมดแก้ไข: ตั้งตัวกรองให้ตรงกับกลยุทธ์ที่ผูกอยู่
            const current = sel.options[sel.selectedIndex];
            if (current && current.value) {
                if (current.dataset.level)    levelFilter.value    = current.dataset.level;
                if (current.dataset.strategy) strategyFilter.value = current.dataset.strategy;
            }

            levelFilter.addEventListener('change', () => { applyStrategies(); applySubStrategies(); });
            strategyFilter.addEventListener('change', applySubStrategies);

            applyStrategies();
            applySubStrategies();
        })();
    </script>
@endpush
