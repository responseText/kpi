@php $user = auth()->user(); @endphp

<x-layouts.app title="ยุทธศาสตร์" header="ยุทธศาสตร์">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-slate-600">ปี</label>
            <select name="year" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">ทั้งหมด</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                @endforeach
            </select>
        </form>

        @if ($user->canMenu('kpi.strategy', 'create'))
            <x-btn :href="route('strategies.create')"><x-icon name="strategy" class="w-4 h-4" /> เพิ่มยุทธศาสตร์</x-btn>
        @endif
    </div>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ปี</th>
                        <th class="px-5 py-3">รหัส</th>
                        <th class="px-5 py-3">ชื่อยุทธศาสตร์</th>
                        <th class="px-5 py-3 text-center">กลยุทธ์</th>
                        <th class="px-5 py-3 text-center">สถานะ</th>
                        <th class="px-5 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($strategies as $s)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-700">{{ $s->year }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $s->code ?: '-' }}</td>
                            <td class="px-5 py-3 text-slate-800">{{ $s->name }}</td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $s->sub_strategies_count }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $s->status === 'enable' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500' }}">
                                    {{ $s->status === 'enable' ? 'เปิด' : 'ปิด' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($user->canMenu('kpi.strategy', 'edit'))
                                        <x-btn :href="route('strategies.edit', $s)" variant="ghost">แก้ไข</x-btn>
                                    @endif
                                    @if ($user->canMenu('kpi.strategy', 'delete'))
                                        <form method="POST" action="{{ route('strategies.destroy', $s) }}" onsubmit="return confirm('ยืนยันลบยุทธศาสตร์นี้?')">
                                            @csrf @method('DELETE')
                                            <x-btn type="submit" variant="ghost" class="!text-red-600 hover:!bg-red-50">ลบ</x-btn>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">ยังไม่มีข้อมูลยุทธศาสตร์</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $strategies->withQueryString()->links() }}</div>
</x-layouts.app>
