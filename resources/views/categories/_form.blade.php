@php $cat = $category ?? null; @endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.select name="sub_strategy_id" label="กลยุทธ์ (ไม่บังคับ)" help="ปล่อยว่างได้หากยังไม่ผูกกับกลยุทธ์">
        <option value="">— ไม่ผูกกลยุทธ์ —</option>
        @foreach ($subStrategyOptions as $opt)
            <option value="{{ $opt->id }}" @selected(old('sub_strategy_id', $cat->sub_strategy_id ?? '') == $opt->id)>
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
