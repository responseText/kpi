@php $user = auth()->user(); @endphp

<x-layouts.app title="หมวด KPI" header="หมวด KPI">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-slate-600">กลยุทธ์</label>
            <select name="sub_strategy_id" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">ทั้งหมด</option>
                @foreach ($subStrategyOptions as $opt)
                    <option value="{{ $opt->id }}" @selected($subStrategyId == $opt->id)>{{ $opt->name }}</option>
                @endforeach
            </select>
        </form>

        @if ($user->canManageIndicatorData('kpi.category', 'create'))
            <x-btn :href="route('categories.create')"><x-icon name="category" class="w-4 h-4" /> เพิ่มหมวด KPI</x-btn>
        @endif
    </div>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">กลยุทธ์</th>
                        <th class="px-5 py-3">ชื่อหมวด KPI</th>
                        <th class="px-5 py-3 text-center">KPI หลัก</th>
                        <th class="px-5 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $cat)
                        @php
                            $stLevel = $cat->subStrategy?->strategy?->level ?? '';
                            $stYear = $cat->subStrategy?->strategy?->year;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-600">
                                @if ($cat->subStrategy)
                                    {{ $cat->subStrategy->name }}
                                @else
                                    <span class="text-amber-500 text-xs">— ยังไม่ผูกกลยุทธ์ —</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ $cat->name }}</div>
                                @if ($cat->code)<div class="text-xs text-slate-400">{{ $cat->code }}</div>@endif
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $cat->mains_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($user->canManageIndicatorData('kpi.category', 'edit', $stLevel, $stYear))
                                        <x-btn :href="route('categories.edit', $cat)" variant="ghost">แก้ไข</x-btn>
                                    @endif
                                    @if ($user->canManageIndicatorData('kpi.category', 'delete', $stLevel, $stYear))
                                        <form method="POST" action="{{ route('categories.destroy', $cat) }}" onsubmit="return confirm('ยืนยันลบหมวด KPI นี้?')">
                                            @csrf @method('DELETE')
                                            <x-btn type="submit" variant="ghost" class="!text-red-600 hover:!bg-red-50">ลบ</x-btn>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">ยังไม่มีข้อมูลหมวด KPI</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $categories->withQueryString()->links() }}</div>
</x-layouts.app>
