<x-layouts.app title="จัดการผู้ใช้งาน" header="จัดการผู้ใช้งาน (ระบบ KPI)">

    {{-- ===== Filter Panel ===== --}}
    <div class="mb-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
            <x-icon name="search" class="h-4 w-4 text-slate-400" />
            <span class="ml-2 text-sm font-semibold text-slate-600">ค้นหาผู้ใช้</span>
            @if ($search)
                <span class="ml-2 inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-bold text-indigo-600">1</span>
            @endif
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 px-5 py-4">

            @php $active = !empty($search); @endphp
            <div class="flex flex-col gap-1 flex-1 min-w-56">
                <label class="text-xs font-medium {{ $active ? 'text-indigo-600' : 'text-slate-500' }}">ค้นหาผู้ใช้</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <x-icon name="user" class="h-4 w-4 {{ $active ? 'text-indigo-400' : 'text-slate-400' }}" />
                    </div>
                    <input name="search" value="{{ $search }}" placeholder="ชื่อผู้ใช้หรือชื่อ-สกุล"
                        class="w-full rounded-xl border py-2.5 pl-10 pr-3 text-sm transition focus:outline-none focus:ring-2 focus:ring-offset-0 {{ $active ? 'border-indigo-300 bg-indigo-50 text-indigo-800 placeholder:text-indigo-300 focus:border-indigo-400 focus:ring-indigo-200' : 'border-slate-200 bg-slate-50 text-slate-700 placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-300 focus:ring-indigo-100' }}">
                </div>
            </div>

            <div class="flex items-center gap-2 pb-0.5">
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                    <x-icon name="search" class="h-4 w-4" /> ค้นหา
                </button>
                @if ($search)
                    <a href="{{ route('users.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800">
                        <x-icon name="x_circle" class="h-4 w-4 text-slate-400" /> ล้างตัวกรอง
                    </a>
                @endif
            </div>

        </form>
    </div>

    {{-- ===== Table ===== --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
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
