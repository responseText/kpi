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
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ $ind->name }}</div>
                                <div class="text-xs text-slate-400">{{ $ind->subStrategy?->strategy?->name }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $ind->level_label }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $done }}/{{ $total }} ช่วง</td>
                            <td class="px-5 py-3 text-right">
                                @if (auth()->user()->canRecordResultFor($ind))
                                    <x-btn :href="route('results.edit', $ind)" variant="ghost"><x-icon name="result" class="w-4 h-4" /> บันทึกผล</x-btn>
                                @else
                                    <span class="text-xs text-slate-400">ไม่มีสิทธิ์บันทึก</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">ยังไม่มีตัวชี้วัด</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $indicators->links() }}</div>
</x-layouts.app>
