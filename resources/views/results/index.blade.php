@php use App\Models\KpiIndicator; @endphp

<x-layouts.app title="บันทึกผลงาน" header="บันทึกผลงาน">
    <form method="GET" class="mb-5 flex flex-wrap items-end gap-2">
        <div>
            <label class="block text-xs text-slate-500">ระดับ</label>
            <select name="level" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm">
                <option value="">ทุกระดับ</option>
                @foreach (KpiIndicator::LEVELS as $k => $v)
                    <option value="{{ $k }}" @selected(($filters['level'] ?? '') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500">ปี</label>
            <select name="year" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm">
                <option value="">ทุกปี</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}" @selected(($filters['year'] ?? '') == $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500">ค้นหา</label>
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ชื่อ/รหัส" class="rounded-lg border-slate-300 text-sm shadow-sm">
        </div>
        <x-btn type="submit" variant="secondary">ค้นหา</x-btn>
    </form>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ตัวชี้วัด</th>
                        <th class="px-5 py-3">ระดับ</th>
                        <th class="px-5 py-3 text-center">ความคืบหน้า</th>
                        <th class="px-5 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($indicators as $ind)
                        @php
                            $total = $ind->targets->count();
                            $done = $ind->targets->filter(fn ($t) => $t->result && $t->result->pass_status !== 'pending')->count();
                            $noTarget = $ind->hasNoTargetDefined();
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ $ind->name }}</div>
                                <div class="text-xs text-slate-400">{{ $ind->main?->category?->name }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $ind->level_label }}</td>
                            <td class="px-5 py-3 text-center">
                                @if ($noTarget)
                                    <span title="ตัวชี้วัดนี้ยังไม่ได้กำหนดค่าเป้าหมาย"
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> ยังไม่ได้กำหนดค่าเป้าหมาย
                                    </span>
                                @else
                                    <span class="text-slate-600">{{ $done }}/{{ $total }} ช่วง</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                {{-- รายการถูกกรองไว้แล้วให้เหลือเฉพาะตัวชี้วัดที่ผู้ใช้มีสิทธิ์บันทึก --}}
                                @if ($noTarget)
                                    <span class="text-xs text-slate-400">ต้องกำหนดค่าเป้าหมายก่อน</span>
                                @else
                                    <x-btn :href="route('results.edit', $ind)" variant="ghost"><x-icon name="result" class="w-4 h-4" /> บันทึกผล</x-btn>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">ไม่มีตัวชี้วัดที่คุณมีสิทธิ์บันทึกผล</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $indicators->links() }}</div>
</x-layouts.app>
