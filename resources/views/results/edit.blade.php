<x-layouts.app title="บันทึกผลงาน" header="บันทึกผลงาน">
    <div class="max-w-4xl">
        <x-card :title="$indicator->name" :subtitle="$indicator->level_label . ' · ' . $indicator->year_type_label . ' ' . $indicator->year">
            <form method="POST" action="{{ route('results.update', $indicator) }}">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    @foreach ($indicator->targets as $t)
                        @php $r = $t->result; @endphp
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h4 class="font-semibold text-slate-700">{{ $t->period_label }}</h4>
                                    <span class="text-xs text-slate-400">{{ $t->thai_range }}</span>
                                </div>
                                <div class="text-xs text-slate-500">
                                    เป้าหมาย: <span class="font-medium">{{ $t->operator_symbol }}
                                    {{ $t->operator === 'passfail' ? ($t->target_text ?: 'ผ่าน') : (rtrim(rtrim((string) $t->target_value, '0'), '.') ?: '-') }}{{ $indicator->unit ? ' '.$indicator->unit : '' }}</span>
                                    @if ($r && $r->pass_status !== 'pending')<x-status-badge :status="$r->pass_status" class="ml-2" />@endif
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                @if ($t->operator === 'passfail')
                                    <x-form.select :name="'results[' . $t->id . '][result_text]'" label="ผลการประเมิน">
                                        <option value="">— ยังไม่บันทึก —</option>
                                        <option value="pass" @selected(old('results.'.$t->id.'.result_text', $r?->result_text) === 'pass')>ผ่าน</option>
                                        <option value="fail" @selected(old('results.'.$t->id.'.result_text', $r?->result_text) === 'fail')>ไม่ผ่าน</option>
                                    </x-form.select>
                                @else
                                    <x-form.input :name="'results[' . $t->id . '][result_value]'" label="ค่าผลงาน{{ $indicator->unit ? ' ('.$indicator->unit.')' : '' }}" type="number" step="any"
                                        :value="old('results.'.$t->id.'.result_value', $r?->result_value)" />
                                @endif
                                <div class="sm:col-span-2">
                                    <x-form.input :name="'results[' . $t->id . '][note]'" label="หมายเหตุ" :value="old('results.'.$t->id.'.note', $r?->note)" />
                                </div>
                            </div>
                            @if ($r && $r->recorded_at)
                                <p class="mt-2 text-xs text-slate-400">บันทึกล่าสุดโดย {{ $r->recorder?->display_name ?? '-' }} เมื่อ {{ \App\Services\PeriodCalculator::thaiDate($r->recorded_at) }} {{ $r->recorded_at->format('H:i') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex items-center gap-2">
                    <x-btn type="submit" variant="success">บันทึกผลงาน</x-btn>
                    <x-btn :href="route('indicators.show', $indicator)" variant="secondary">ยกเลิก</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
