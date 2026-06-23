@php $m = $main ?? null; @endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.select name="category_id" label="หมวด KPI" :required="true">
        <option value="">— เลือกหมวด KPI —</option>
        @foreach ($categoryOptions as $opt)
            <option value="{{ $opt->id }}" @selected(old('category_id', $m->category_id ?? '') == $opt->id)>
                {{ $opt->code ? '['.$opt->code.'] ' : '' }}{{ $opt->name }}
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
