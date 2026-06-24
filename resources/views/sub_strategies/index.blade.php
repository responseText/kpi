@php $user = auth()->user(); @endphp

<x-layouts.app title="กลยุทธ์" header="กลยุทธ์">

    {{-- ===== Filter Panel ===== --}}
    @php $hasFilters = $year || $level || $strategyId; @endphp
    <div class="mb-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
            <div class="flex items-center gap-2">
                <x-icon name="search" class="h-4 w-4 text-slate-400" />
                <span class="text-sm font-semibold text-slate-600">กรองและค้นหา</span>
                @if ($hasFilters)
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-bold text-indigo-600">
                        {{ ($year ? 1 : 0) + ($level ? 1 : 0) + ($strategyId ? 1 : 0) }}
                    </span>
                @endif
            </div>
            @if ($user->canManageIndicatorData('kpi.sub_strategy', 'create'))
                <x-btn :href="route('sub-strategies.create')"><x-icon name="sub_strategy" class="w-4 h-4" /> เพิ่มกลยุทธ์</x-btn>
            @endif
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 px-5 py-4">

            {{-- ยุทธศาสตร์ --}}
            @php $active = !empty($strategyId); @endphp
            <div class="flex flex-col gap-1 min-w-48 flex-1">
                <label class="flex items-center gap-1.5 text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">
                    <x-icon name="strategy" class="h-3.5 w-3.5" /> ยุทธศาสตร์
                </label>
                <select name="strategy_id" onchange="this.form.submit()"
                    class="w-full rounded-xl border py-2.5 pl-3 pr-8 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                    <option value="">ทั้งหมด</option>
                    @foreach ($strategyOptions as $s)
                        <option value="{{ $s->id }}" @selected($s->id == $strategyId)>
                            [{{ $s->year }}] {{ $s->name }}
                            @if($s->level) · {{ \App\Models\KpiStrategy::LEVELS[$s->level] ?? $s->level }} @endif
                        </option>
                    @endforeach
                </select>
            </div>

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
                    @foreach ($levelOptions as $code => $label)
                        <option value="{{ $code }}" @selected($code === $level)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if ($hasFilters)
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-transparent select-none">x</label>
                    <a href="{{ route('sub-strategies.index') }}"
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
                        <th class="px-5 py-3">ยุทธศาสตร์ (ปี)</th>
                        <th class="px-5 py-3">ชื่อกลยุทธ์</th>
                        <th class="px-5 py-3">ผู้ตรวจสอบ</th>
                        <th class="px-5 py-3 text-center">ตัวชี้วัด</th>
                        <th class="px-5 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($subStrategies as $ss)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-600">
                                <span class="text-slate-400">[{{ $ss->strategy?->year }}]</span> {{ $ss->strategy?->name }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ $ss->name }}</div>
                                @if ($ss->code)<div class="text-xs text-slate-400">{{ $ss->code }}</div>@endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                @forelse ($ss->reviewers as $r)
                                    <span class="mr-1 inline-block rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $r->display_name }}</span>
                                @empty
                                    <span class="text-xs text-red-400">ยังไม่มี</span>
                                @endforelse
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $ss->indicators_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($user->canManageIndicatorData('kpi.sub_strategy', 'edit', $ss->strategy?->level ?? '', $ss->strategy?->year))
                                        <x-btn :href="route('sub-strategies.edit', $ss)" variant="ghost">แก้ไข</x-btn>
                                    @endif
                                    @if ($user->canManageIndicatorData('kpi.sub_strategy', 'delete', $ss->strategy?->level ?? '', $ss->strategy?->year))
                                        <form method="POST" action="{{ route('sub-strategies.destroy', $ss) }}" onsubmit="return confirm('ยืนยันลบกลยุทธ์นี้?')">
                                            @csrf @method('DELETE')
                                            <x-btn type="submit" variant="ghost" class="!text-red-600 hover:!bg-red-50">ลบ</x-btn>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">ยังไม่มีข้อมูลกลยุทธ์</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $subStrategies->withQueryString()->links() }}</div>
</x-layouts.app>
