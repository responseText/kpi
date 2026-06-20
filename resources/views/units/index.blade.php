@php
    $groups = \App\Models\KpiUnit::GROUPS;
    $groupStyles = [
        'quantity'   => 'bg-sky-50 text-sky-700',
        'quality'    => 'bg-violet-50 text-violet-700',
        'time'       => 'bg-amber-50 text-amber-700',
        'cost'       => 'bg-rose-50 text-rose-700',
        'efficiency' => 'bg-emerald-50 text-emerald-700',
    ];
@endphp

<x-layouts.app title="หน่วยวัด KPI" header="จัดการหน่วยวัด KPI">
    <p class="mb-5 rounded-lg bg-indigo-50 px-3 py-2 text-xs text-indigo-700">
        จัดหมวดหมู่หน่วยวัดตามหลักการบริหารผลงาน (Performance Measurement) — แบ่งเป็น 5 กลุ่ม KPI
        หน่วยวัดเหล่านี้จะปรากฏให้เลือกในฟอร์ม “ตัวชี้วัด” (จัดกลุ่มตามประเภท)
    </p>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <label class="text-sm text-slate-600">กลุ่ม KPI</label>
            <select name="group" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">ทุกกลุ่ม</option>
                @foreach ($groups as $code => $label)
                    <option value="{{ $code }}" @selected(($group ?? '') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </form>

        <x-btn :href="route('units.create')"><x-icon name="unit" class="w-4 h-4" /> เพิ่มหน่วยวัด</x-btn>
    </div>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">กลุ่ม KPI</th>
                        <th class="px-5 py-3">หน่วยวัด</th>
                        <th class="px-5 py-3">คำอธิบาย</th>
                        <th class="px-5 py-3 text-center">ลำดับ</th>
                        <th class="px-5 py-3 text-center">สถานะ</th>
                        <th class="px-5 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($units as $u)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $groupStyles[$u->group_code] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $u->group_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $u->name }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $u->description ?: '-' }}</td>
                            <td class="px-5 py-3 text-center text-slate-500">{{ $u->orderby }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $u->status === 'enable' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500' }}">
                                    {{ $u->status === 'enable' ? 'เปิด' : 'ปิด' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <x-btn :href="route('units.edit', $u)" variant="ghost">แก้ไข</x-btn>
                                    <form method="POST" action="{{ route('units.destroy', $u) }}" onsubmit="return confirm('ยืนยันลบหน่วยวัดนี้? (ตัวชี้วัดที่บันทึกหน่วยวัดนี้ไว้แล้วจะไม่ถูกเปลี่ยน)')">
                                        @csrf @method('DELETE')
                                        <x-btn type="submit" variant="ghost" class="!text-red-600 hover:!bg-red-50">ลบ</x-btn>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">ยังไม่มีหน่วยวัดในกลุ่มนี้</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $units->withQueryString()->links() }}</div>
</x-layouts.app>
