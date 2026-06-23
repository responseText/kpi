@php use App\Models\KpiIndicator; $user = auth()->user(); @endphp

<x-layouts.app title="ตัวชี้วัด" header="ตัวชี้วัด">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-2">

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">ระดับ</label>
                <select name="level" onchange="this.form.submit()" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs">
                    <option value="">ทุกระดับ</option>
                    @foreach (KpiIndicator::LEVELS as $k => $v)
                        <option value="{{ $k }}" @selected(($filters['level'] ?? '') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">ปี</label>
                <select name="year" onchange="this.form.submit()" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs">
                    <option value="">ทุกปี</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected(($filters['year'] ?? '') == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">ค้นหา</label>
                <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ชื่อ/รหัส" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs">
            </div>
            <x-btn type="submit" variant="secondary">ค้นหา</x-btn>
        </form>

        @if ($user->canManageIndicatorData('kpi.indicator', 'create'))
            <x-btn :href="route('indicators.create')"><x-icon name="indicator" class="w-4 h-4" /> เพิ่มตัวชี้วัด</x-btn>
        @endif
    </div>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ตัวชี้วัด</th>
                        <th class="px-5 py-3">ระดับ</th>
                        <th class="px-5 py-3">ปี/รูปแบบ</th>
                        <th class="px-5 py-3">ผู้รับผิดชอบ</th>
                        <th class="px-5 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($indicators as $ind)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <a href="{{ route('indicators.show', $ind) }}" class="font-medium text-indigo-700 hover:underline">{{ $ind->name }}</a>
                                @if ($ind->measurement_type)
                                    <span class="ml-1 inline-block rounded bg-indigo-50 px-1.5 py-0.5 text-[11px] text-indigo-600">{{ $ind->measurement_type_label }}</span>
                                @endif
                                <div class="text-xs text-slate-400">{{ $ind->main?->category?->name }} › {{ $ind->main?->name }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $ind->level_label }}</td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $ind->year_type === 'fiscal' ? 'งบ' : 'พ.ศ.' }} {{ $ind->year }}
                                <span class="text-slate-400">· {{ $ind->period_type === 'quarterly' ? 'ไตรมาส' : 'รายปี' }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                @foreach ($ind->owners->take(2) as $o)
                                    <span class="mr-1 inline-block rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $o->display_name }}</span>
                                @endforeach
                                @if ($ind->owners->count() > 2)<span class="text-xs text-slate-400">+{{ $ind->owners->count() - 2 }}</span>@endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <x-btn :href="route('indicators.show', $ind)" variant="ghost">ดู</x-btn>
                                    @if ($user->canManageIndicatorData('kpi.indicator', 'edit', $ind->level, $ind->year))
                                        <x-btn :href="route('indicators.edit', $ind)" variant="ghost">แก้ไข</x-btn>
                                    @endif
                                    @if ($user->canManageIndicatorData('kpi.indicator', 'delete', $ind->level, $ind->year))
                                        <form method="POST" action="{{ route('indicators.destroy', $ind) }}" onsubmit="return confirm('ยืนยันลบตัวชี้วัดนี้?')">
                                            @csrf @method('DELETE')
                                            <x-btn type="submit" variant="ghost" class="!text-red-600 hover:!bg-red-50">ลบ</x-btn>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">ยังไม่มีข้อมูลตัวชี้วัด</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $indicators->links() }}</div>
</x-layouts.app>
