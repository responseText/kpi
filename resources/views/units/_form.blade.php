@php $u = $unit ?? null; @endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.select name="group_code" label="กลุ่ม KPI" :required="true" help="จัดตามหลักการบริหารผลงาน">
        @foreach (\App\Models\KpiUnit::GROUPS as $key => $label)
            <option value="{{ $key }}" @selected(old('group_code', $u->group_code ?? 'quantity') === $key)>{{ $label }}</option>
        @endforeach
    </x-form.select>
    <x-form.input name="name" label="หน่วยวัด" :value="$u->name ?? ''" :required="true" placeholder="เช่น ร้อยละ / ครั้ง / คน" />
</div>

<div class="mt-4">
    <x-form.input name="description" label="คำอธิบาย (ถ้ามี)" :value="$u->description ?? ''" />
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.input name="orderby" label="ลำดับการแสดง" type="number" :value="$u->orderby ?? 0" />
    <x-form.select name="status" label="สถานะ" :required="true">
        <option value="enable" @selected(old('status', $u->status ?? 'enable') === 'enable')>เปิดใช้งาน</option>
        <option value="disable" @selected(old('status', $u->status ?? '') === 'disable')>ปิดใช้งาน</option>
    </x-form.select>
</div>

<div class="mt-6 flex items-center gap-2">
    <x-btn type="submit" variant="primary">บันทึก</x-btn>
    <x-btn :href="route('units.index')" variant="secondary">ยกเลิก</x-btn>
</div>
