@php
    $ind = $indicator ?? null;
    $selectedOwners = old('owners', $ind ? $ind->owners->pluck('id')->all() : []);
    $primaryOwner = old('primary_owner', $ind?->owners->firstWhere('pivot.is_primary', true)?->id);
    $subStrategyYears = $subStrategyOptions->pluck('strategy.year')->filter()->unique()->sortDesc()->values();
@endphp

{{-- กรองกลยุทธ์ตามปี — เลือกปีเพื่อให้ช่อง "กลยุทธ์" แสดงเฉพาะของปีที่เลือก --}}
<div class="mb-4">
    <label for="ind-year-filter" class="mb-1 block text-sm font-medium text-slate-700">ปี (กรองกลยุทธ์)</label>
    <select id="ind-year-filter"
        class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs">
        <option value="">— ทุกปี —</option>
        @foreach ($subStrategyYears as $y)
            <option value="{{ $y }}">{{ $y }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-slate-400">เลือกปีและระดับตัวชี้วัดเพื่อแสดงเฉพาะกลยุทธ์ (ภายใต้ยุทธศาสตร์) ที่สัมพันธ์กันในช่องด้านล่าง</p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.select name="sub_strategy_id" label="กลยุทธ์ (ภายใต้ยุทธศาสตร์)" :required="true">
        <option value="">— เลือกกลยุทธ์ —</option>
        @foreach ($subStrategyOptions as $opt)
            <option value="{{ $opt->id }}" data-year="{{ $opt->strategy?->year }}" data-level="{{ $opt->strategy?->level }}"
                @selected(old('sub_strategy_id', $ind->sub_strategy_id ?? '') == $opt->id)>
                [{{ $opt->strategy?->year }} · {{ $opt->strategy?->level_label }}] {{ $opt->strategy?->name }} › {{ $opt->name }}
            </option>
        @endforeach
    </x-form.select>

    <x-form.select name="level" label="ระดับตัวชี้วัด" :required="true">
        @foreach ($levels as $key => $name)
            <option value="{{ $key }}" @selected(old('level', $ind->level ?? 'hospital') === $key)>{{ $name }}</option>
        @endforeach
    </x-form.select>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-form.input name="code" label="รหัสตัวชี้วัด" :value="$ind->code ?? ''" />
    <div class="sm:col-span-2">
        <x-form.input name="name" label="ชื่อตัวชี้วัด" :value="$ind->name ?? ''" :required="true" />
    </div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
    <x-form.select name="year_type" label="แบบปี" :required="true">
        @foreach ($yearTypes as $key => $name)
            <option value="{{ $key }}" @selected(old('year_type', $ind->year_type ?? 'fiscal') === $key)>{{ $name }}</option>
        @endforeach
    </x-form.select>
    <x-form.input name="year" label="ปี พ.ศ." type="number" :value="$ind->year ?? (now()->year + 543)" :required="true" />
    <x-form.select name="period_type" label="การเก็บผลงาน" :required="true">
        @foreach ($periodTypes as $key => $name)
            <option value="{{ $key }}" @selected(old('period_type', $ind->period_type ?? 'annual') === $key)>{{ $name }}</option>
        @endforeach
    </x-form.select>
    @php
        $currentUnit = old('unit', $ind->unit ?? '');
        $knownUnit = false;
    @endphp
    <x-form.select name="unit" label="หน่วยวัด" help="เลือกตามกลุ่ม KPI">
        <option value="">— ไม่ระบุ —</option>
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
    ระบบจะสร้างช่วงเวลาเก็บข้อมูลให้อัตโนมัติตาม “แบบปี” และ “การเก็บผลงาน” — กำหนดค่าเป้าหมายได้ที่เมนู “กำหนดค่าเป้าหมาย”
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
        <x-form.select name="measurement_type" label="ประเภทการวัด (Measurement Type)"
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

@push('scripts')
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

            // กรองหน่วยวัดให้เหลือเฉพาะกลุ่มของประเภทที่เลือก (ไม่ซ่อนกลุ่มของค่าที่เลือกไว้แล้ว)
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
    <script>
        (function () {
            const yearFilter = document.getElementById('ind-year-filter');
            const levelSel = document.getElementById('level');
            const sel = document.getElementById('sub_strategy_id');
            if (!yearFilter || !sel) return;

            // แสดงเฉพาะกลยุทธ์ที่ตรงทั้งปี (ค่าว่าง = ทุกปี) และระดับตัวชี้วัดที่เลือก
            function apply(resetIfHidden) {
                const year = yearFilter.value;
                const level = levelSel ? levelSel.value : '';
                let hideSelected = false;
                Array.from(sel.options).forEach(opt => {
                    if (!opt.value) return; // คงตัวเลือก placeholder ไว้
                    const match = (!year || opt.dataset.year === year)
                        && (!level || opt.dataset.level === level);
                    opt.hidden = !match;
                    opt.disabled = !match;
                    if (!match && opt.selected) hideSelected = true;
                });
                if (resetIfHidden && hideSelected) sel.value = ''; // เปลี่ยนตัวกรองแล้วตัวที่เลือกหลุดเงื่อนไข → รีเซ็ต
            }

            // โหมดแก้ไข/หลัง validation: ตั้งปีให้ตรงกับกลยุทธ์ที่เลือกอยู่ แล้วกรองโดยไม่รีเซ็ตตัวที่เลือก
            const current = sel.options[sel.selectedIndex];
            if (current && current.value && current.dataset.year) yearFilter.value = current.dataset.year;

            yearFilter.addEventListener('change', () => apply(true));
            if (levelSel) levelSel.addEventListener('change', () => apply(true));
            apply(false);
        })();
    </script>
@endpush
