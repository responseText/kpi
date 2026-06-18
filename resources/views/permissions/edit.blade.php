<x-layouts.app title="กำหนดสิทธิ์" header="กำหนดสิทธิ์ผู้ใช้">
    <div class="max-w-3xl">
        <x-card :title="$user->display_name" :subtitle="'ชื่อผู้ใช้: ' . $user->name">

            @if ($user->is_super_admin)
                {{-- ผู้ดูแลระบบสูงสุด: ล็อก ไม่มีผู้ใดปรับสิทธิ์ได้ --}}
                <div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-5">
                    <div class="flex items-center gap-2 text-amber-800">
                        <x-icon name="star" class="w-5 h-5" />
                        <span class="font-semibold">ผู้ดูแลระบบสูงสุด</span>
                    </div>
                    <p class="mt-2 text-sm text-amber-700">
                        ผู้ใช้นี้มีสิทธิ์ทุกอย่างในระบบ และไม่สามารถปรับสิทธิ์ได้จากหน้านี้
                        (กำหนดได้เฉพาะระดับฐานข้อมูล/ผู้ติดตั้งระบบเท่านั้น)
                    </p>
                </div>
                <div class="mt-6">
                    <x-btn :href="route('permissions.index')" variant="secondary">กลับ</x-btn>
                </div>
            @else
                @php $selectedLevels = collect(old('kpi_level_ids', $user->kpiLevelIds()))->map(fn ($v) => (int) $v); @endphp
                <form method="POST" action="{{ route('permissions.update', $user) }}">
                    @csrf
                    @method('PUT')

                    {{-- บทบาท/ระดับสิทธิ์ (เลือกได้หลายบทบาท) --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-2">บทบาทในระบบ KPI (เลือกได้มากกว่า 1)</label>
                        <div class="space-y-2">
                            @foreach ($levels as $level)
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                                    <input type="checkbox" name="kpi_level_ids[]" value="{{ $level->id }}"
                                        @checked($selectedLevels->contains($level->id))
                                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>
                                        <span class="block text-sm font-medium text-slate-800">{{ $level->name }}</span>
                                        @if ($level->description)
                                            <span class="block text-xs text-slate-500">{{ $level->description }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-slate-400">
                            บทบาทควบคุมขอบเขตการจัดการข้อมูล (เช่น การบันทึกผล) ส่วนสิทธิ์รายเมนูด้านล่างควบคุมการเข้าถึงเมนู — หากไม่เลือกบทบาทใด จะใช้เฉพาะสิทธิ์รายเมนู
                        </p>
                    </div>

                    {{-- สิทธิ์รายเมนู --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">เมนู</th>
                                    <th class="px-4 py-3 text-center">ดู</th>
                                    <th class="px-4 py-3 text-center">เพิ่ม</th>
                                    <th class="px-4 py-3 text-center">แก้ไข</th>
                                    <th class="px-4 py-3 text-center">ลบ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($menus as $menu)
                                    @php $perm = $current->get($menu->id); @endphp
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-700">{{ $menu->name }}</td>
                                        @foreach (['can_view' => 'view', 'can_create' => 'create', 'can_edit' => 'edit', 'can_delete' => 'delete'] as $field => $a)
                                            <td class="px-4 py-3 text-center">
                                                <input type="checkbox" name="permissions[{{ $menu->id }}][{{ $field }}]" value="1"
                                                    @checked($perm?->{$field})
                                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex items-center gap-2">
                        <x-btn type="submit" variant="primary">บันทึกสิทธิ์</x-btn>
                        <x-btn :href="route('permissions.index')" variant="secondary">ยกเลิก</x-btn>
                    </div>
                </form>
            @endif
        </x-card>
    </div>
</x-layouts.app>
