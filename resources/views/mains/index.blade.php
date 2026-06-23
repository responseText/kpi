@php $user = auth()->user(); @endphp

<x-layouts.app title="KPI หลัก" header="KPI หลัก">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-slate-600">หมวด KPI</label>
            <select name="category_id" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">ทั้งหมด</option>
                @foreach ($categoryOptions as $opt)
                    <option value="{{ $opt->id }}" @selected($categoryId == $opt->id)>{{ $opt->name }}</option>
                @endforeach
            </select>
        </form>

        @if ($user->canManageIndicatorData('kpi.main', 'create'))
            <x-btn :href="route('mains.create')"><x-icon name="main" class="w-4 h-4" /> เพิ่ม KPI หลัก</x-btn>
        @endif
    </div>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">หมวด KPI</th>
                        <th class="px-5 py-3">ชื่อ KPI หลัก</th>
                        <th class="px-5 py-3 text-center">ตัวชี้วัด</th>
                        <th class="px-5 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($mains as $m)
                        @php
                            $stLevel = $m->category?->subStrategy?->strategy?->level ?? '';
                            $stYear = $m->category?->subStrategy?->strategy?->year;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-600">{{ $m->category?->name }}</td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ $m->name }}</div>
                                @if ($m->code)<div class="text-xs text-slate-400">{{ $m->code }}</div>@endif
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $m->indicators_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($user->canManageIndicatorData('kpi.main', 'edit', $stLevel, $stYear))
                                        <x-btn :href="route('mains.edit', $m)" variant="ghost">แก้ไข</x-btn>
                                    @endif
                                    @if ($user->canManageIndicatorData('kpi.main', 'delete', $stLevel, $stYear))
                                        <form method="POST" action="{{ route('mains.destroy', $m) }}" onsubmit="return confirm('ยืนยันลบ KPI หลัก นี้?')">
                                            @csrf @method('DELETE')
                                            <x-btn type="submit" variant="ghost" class="!text-red-600 hover:!bg-red-50">ลบ</x-btn>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">ยังไม่มีข้อมูล KPI หลัก</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $mains->withQueryString()->links() }}</div>
</x-layouts.app>
