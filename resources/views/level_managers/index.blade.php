@php use App\Models\KpiIndicator; use App\Models\KpiLevelManager; $user = auth()->user(); $grouped = $managers->groupBy('level'); @endphp

<x-layouts.app title="ผู้รับผิดชอบระดับ" header="ผู้รับผิดชอบ / ผู้กำหนดตัวชี้วัดแต่ละระดับ">
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        {{-- ฟอร์มเพิ่ม --}}
        @if ($user->canMenu('kpi.level_manager', 'create'))
            <div class="lg:col-span-1">
                <x-card title="เพิ่มผู้รับผิดชอบ">
                    <form method="POST" action="{{ route('level-managers.store') }}" class="space-y-4">
                        @csrf
                        <x-form.select name="level" label="ระดับ" :required="true">
                            @foreach ($levels as $k => $v)
                                <option value="{{ $k }}" @selected(old('level') === $k)>{{ $v }}</option>
                            @endforeach
                        </x-form.select>
                        <x-form.select name="role" label="บทบาท" :required="true">
                            @foreach ($roles as $k => $v)
                                <option value="{{ $k }}" @selected(old('role') === $k)>{{ $v }}</option>
                            @endforeach
                        </x-form.select>
                        <x-form.select name="user_id" label="ผู้ใช้" :required="true">
                            <option value="">— เลือกผู้ใช้ —</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->display_name }} ({{ $u->name }})</option>
                            @endforeach
                        </x-form.select>
                        <x-form.input name="year" label="ปี พ.ศ. (เว้นว่าง = ทุกปี)" type="number" :value="old('year')" />
                        <x-btn type="submit" variant="primary" class="w-full justify-center">เพิ่ม</x-btn>
                    </form>
                </x-card>
            </div>
        @endif

        {{-- รายการ --}}
        <div class="lg:col-span-2 space-y-5">
            {{-- ค้นหาผู้รับผิดชอบตามปี พ.ศ. --}}
            <x-card padding="p-4">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs text-slate-500">ปี พ.ศ.</label>
                        <select name="year" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm">
                            <option value="">ทุกปี</option>
                            @foreach ($years as $y)
                                <option value="{{ $y }}" @selected(($year ?? '') == $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-btn type="submit" variant="secondary">ค้นหา</x-btn>
                    @if ($year)
                        <span class="text-sm text-slate-500">
                            แสดงผู้รับผิดชอบของปี <span class="font-semibold text-slate-700">{{ $year }}</span>
                            <span class="text-xs text-slate-400">(รวมรายการที่ตั้งไว้ทุกปี)</span>
                        </span>
                        <x-btn :href="route('level-managers.index')" variant="ghost" class="ml-auto">ล้างตัวกรอง</x-btn>
                    @endif
                </form>
            </x-card>

            @foreach ($levels as $levelKey => $levelName)
                <x-card :title="$levelName">
                    @php $items = $grouped->get($levelKey, collect()); @endphp
                    @if ($items->isEmpty())
                        <p class="text-sm text-slate-400">ยังไม่มีผู้รับผิดชอบในระดับนี้</p>
                    @else
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($items as $m)
                                    <tr>
                                        <td class="py-2 text-slate-700">{{ $m->user?->display_name ?? '-' }}</td>
                                        <td class="py-2">
                                            <span class="rounded px-2 py-0.5 text-xs {{ $m->role === 'definer' ? 'bg-purple-100 text-purple-700' : 'bg-sky-100 text-sky-700' }}">{{ $m->role_label }}</span>
                                        </td>
                                        <td class="py-2 text-slate-500">{{ $m->year ?: 'ทุกปี' }}</td>
                                        <td class="py-2 text-right">
                                            @if ($user->canMenu('kpi.level_manager', 'delete'))
                                                <form method="POST" action="{{ route('level-managers.destroy', $m) }}" onsubmit="return confirm('ยืนยันลบ?')">
                                                    @csrf @method('DELETE')
                                                    <button class="text-xs text-red-600 hover:underline">ลบ</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </x-card>
            @endforeach
        </div>
    </div>
</x-layouts.app>
