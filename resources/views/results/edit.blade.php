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

                            @if ($t->operator === 'passfail')
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <x-form.select :name="'results[' . $t->id . '][result_text]'" label="ผลการประเมิน">
                                        <option value="">— ยังไม่บันทึก —</option>
                                        <option value="pass" @selected(old('results.'.$t->id.'.result_text', $r?->result_text) === 'pass')>ผ่าน</option>
                                        <option value="fail" @selected(old('results.'.$t->id.'.result_text', $r?->result_text) === 'fail')>ไม่ผ่าน</option>
                                    </x-form.select>
                                    <div class="sm:col-span-2">
                                        <x-form.input :name="'results[' . $t->id . '][note]'" label="หมายเหตุ" :value="old('results.'.$t->id.'.note', $r?->note)" />
                                    </div>
                                </div>
                            @elseif ($indicator->usesNumeratorDenominator())
                                {{-- ประเภท A/B: กรอกตัวตั้ง (A) และตัวหาร (B) แล้วระบบคำนวณผลให้อัตโนมัติ --}}
                                <div data-abcard data-type="{{ $indicator->measurement_type }}" data-factor="{{ $indicator->factor }}">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <x-form.input :name="'results[' . $t->id . '][numerator_value]'"
                                            label="{{ $indicator->numerator_label ?: 'ตัวตั้ง (A)' }}" type="number" step="any" data-a="1"
                                            :value="old('results.'.$t->id.'.numerator_value', $r?->numerator_value)" />
                                        <x-form.input :name="'results[' . $t->id . '][denominator_value]'"
                                            label="{{ $indicator->denominator_label ?: 'ตัวหาร (B)' }}" type="number" step="any" data-b="1"
                                            :value="old('results.'.$t->id.'.denominator_value', $r?->denominator_value)" />
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-slate-700">ผลคำนวณ{{ $indicator->unit ? ' (' . $indicator->unit . ')' : '' }}</label>
                                            <div class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-indigo-700" data-out>—</div>
                                            <p class="mt-1 text-xs text-slate-400">สูตร {{ $indicator->formula_display }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <x-form.input :name="'results[' . $t->id . '][note]'" label="หมายเหตุ" :value="old('results.'.$t->id.'.note', $r?->note)" />
                                    </div>
                                </div>
                            @else
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <x-form.input :name="'results[' . $t->id . '][result_value]'" label="ค่าผลงาน{{ $indicator->unit ? ' (' . $indicator->unit . ')' : '' }}" type="number" step="any"
                                        :value="old('results.'.$t->id.'.result_value', $r?->result_value)" />
                                    <div class="sm:col-span-2">
                                        <x-form.input :name="'results[' . $t->id . '][note]'" label="หมายเหตุ" :value="old('results.'.$t->id.'.note', $r?->note)" />
                                    </div>
                                </div>
                            @endif
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

    @push('scripts')
        <script>
            (function () {
                function compute(type, a, b, factor) {
                    if (a === '' || b === '' || a == null || b == null) return null;
                    a = Number(a); b = Number(b); factor = Number(factor) || 0;
                    if (!isFinite(a) || !isFinite(b) || b === 0) return null;
                    switch (type) {
                        case 'percent': return (a / b) * 100;
                        case 'rate': return (a / b) * factor;
                        case 'average':
                        case 'ratio': return a / b;
                        default: return null;
                    }
                }
                function fmt(n) {
                    if (n === null || !isFinite(n)) return '—';
                    return (Math.round(n * 100) / 100).toLocaleString('th-TH', { maximumFractionDigits: 2 });
                }
                document.querySelectorAll('[data-abcard]').forEach(function (card) {
                    const type = card.dataset.type;
                    const factor = card.dataset.factor;
                    const aEl = card.querySelector('[data-a]');
                    const bEl = card.querySelector('[data-b]');
                    const out = card.querySelector('[data-out]');
                    if (!aEl || !bEl || !out) return;
                    const update = () => { out.textContent = fmt(compute(type, aEl.value, bEl.value, factor)); };
                    aEl.addEventListener('input', update);
                    bEl.addEventListener('input', update);
                    update();
                });
            })();
        </script>
    @endpush
</x-layouts.app>
