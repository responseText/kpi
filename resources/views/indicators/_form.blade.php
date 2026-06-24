@php
    $ind = $indicator ?? null;
    $selectedOwners = old('owners', $ind ? $ind->owners->pluck('id')->all() : []);
    $primaryOwner = old('primary_owner', $ind?->owners->firstWhere('pivot.is_primary', true)?->id);
    $mainsByCategory = $mainOptions->groupBy(function ($m) {
        $strategy = $m->category?->subStrategy?->strategy;
        $prefix = $strategy ? '[' . $strategy->year . ' · ' . ($strategy->level_label ?? $strategy->level) . '] ' : '';
        return $prefix . ($m->category?->name ?? 'ไม่ระบุหมวด KPI');
    });
@endphp

{{-- ===== Filter helper (ไม่ถูกบันทึก — ใช้เพื่อกรองรายการ KPI หลัก ด้านล่างเท่านั้น) ===== --}}
<div class="mb-5 overflow-hidden rounded-xl ring-1 ring-slate-200">

    <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-4 py-2.5">
        <x-icon name="search" class="h-3.5 w-3.5 text-slate-400" />
        <span class="text-xs font-semibold text-slate-600">กรองเพื่อค้นหา KPI หลัก</span>
        <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-600">ไม่ถูกบันทึก</span>
    </div>

    <div class="grid grid-cols-1 gap-3 bg-white p-4 sm:grid-cols-2">

        {{-- ปี --}}
        <div class="flex flex-col gap-1">
            <label for="ind-year-filter" class="text-xs font-medium text-slate-500">ปี (ยุทธศาสตร์)</label>
            <select id="ind-year-filter"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-3 pr-8 text-sm transition hover:border-slate-300 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:ring-offset-0">
                <option value="">— ทุกปี —</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>

        {{-- คำอธิบาย --}}
        <div class="flex items-end pb-1">
            <p class="text-xs leading-relaxed text-slate-500">
                <x-icon name="strategy" class="inline h-3.5 w-3.5 text-slate-400" />
                รายการ <strong>KPI หลัก</strong> จะกรองตามปีที่เลือกด้านซ้าย
                ร่วมกับ <strong>ระดับตัวชี้วัด</strong> ที่เลือกด้านล่าง
            </p>
        </div>

    </div>
</div>

