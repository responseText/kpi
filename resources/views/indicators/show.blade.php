@php use App\Services\KpiEvaluator; $user = auth()->user(); @endphp

<x-layouts.app title="รายละเอียดตัวชี้วัด" header="รายละเอียดตัวชี้วัด">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <x-btn :href="route('indicators.index')" variant="secondary">← กลับ</x-btn>
        <div class="flex gap-2">
            @if ($user->canMenu('kpi.target', 'edit'))
                <x-btn :href="route('targets.edit', $indicator)" variant="secondary"><x-icon name="target" class="w-4 h-4" /> กำหนดค่าเป้าหมาย</x-btn>
            @endif
            @if ($user->canMenu('kpi.result', 'edit') && $user->canRecordResultFor($indicator))
                <x-btn :href="route('results.edit', $indicator)" variant="success"><x-icon name="result" class="w-4 h-4" /> บันทึกผลงาน</x-btn>
            @endif
            @if ($user->canMenu('kpi.indicator', 'edit'))
                <x-btn :href="route('indicators.edit', $indicator)"><x-icon name="indicator" class="w-4 h-4" /> แก้ไข</x-btn>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-5">
            <x-card :title="$indicator->name" :subtitle="$indicator->code">
                <dl class="grid grid-cols-2 gap-y-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-slate-400">ระดับ</dt><dd class="font-medium text-slate-700">{{ $indicator->level_label }}</dd></div>
                    <div><dt class="text-slate-400">แบบปี</dt><dd class="font-medium text-slate-700">{{ $indicator->year_type_label }} {{ $indicator->year }}</dd></div>
                    <div><dt class="text-slate-400">การเก็บผลงาน</dt><dd class="font-medium text-slate-700">{{ $indicator->period_type_label }}</dd></div>
                    <div><dt class="text-slate-400">หน่วยวัด</dt><dd class="font-medium text-slate-700">{{ $indicator->unit ?: '-' }}</dd></div>
                    @if ($indicator->measurement_type)
                        <div><dt class="text-slate-400">ประเภทการวัด</dt>
                            <dd class="font-medium text-slate-700">{{ $indicator->measurement_type_label }}
                                <span class="text-xs text-slate-400">· {{ $indicator->measurement_group_label }}</span>
                            </dd></div>
                        <div><dt class="text-slate-400">สูตรคำนวณ</dt>
                            <dd class="font-medium text-slate-700">{{ $indicator->formula_display ?: '-' }}</dd></div>
                    @endif
                    <div class="col-span-2 sm:col-span-3"><dt class="text-slate-400">ยุทธศาสตร์ › กลยุทธ์</dt>
                        <dd class="font-medium text-slate-700">{{ $indicator->subStrategy?->strategy?->name }} › {{ $indicator->subStrategy?->name }}</dd></div>
                    @if ($indicator->numerator_label)
                        <div class="col-span-2 sm:col-span-3 grid grid-cols-2 gap-3 rounded-lg bg-slate-50 p-3">
                            <div><dt class="text-slate-400">ตัวตั้ง (A)</dt><dd class="font-medium text-slate-700">{{ $indicator->numerator_label }}</dd></div>
                            <div><dt class="text-slate-400">ตัวหาร (B)</dt><dd class="font-medium text-slate-700">{{ $indicator->denominator_label ?: '-' }}</dd></div>
                        </div>
                    @endif
                </dl>
                @if ($indicator->description)
                    <div class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-600">{{ $indicator->description }}</div>
                @endif
            </x-card>

            <x-card title="ค่าเป้าหมายและผลงานรายช่วง">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-2">ช่วง</th>
                                <th class="px-3 py-2">ระยะเวลา</th>
                                <th class="px-3 py-2 text-center">เกณฑ์</th>
                                <th class="px-3 py-2 text-right">เป้าหมาย</th>
                                <th class="px-3 py-2 text-right">ผลงาน</th>
                                <th class="px-3 py-2 text-center">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($indicator->targets as $t)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $t->period_label }}</td>
                                    <td class="px-3 py-2 text-slate-500">{{ $t->thai_range }}</td>
                                    <td class="px-3 py-2 text-center">{{ $t->operator_symbol }}</td>
                                    <td class="px-3 py-2 text-right text-slate-700">
                                        {{ $t->operator === 'passfail' ? ($t->target_text ?: '-') : ($t->target_value !== null ? rtrim(rtrim((string) $t->target_value, '0'), '.') : '-') }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-slate-700">
                                        @php $r = $t->result; @endphp
                                        {{ $r ? ($t->operator === 'passfail' ? ($r->result_text === 'pass' ? 'ผ่าน' : 'ไม่ผ่าน') : ($r->result_value !== null ? rtrim(rtrim((string) $r->result_value, '0'), '.') : '-')) : '—' }}
                                        @if ($r && $r->numerator_value !== null)
                                            <div class="text-[11px] text-slate-400">{{ rtrim(rtrim((string) $r->numerator_value, '0'), '.') }} ÷ {{ $r->denominator_value !== null ? rtrim(rtrim((string) $r->denominator_value, '0'), '.') : '-' }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center"><x-status-badge :status="$t->result?->pass_status ?? 'pending'" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="space-y-5">
            <x-card title="ผู้รับผิดชอบ">
                <ul class="space-y-2 text-sm">
                    @forelse ($indicator->owners as $o)
                        <li class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ mb_substr($o->display_name, 0, 1) }}</span>
                            <span class="text-slate-700">{{ $o->display_name }}</span>
                            @if ($o->pivot->is_primary)<span class="rounded bg-amber-100 px-1.5 py-0.5 text-[11px] text-amber-700">หลัก</span>@endif
                        </li>
                    @empty
                        <li class="text-red-400">ยังไม่มีผู้รับผิดชอบ</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
</x-layouts.app>
