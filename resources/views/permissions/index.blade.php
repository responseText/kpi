<x-layouts.app title="สิทธิ์ผู้ใช้งาน" header="สิทธิ์ผู้ใช้งาน (ระบบ KPI)">
    <form method="GET" class="mb-5 flex items-end gap-2">
        <div>
            <label class="block text-xs text-slate-500">ค้นหาผู้ใช้</label>
            <input name="search" value="{{ $search }}" placeholder="ชื่อผู้ใช้" class="rounded-lg border-slate-300 text-sm shadow-sm">
        </div>
        <x-btn type="submit" variant="secondary">ค้นหา</x-btn>
    </form>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ผู้ใช้</th>
                        <th class="px-5 py-3">ชื่อ-สกุล</th>
                        <th class="px-5 py-3 text-center">ระดับสิทธิ์</th>
                        <th class="px-5 py-3 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $u)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-700">{{ $u->name }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $u->display_name }}</td>
                            <td class="px-5 py-3 text-center">
                                @if ($u->is_super_admin)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                        <x-icon name="star" class="w-3 h-3" />
                                        ผู้ดูแลระบบสูงสุด
                                    </span>
                                @elseif ($u->kpiLevels()->isNotEmpty())
                                    @php $roleGroups = $u->kpiLevelRows->filter(fn ($r) => $r->level)->groupBy('level_id'); @endphp
                                    <div class="flex flex-wrap items-center justify-center gap-1">
                                        @foreach ($roleGroups as $rows)
                                            @php
                                                $lvl = $rows->first()->level;
                                                $years = $rows->pluck('year');
                                                $yearLabel = $years->contains(null) ? 'ทุกปี' : $years->filter()->unique()->sort()->implode(', ');
                                            @endphp
                                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                                                {{ $lvl->name }}
                                                @if ($lvl->isYearScoped())<span class="ml-1 text-[10px] font-normal text-indigo-500">({{ $yearLabel }})</span>@endif
                                            </span>
                                        @endforeach
                                        @if ($u->menu_permissions_count)
                                            <span class="text-xs text-slate-400">· {{ $u->menu_permissions_count }} เมนู</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-500">{{ $u->menu_permissions_count }} เมนู</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <x-btn :href="route('permissions.edit', $u)" variant="ghost"><x-icon name="permission" class="w-4 h-4" /> กำหนดสิทธิ์</x-btn>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">ไม่พบผู้ใช้</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.app>
