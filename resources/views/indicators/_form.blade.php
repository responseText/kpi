@php
    $ind = $indicator ?? null;
    $selectedOwners = old('owners', $ind ? $ind->owners->pluck('id')->all() : []);
    $primaryOwner = old('primary_owner', $ind?->owners->firstWhere('pivot.is_primary', true)?->id);
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.select name="sub_strategy_id" label="กลยุทธ์ (ภายใต้ยุทธศาสตร์)" :required="true">
        <option value="">— เลือกกลยุทธ์ —</option>
        @foreach ($subStrategyOptions as $opt)
            <option value="{{ $opt->id }}" @selected(old('sub_strategy_id', $ind->sub_strategy_id ?? '') == $opt->id)>
                [{{ $opt->strategy?->year }}] {{ $opt->strategy?->name }} › {{ $opt->name }}
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
            <optgroup label="{{ \App\Models\KpiUnit::GROUPS[$groupCode] ?? $groupCode }}">
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