{{-- ===== KPI หลัก + ระดับตัวชี้วัด ===== --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.select name="level" label="ระดับตัวชี้วัด" :required="true">
        @foreach ($levels as $key => $name)
            <option value="{{ $key }}" @selected(old('level', $ind->level ?? 'hospital') === $key)>{{ $name }}</option>
        @endforeach
    </x-form.select>
    <x-form.select name="kpi_main_id" label="KPI หลัก (ภายใต้หมวด KPI)" :required="true">
        <option value="">— เลือก KPI หลัก —</option>
        @foreach ($mainsByCategory as $categoryName => $mains)
            <optgroup label="{{ $categoryName }}">
                @foreach ($mains as $opt)
                    <option value="{{ $opt->id }}"
                        data-year="{{ $opt->category?->subStrategy?->strategy?->year }}"
                        data-level="{{ $opt->category?->subStrategy?->strategy?->level }}"
                        @selected(old('kpi_main_id', $ind->kpi_main_id ?? '') == $opt->id)>
                        {{ $opt->code ? '['.$opt->code.'] ' : '' }}{{ $opt->name }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </x-form.select>
</div>

{{-- ปีที่ derive จาก KPI หลัก (แสดงผลเท่านั้น ไม่มี input) --}}
@php
    $derivedYear = $ind?->main?->category?->subStrategy?->strategy?->year;
@endphp
<div id="ind-year-info" class="mt-3 flex items-center gap-2 rounded-lg px-3 py-2 text-xs {{ $derivedYear ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">
    @if ($derivedYear)
        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" /></svg>
        ปีของตัวชี้วัด: <strong id="ind-year-value">{{ $derivedYear }}</strong> <span class="opacity-70">(นำมาจาก KPI หลัก ที่เลือก)</span>
    @else
        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
        <span id="ind-year-value">กรุณาเลือก KPI หลัก เพื่อกำหนดปีของตัวชี้วัด</span>
    @endif
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-form.input name="code" label="รหัสตัวชี้วัด" :value="$ind->code ?? ''" />
    <div class="sm:col-span-2">
        <x-form.input name="name" label="ชื่อตัวชี้วัด" :value="$ind->name ?? ''" :required="true" />
    </div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-form.select name="year_type" label="แบบปี" :required="true">
        @foreach ($yearTypes as $key => $name)
            <option value="{{ $key }}" @selected(old('year_type', $ind->year_type ?? 'fiscal') === $key)>{{ $name }}</option>
        @endforeach
    </x-form.select>
    <x-form.select name="period_type" label="การเก็บผลงาน" :required="true">
        @foreach ($periodTypes as $key => $name)
            <option value="{{ $key }}" @selected(old('period_type', $ind->period_type ?? 'annual') === $key)>{{ $name }}</option>
        @endforeach
    </x-form.select>
    @php
        $currentUnit = old('unit', $ind->unit ?? '');
        $knownUnit = false;
    @endphp
    <x-form.select name="unit" label="หน่วยวัด" :required="true" help="เลือกตามกลุ่ม KPI">
        <option value="">— เลือกหน่วยวัด —</option>
        @foreach ($unitGroups as $groupCode => $groupUnits)
            <optgroup data-group="{{ $groupCode }}" label="{{ \App\Models\KpiUnit::GROUPS[$groupCode] ?? $groupCode }}">
                @foreach ($groupUnits as $unitOption)
                    @if ($currentUnit === $unitOption->name) @php $knownUnit = true; @endphp @endif
                    <option value="{{ $unitOption->name }}" @selected($currentUnit === $unitOption->name)>{{ $unitOption->name }}</option>
                @endforeach
            </optgroup>
        @endforeach
        @if ($currentUnit !== '' && ! $knownUnit)
            <option value="{{ $currentUnit }}" selected>{{ $currentUnit }} (กำหนดเอง)</option>
        @endif
    </x-form.select>
</div>

<p class="mt-2 rounded-lg bg-indigo-50 px-3 py-2 text-xs text-indigo-700">
    ระบบจะสร้างช่วงเวลาเก็บข้อมูลให้อัตโนมัติตาม "แบบปี" และ "การเก็บผลงาน" — กำหนดค่าเป้าหมายได้ที่เมนู "กำหนดค่าเป้าหมาย"
</p>

{{-- ประเภทการวัด (Measurement Type) + เงื่อนไขตามหลักการบริหารผลงาน --}}
@php
    $measurementType = old('measurement_type', $ind->measurement_type ?? '');
    $numeratorLabel = old('numerator_label', $ind->numerator_label ?? '');
    $denominatorLabel = old('denominator_label', $ind->denominator_label ?? '');
    $formulaVal = old('formula', $ind->formula ?? '');
    $factorVal = old('factor', $ind->factor ?? '');
@endphp
<div class="mt-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4" data-measurement>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-form.select name="measurement_type" label="ประเภทการวัด (Measurement Type)" :required="true"
            help="เลือกตามลักษณะการวัดผล — ระบบจะปรับช่องกรอกและกรองหน่วยวัดให้อัตโนมัติ">
            <option value="">— เลือกประเภทการวัด —</option>
            @foreach (\App\Support\MeasurementType::optgroups() as $gCode => $types)
                <optgroup label="{{ \App\Models\KpiUnit::GROUPS[$gCode] ?? $gCode }}">
                    @foreach ($types as $tCode => $tLabel)
                        <option value="{{ $tCode }}" @selected($measurementType === $tCode)>{{ $tLabel }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </x-form.select>
        <div class="flex items-end">
            <p class="w-full rounded-lg bg-white px-3 py-2 text-xs text-slate-500 ring-1 ring-slate-200" data-formula-hint>
                เลือกประเภทการวัดเพื่อดูสูตรมาตรฐาน
            </p>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div data-field="a" hidden>
            <x-form.input name="numerator_label" label="นิยามตัวตั้ง (A)" :value="$numeratorLabel" help="เช่น จำนวนที่ผ่านเกณฑ์" />
        </div>
        <div data-field="b" hidden>
            <x-form.input name="denominator_label" label="นิยามตัวหาร (B)" :value="$denominatorLabel" help="เช่น จำนวนทั้งหมด" />
        </div>
        <div data-field="factor" hidden>
            <x-form.input name="factor" label="ค่าคงที่ K" type="number" step="any" :value="$factorVal" help="เช่น 100000 (ต่อแสนประชากร)" />
        </div>
    </div>

    <div class="mt-4" data-field="formula" hidden>
        <x-form.input name="formula" label="สูตร/เกณฑ์การคำนวณ" :value="$formulaVal"
            help="ระบุเกณฑ์/สูตรการคำนวณของตัวชี้วัดนี้" />
    </div>
</div>

<div class="mt-4">
    <x-form.textarea name="description" label="นิยาม/รายละเอียด" :value="$ind->description ?? ''" />
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-user-picker name="owners[]" :users="$users" :selected="$selectedOwners" label="ผู้รับผิดชอบตัวชี้วัด (อย่างน้อย 1 คน)" :required="true" />
    <x-form.select name="primary_owner" label="ผู้รับผิดชอบหลัก (ถ้ามี)">
        <option value="">— ไม่ระบุ —</option>
        @foreach ($users as $u)
            <option value="{{ $u->id }}" @selected((string) $primaryOwner === (string) $u->id)>{{ $u->display_name }} ({{ $u->name }})</option>
        @endforeach
    </x-form.select>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.input name="orderby" label="ลำดับการแสดง" type="number" :value="$ind->orderby ?? 0" />
    <x-form.select name="status" label="สถานะ" :required="true">
        <option value="enable" @selected(old('status', $ind->status ?? 'enable') === 'enable')>เปิดใช้งาน</option>
        <option value="disable" @selected(old('status', $ind->status ?? '') === 'disable')>ปิดใช้งาน</option>
    </x-form.select>
</div>

<div class="mt-6 flex items-center gap-2">
    <x-btn type="submit" variant="primary">บันทึก</x-btn>
    <x-btn :href="route('indicators.index')" variant="secondary">ยกเลิก</x-btn>
</div>

@push('scripts')
{{-- Cascade: กรอง KPI หลัก ตาม ปี (helper) + ระดับตัวชี้วัด (submitted) --}}
<script>
(function () {
    const yearFilter = document.getElementById('ind-year-filter');
    const levelSel   = document.querySelector('select[name="level"]');
    const mainSel    = document.getElementById('kpi_main_id');
    const yearInfo   = document.getElementById('ind-year-info');
    const yearValue  = document.getElementById('ind-year-value');
    if (!yearFilter || !levelSel || !mainSel) return;

    function applyMains() {
        const year  = yearFilter.value;
        const level = levelSel.value;
        let hideSelected = false;

        mainSel.querySelectorAll('option[value]').forEach(opt => {
            if (!opt.value) return;
            const match = (!year  || opt.dataset.year  === year) &&
                          (!level || opt.dataset.level === level);
            opt.hidden   = !match;
            opt.disabled = !match;
            if (!match && opt.selected) hideSelected = true;
        });
        if (hideSelected) {
            mainSel.value = '';
            updateYearInfo();
        }

        // ซ่อน optgroup ที่ไม่มี option ที่มองเห็น
        mainSel.querySelectorAll('optgroup').forEach(og => {
            const hasVisible = Array.from(og.querySelectorAll('option')).some(o => !o.hidden && o.value);
            og.hidden   = !hasVisible;
            og.disabled = !hasVisible;
        });
    }

    function updateYearInfo() {
        if (!yearInfo || !yearValue) return;
        const opt  = mainSel.options[mainSel.selectedIndex];
        const year = (opt && opt.value) ? opt.dataset.year : null;
        if (year) {
            yearInfo.className = 'mt-3 flex items-center gap-2 rounded-lg px-3 py-2 text-xs bg-indigo-50 text-indigo-700';
            yearValue.textContent = year;
            yearInfo.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />';
        } else {
            yearInfo.className = 'mt-3 flex items-center gap-2 rounded-lg px-3 py-2 text-xs bg-amber-50 text-amber-700';
            yearValue.textContent = 'กรุณาเลือก KPI หลัก เพื่อกำหนดปีของตัวชี้วัด';
            yearInfo.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />';
        }
    }

    // โหมดแก้ไข: ตั้งปีกรองจาก KPI หลัก ที่เลือกอยู่
    const current = mainSel.options[mainSel.selectedIndex];
    if (current && current.value && current.dataset.year) {
        yearFilter.value = current.dataset.year;
    }

    yearFilter.addEventListener('change', applyMains);
    levelSel.addEventListener('change',   applyMains);
    mainSel.addEventListener('change',    updateYearInfo);

    applyMains();
})();
</script>

{{-- Measurement type fields toggle + unit filtering --}}
<script>
    (function () {
        const meta = @json(\App\Support\MeasurementType::META);
        const root = document.querySelector('[data-measurement]');
        if (!root) return;

        const typeSel = root.querySelector('select[name="measurement_type"]');
        const hint = root.querySelector('[data-formula-hint]');
        const fields = {
            a: root.querySelector('[data-field="a"]'),
            b: root.querySelector('[data-field="b"]'),
            factor: root.querySelector('[data-field="factor"]'),
            formula: root.querySelector('[data-field="formula"]'),
        };
        const formulaLabelEl = fields.formula ? fields.formula.querySelector('label') : null;
        const formulaLabelDefault = formulaLabelEl ? formulaLabelEl.textContent : '';
        const unitSel = document.getElementById('unit');

        function toggle(wrapper, show) {
            if (!wrapper) return;
            wrapper.hidden = !show;
            if (!show) {
                wrapper.querySelectorAll('input, textarea, select').forEach(el => { el.value = ''; });
            }
        }

        function filterUnits(group) {
            if (!unitSel) return;
            const selected = unitSel.options[unitSel.selectedIndex] || null;
            unitSel.querySelectorAll('optgroup').forEach(og => {
                const keep = !group || og.dataset.group === group || (selected && og.contains(selected));
                og.hidden = !keep;
                og.disabled = !keep;
            });
        }

        function apply() {
            const m = meta[typeSel.value] || null;
            toggle(fields.a, !!(m && (m.requires_a || m.allows_ab)));
            toggle(fields.b, !!(m && (m.requires_b || m.allows_ab)));
            toggle(fields.factor, !!(m && m.requires_factor));
            toggle(fields.formula, !!(m && m.requires_formula));

            if (formulaLabelEl) {
                formulaLabelEl.textContent = (m && m.requires_formula && m.formula_label) ? m.formula_label : formulaLabelDefault;
            }
            if (hint) {
                hint.textContent = m ? ('สูตรมาตรฐาน: ' + m.formula + ' · กลุ่ม ' + (m.group || '')) : 'เลือกประเภทการวัดเพื่อดูสูตรมาตรฐาน';
            }
            filterUnits(m ? m.group : null);
        }

        typeSel.addEventListener('change', apply);
        apply();
    })();
</script>
@endpush
