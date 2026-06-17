<x-layouts.app title="กำหนดค่าเป้าหมาย" header="กำหนดค่าเป้าหมาย">
    <div class="max-w-4xl">
        <x-card :title="$indicator->name" :subtitle="$indicator->level_label . ' · ' . $indicator->year_type_label . ' ' . $indicator->year . ' · ' . $indicator->period_type_label">
            <form method="POST" action="{{ route('targets.update', $indicator) }}">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    @foreach ($indicator->targets as $t)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <h4 class="font-semibold text-slate-700">{{ $t->period_label }}</h4>
                                <span class="text-xs text-slate-400">{{ $t->thai_range }}</span>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <x-form.select :name="'targets[' . $t->period_no . '][operator]'" label="เงื่อนไขเกณฑ์">
                                    @foreach ($operators as $opKey => $opLabel)
                                        <option value="{{ $opKey }}" @selected(old('targets.'.$t->period_no.'.operator', $t->operator) === $opKey)>{{ $opLabel }}</option>
                                    @endforeach
                                </x-form.select>
                                <x-form.input :name="'targets[' . $t->period_no . '][target_value]'" label="ค่าเป้าหมาย (ตัวเลข)" type="number" step="any"
                                    :value="old('targets.'.$t->period_no.'.target_value', $t->target_value)" />
                                <x-form.input :name="'targets[' . $t->period_no . '][target_text]'" label="คำอธิบายเป้าหมาย (ถ้ามี)"
                                    :value="old('targets.'.$t->period_no.'.target_text', $t->target_text)" placeholder="เช่น ผ่านเกณฑ์ระดับ 5" />
                            </div>
                            @error('targets.'.$t->period_no.'.target_value')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>

                <p class="mt-3 text-xs text-slate-400">หมายเหตุ: เลือกเงื่อนไข “ผ่าน/ไม่ผ่าน” สำหรับตัวชี้วัดที่ไม่มีค่าตัวเลข — ระบบจะประเมินจากผลที่บันทึก</p>

                <div class="mt-6 flex items-center gap-2">
                    <x-btn type="submit" variant="primary">บันทึกค่าเป้าหมาย</x-btn>
                    <x-btn :href="route('indicators.show', $indicator)" variant="secondary">ยกเลิก</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
