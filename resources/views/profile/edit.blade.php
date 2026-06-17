<x-layouts.app title="ข้อมูลส่วนตัว" header="ข้อมูลส่วนตัว">
    <div class="max-w-3xl space-y-6">

        {{-- ข้อมูลส่วนบุคคล --}}
        <x-card title="ข้อมูลส่วนบุคคล" subtitle="ข้อมูลบางส่วนมาจากระบบบุคลากร (แก้ไขไม่ได้)">
            <dl class="mb-5 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">ชื่อผู้ใช้ (สำหรับเข้าระบบ)</dt>
                    <dd class="font-medium text-slate-800">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">ชื่อ-สกุล</dt>
                    <dd class="font-medium text-slate-800">{{ $user->display_name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">ตำแหน่ง</dt>
                    <dd class="font-medium text-slate-800">{{ $user->employee?->position?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">หน่วยงาน</dt>
                    <dd class="font-medium text-slate-800">{{ $user->employee?->division?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">บทบาทในระบบ KPI</dt>
                    <dd class="font-medium text-slate-800">
                        @if ($user->is_super_admin)
                            ผู้ดูแลระบบสูงสุด
                        @else
                            {{ $user->kpiLevel?->name ?: 'ผู้ใช้งานทั่วไป' }}
                        @endif
                    </dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('profile.update') }}" class="border-t border-slate-100 pt-5">
                @csrf
                @method('PUT')
                <div class="max-w-md">
                    <x-form.input name="email" label="อีเมล" type="email" :value="$user->email"
                                  placeholder="เช่น name@example.com" help="ใช้สำหรับติดต่อ (ไม่บังคับ)" />
                </div>
                <div class="mt-5">
                    <x-btn type="submit" variant="primary">บันทึกข้อมูลส่วนตัว</x-btn>
                </div>
            </form>
        </x-card>

        {{-- เปลี่ยนรหัสผ่าน --}}
        <x-card title="เปลี่ยนรหัสผ่าน" subtitle="ต้องยืนยันรหัสผ่านปัจจุบันก่อนทุกครั้ง">
            <form method="POST" action="{{ route('profile.password') }}" class="max-w-md space-y-4">
                @csrf
                @method('PUT')
                <x-form.input name="current_password" label="รหัสผ่านปัจจุบัน" type="password" :required="true" autocomplete="current-password" />
                <x-form.input name="password" label="รหัสผ่านใหม่" type="password" :required="true" autocomplete="new-password" help="อย่างน้อย 8 ตัวอักษร" />
                <x-form.input name="password_confirmation" label="ยืนยันรหัสผ่านใหม่" type="password" :required="true" autocomplete="new-password" />
                <div class="pt-1">
                    <x-btn type="submit" variant="primary">เปลี่ยนรหัสผ่าน</x-btn>
                </div>
            </form>
        </x-card>

    </div>
</x-layouts.app>
