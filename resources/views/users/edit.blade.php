<x-layouts.app title="จัดการผู้ใช้งาน" header="จัดการผู้ใช้งาน">
    <div class="max-w-3xl space-y-6">

        @if ($user->is_super_admin)
            {{-- ผู้ดูแลระบบสูงสุด: ล็อก ไม่มีผู้ใดจัดการบัญชีนี้ได้ --}}
            <x-card :title="$user->display_name" :subtitle="'ชื่อผู้ใช้: ' . $user->name">
                <div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-5">
                    <div class="flex items-center gap-2 text-amber-800">
                        <x-icon name="star" class="w-5 h-5" />
                        <span class="font-semibold">ผู้ดูแลระบบสูงสุด</span>
                    </div>
                    <p class="mt-2 text-sm text-amber-700">
                        ไม่สามารถเปลี่ยนรหัสผ่าน สถานะ หรือระดับของผู้ดูแลระบบสูงสุดจากหน้านี้ได้
                        (เปลี่ยนรหัสผ่านของตนเองได้ที่เมนู “ข้อมูลส่วนตัว”)
                    </p>
                </div>
                <div class="mt-6">
                    <x-btn :href="route('users.index')" variant="secondary">กลับ</x-btn>
                </div>
            </x-card>
        @else
            {{-- สถานะการใช้งาน + ระดับ/บทบาท --}}
            <x-card :title="$user->display_name" :subtitle="'ชื่อผู้ใช้: ' . $user->name">
                @php $selectedLevels = collect(old('kpi_level_ids', $user->kpiLevelIds()))->map(fn ($v) => (int) $v); @endphp
                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    {{-- สถานะการใช้งาน --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-2">สถานะการใช้งาน</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['enable' => 'เปิดใช้งาน', 'disable' => 'ปิดใช้งาน (ระงับการเข้าสู่ระบบ)'] as $val => $label)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 hover:bg-slate-50 has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50">
                                    <input type="radio" name="status" value="{{ $val }}"
                                        @checked(old('status', $user->status) === $val)
                                        class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- ระดับ/บทบาทในระบบ --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-2">ระดับ/บทบาทในระบบ KPI (เลือกได้มากกว่า 1)</label>
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
                            การจัดการสิทธิ์รายเมนูโดยละเอียด ทำได้ที่เมนู “สิทธิ์ผู้ใช้งาน”
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-btn type="submit" variant="primary">บันทึกสถานะ/ระดับ</x-btn>
                        <x-btn :href="route('users.index')" variant="secondary">ยกเลิก</x-btn>
                    </div>
                </form>
            </x-card>

            {{-- เปลี่ยนรหัสผ่าน --}}
            <x-card title="ตั้งรหัสผ่านใหม่" subtitle="ผู้ดูแลระบบสูงสุดสามารถรีเซ็ตรหัสผ่านให้ผู้ใช้นี้ได้โดยตรง">
                <form method="POST" action="{{ route('users.password', $user) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.input name="password" type="password" label="รหัสผ่านใหม่" :required="true" help="อย่างน้อย 8 ตัวอักษร" autocomplete="new-password" />
                        <x-form.input name="password_confirmation" type="password" label="ยืนยันรหัสผ่านใหม่" :required="true" autocomplete="new-password" />
                    </div>

                    <div>
                        <x-btn type="submit" variant="primary">เปลี่ยนรหัสผ่าน</x-btn>
                    </div>
                </form>
            </x-card>
        @endif
    </div>
</x-layouts.app>
