@php $user = auth()->user(); @endphp

<x-layouts.app title="ยุทธศาสตร์" header="ยุทธศาสตร์">

    {{-- ===== Filter Panel ===== --}}
    @php $hasFilters = $year || $level; @endphp
    <div class="mb-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
            <div class="flex items-center gap-2">
                <x-icon name="search" class="h-4 w-4 text-slate-400" />
                <span class="text-sm font-semibold text-slate-600">กรองและค้นหา</span>
                @if ($hasFilters)
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-bold text-indigo-600">
                        {{ ($year ? 1 : 0) + ($level ? 1 : 0) }}
                    </span>
                @endif
            </div>
            @if ($user->canManageIndicatorData('kpi.strategy', 'create'))
                <x-btn :href="route('strategies.create')"><x-icon name="strategy" class="w-4 h-4" /> เพิ่มยุทธศาสตร์</x-btn>
            @endif
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 px-5 py-4">

            {{-- ปี --}}
            @php $active = !empty($year); @endphp
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">ปี</label>
                <select name="year" onchange="this.form.submit()"
                    class="rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                    <option value="">ทุกปี</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            {{-- ระดับ --}}
            @php $active = !empty($level); @endphp
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">ระดับ</label>
                <select name="level" onchange="this.form.submit()"
                    class="rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                    <option value="">ทุกระดับ</option>
                    @foreach ($levels as $k => $v)
                        <option value="{{ $k }}" @selected(($level ?? '') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            @if ($hasFilters)
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-transparent select-none">x</label>
                    <a href="{{ route('strategies.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800">
                        <x-icon name="x_circle" class="h-4 w-4 text-slate-400" /> ล้างตัวกรอง
                    </a>
                </div>
            @endif

        </form>
    </div>

    {{-- ===== Table ===== --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ปี</th>
                        <th class="px-5 py-3">ระดับ</th>
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
                            <td class="px-5 py-3">
                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ $s->level_label }}</span>
                            </td>
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
                                    @if ($user->canManageIndicatorData('kpi.strategy', 'edit', $s->level, $s->year))
                                        <x-btn :href="route('strategies.edit', $s)" variant="ghost">แก้ไข</x-btn>
                                    @endif
                                    @if ($user->canManageIndicatorData('kpi.strategy', 'delete', $s->level, $s->year))
                                        <form method="POST" action="{{ route('strategies.destroy', $s) }}" onsubmit="return confirm('ยืนยันลบยุทธศาสตร์นี้?')">
                                            @csrf @method('DELETE')
                                            <x-btn type="submit" variant="ghost" class="!text-red-600 hover:!bg-red-50">ลบ</x-btn>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">ยังไม่มีข้อมูลยุทธศาสตร์</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $strategies->withQueryString()->links() }}</div>
</x-layouts.app>
