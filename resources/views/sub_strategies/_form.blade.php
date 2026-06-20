@php
    $ss = $subStrategy ?? null;
    $selectedReviewers = old('reviewers', $ss ? $ss->reviewers->pluck('id')->all() : []);
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-form.select name="strategy_id" label="ยุทธศาสตร์" :required="true">
        <option value="">— เลือกยุทธศาสตร์ —</option>
        @foreach ($strategyOptions as $opt)
            <option value="{{ $opt->id }}" @selected(old('strategy_id', $ss->strategy_id ?? '') == $opt->id)>
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
