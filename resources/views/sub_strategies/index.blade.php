@php $user = auth()->user(); @endphp

<x-layouts.app title="กลยุทธ์" header="กลยุทธ์">
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

        @if ($user->canMenu('kpi.sub_strategy', 'create'))
            <x-btn :href="route('sub-strategies.create')"><x-icon name="sub_strategy" class="w-4 h-4" /> เพิ่มกลยุทธ์</x-btn>
        @endif
    </div>

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
                                    <span class="text-red-400 text-xs">ยังไม่มี</span>
                                @endforelse
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $ss->indicators_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($user->canMenu('kpi.sub_strategy', 'edit'))
                                        <x-btn :href="route('sub-strategies.edit', $ss)" variant="ghost">แก้ไข</x-btn>
                                    @endif
                                    @if ($user->canMenu('kpi.sub_strategy', 'delete'))
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
