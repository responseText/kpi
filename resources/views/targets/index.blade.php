@php use App\Models\KpiIndicator; $user = auth()->user(); @endphp

<x-layouts.app title="กำหนดค่าเป้าหมาย" header="กำหนดค่าเป้าหมาย">
    <form method="GET" class="mb-5 flex flex-wrap items-end gap-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">ระดับ</label>
            <select name="level" onchange="this.form.submit()"
                class="rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">ทุกระดับ</option>
                @foreach (KpiIndicator::LEVELS as $k => $v)
                    <option value="{{ $k }}" @selected(($filters['level'] ?? '') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">ปี</label>
            <select name="year" onchange="this.form.submit()"
                class="rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">ทุกปี</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}" @selected(($filters['year'] ?? '') == $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">ค้นหา</label>
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ชื่อ/รหัส"
                class="rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                        <th class="px-5 py-3">รูปแบบ</th>
                        <th class="px-5 py-3 text-center">ช่วงที่ตั้งเป้าแล้ว</th>
                        <th class="px-5 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($indicators as $ind)
                        @php
                            $total = $ind->targets->count();
                            $defined = $ind->definedTargetsCount();
                            $noTarget = $defined === 0;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="font-medium text-slate-800">{{ $ind->name }}</div>
                                    @if ($noTarget)
                                        <span title="ตัวชี้วัดนี้ยังไม่ได้กำหนดค่าเป้าหมาย"
                                            class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> ยังไม่ได้กำหนดค่าเป้าหมาย
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-400">{{ $ind->subStrategy?->strategy?->name }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $ind->level_label }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $ind->period_type === 'quarterly' ? 'รายไตรมาส' : 'รายปี' }}</td>
                            <td class="px-5 py-3 text-center {{ $defined < $total ? 'font-medium text-amber-600' : 'text-slate-600' }}">{{ $defined }}/{{ $total }} ช่วง</td>
                            <td class="px-5 py-3 text-right">
                                @if ($user->canManageIndicatorData('kpi.target', 'edit', $ind->level, $ind->year))
                                    <x-btn :href="route('targets.edit', $ind)" variant="ghost"><x-icon name="target" class="w-4 h-4" /> กำหนดเป้าหมาย</x-btn>
                                @else
                                    <span class="text-xs text-slate-400">ดูอย่างเดียว</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">ยังไม่มีตัวชี้วัด</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $indicators->links() }}</div>
</x-layouts.app>
