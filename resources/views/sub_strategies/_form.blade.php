@php
    $ss = $subStrategy ?? null;
    $selectedReviewers = old('reviewers', $ss ? $ss->reviewers->pluck('id')->all() : []);
    $strategyYears = $strategyOptions->pluck('year')->filter()->unique()->sortDesc()->values();
@endphp

{{-- กรองยุทธศาสตร์ตามระดับ+ปี --}}
<div class="mb-4 flex flex-wrap items-end gap-4">
    <div>
        <label for="ss-level-filter" class="mb-1 block text-sm font-medium text-slate-700">ระดับตัวชี้วัด</label>
        <select id="ss-level-filter"
            class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-48">
            <option value="">— ทุกระดับ —</option>
            @foreach ($levelOptions as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="ss-year-filter" class="mb-1 block text-sm font-medium text-slate-700">ปี</label>
        <select id="ss-year-filter"
            class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-32">
            <option value="">— ทุกปี —</option>
            @foreach ($strategyYears as $y)
                <option value="{{ $y }}">{{ $y }}</option>
            @endforeach
        </select>
    </div>
    <p class="text-xs text-slate-400">เลือกระดับ/ปี เพื่อกรองรายการยุทธศาสตร์ด้านล่าง</p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.select name="strategy_id" label="ยุทธศาสตร์" :required="true">
        <option value="">— เลือกยุทธศาสตร์ —</option>
        @foreach ($strategyOptions as $opt)
            <option value="{{ $opt->id }}"
                data-year="{{ $opt->year }}"
                data-level="{{ $opt->level }}"
                @selected(old('strategy_id', $ss->strategy_id ?? '') == $opt->id)>
                [{{ $opt->year }} · {{ $opt->level_label }}] {{ $opt->name }}
            </option>
        @endforeach
    </x-form.select>
    <x-form.input name="code" label="รหัสกลยุทธ์" :value="$ss->code ?? ''" placeholder="เช่น กลยุทธ์ 1.1" />
</div>

<div class="mt-4">
    <x-form.input name="name" label="ชื่อกลยุทธ์" :value="$ss->name ?? ''" :required="true" />
</div>

<div class="mt-4">
    <x-form.textarea name="description" label="รายละเอียด" :value="$ss->description ?? ''" />
</div>

<div class="mt-4">
    <x-user-picker name="reviewers[]" :users="$users" :selected="$selectedReviewers" label="ผู้ตรวจสอบกลยุทธ์ (อย่างน้อย 1 คน)" :required="true" />
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.input name="orderby" label="ลำดับการแสดง" type="number" :value="$ss->orderby ?? 0" />
    <x-form.select name="status" label="สถานะ" :required="true">
        <option value="enable" @selected(old('status', $ss->status ?? 'enable') === 'enable')>เปิดใช้งาน</option>
        <option value="disable" @selected(old('status', $ss->status ?? '') === 'disable')>ปิดใช้งาน</option>
    </x-form.select>
</div>

<div class="mt-6 flex items-center gap-2">
    <x-btn type="submit" variant="primary">บันทึก</x-btn>
    <x-btn :href="route('sub-strategies.index')" variant="secondary">ยกเลิก</x-btn>
</div>

@push('scripts')
    <script>
        (function () {
            const levelFilter = document.getElementById('ss-level-filter');
            const yearFilter  = document.getElementById('ss-year-filter');
            const sel         = document.getElementById('strategy_id');
            if (!levelFilter || !yearFilter || !sel) return;

            function apply() {
                const level = levelFilter.value;
                const year  = yearFilter.value;
                let hideSelected = false;

                Array.from(sel.options).forEach(opt => {
                    if (!opt.value) return; // placeholder
                    const matchLevel = !level || opt.dataset.level === level;
                    const matchYear  = !year  || opt.dataset.year  === year;
                    const match = matchLevel && matchYear;
                    opt.hidden   = !match;
                    opt.disabled = !match;
                    if (!match && opt.selected) hideSelected = true;
                });

                if (hideSelected) sel.value = '';
            }

            // โหมดแก้ไข: ตั้งระดับ+ปีให้ตรงกับยุทธศาสตร์ที่เลือกอยู่
            const current = sel.options[sel.selectedIndex];
            if (current && current.value) {
                if (current.dataset.level) levelFilter.value = current.dataset.level;
                if (current.dataset.year)  yearFilter.value  = current.dataset.year;
            }

            levelFilter.addEventListener('change', apply);
            yearFilter.addEventListener('change', apply);
            apply();
        })();
    </script>
@endpush
