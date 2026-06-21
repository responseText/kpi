@php $s = $strategy ?? null; @endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.input name="year" label="ปี พ.ศ." type="number" :value="$s->year ?? (now()->year + 543)" :required="true" help="เช่น 2569" />
    <x-form.select name="level" label="ระดับตัวชี้วัด" :required="true" help="1 ยุทธศาสตร์ สังกัด 1 ระดับ">
        @foreach (($levels ?? \App\Models\KpiStrategy::LEVELS) as $key => $label)
            <option value="{{ $key }}" @selected(old('level', $s->level ?? array_key_first($levels ?? \App\Models\KpiStrategy::LEVELS)) === $key)>{{ $label }}</option>
        @endforeach
    </x-form.select>
</div>

<div class="mt-4">
    <x-form.input name="code" label="รหัสยุทธศาสตร์" :value="$s->code ?? ''" placeholder="เช่น ยุทธศาสตร์ที่ 1" />
</div>

<div class="mt-4">
    <x-form.input name="name" label="ชื่อยุทธศาสตร์" :value="$s->name ?? ''" :required="true" />
</div>

<div class="mt-4">
    <x-form.textarea name="description" label="รายละเอียด" :value="$s->description ?? ''" />
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.input name="orderby" label="ลำดับการแสดง" type="number" :value="$s->orderby ?? 0" />
    <x-form.select name="status" label="สถานะ" :required="true">
        <option value="enable" @selected(old('status', $s->status ?? 'enable') === 'enable')>เปิดใช้งาน</option>
        <option value="disable" @selected(old('status', $s->status ?? '') === 'disable')>ปิดใช้งาน</option>
    </x-form.select>
</div>

<div class="mt-6 flex items-center gap-2">
    <x-btn type="submit" variant="primary">บันทึก</x-btn>
    <x-btn :href="route('strategies.index')" variant="secondary">ยกเลิก</x-btn>
</div>
