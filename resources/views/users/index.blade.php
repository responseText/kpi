<x-layouts.app title="จัดการผู้ใช้งาน" header="จัดการผู้ใช้งาน (ระบบ KPI)">
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
                        <th class="px-5 py-3 text-center">ระดับ/บทบาท</th>
                        <th class="px-5 py-3 text-center">สถานะ</th>
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
                                    <div class="flex flex-wrap items-center justify-center gap-1">
                                        @foreach ($u->kpiLevels() as $lvl)
                                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                                                {{ $lvl->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $u->status === 'enable' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500' }}">
                                    {{ $u->status === 'enable' ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($u->is_super_admin)
                                    <span class="text-xs text-slate-400">ล็อก</span>
                                @else
                                    <x-btn :href="route('users.edit', $u)" variant="ghost"><x-icon name="user" class="w-4 h-4" /> จัดการ</x-btn>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">ไม่พบผู้ใช้</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.app>
