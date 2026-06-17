<x-layouts.app title="กำหนดสิทธิ์" header="กำหนดสิทธิ์ผู้ใช้">
    <div class="max-w-3xl" x-data="{ superAdmin: {{ $user->is_super_admin ? 'true' : 'false' }} }">
        <x-card :title="$user->display_name" :subtitle="'ชื่อผู้ใช้: ' . $user->name">
            <form method="POST" action="{{ route('permissions.update', $user) }}">
                @csrf
                @method('PUT')

                {{-- Super Admin Toggle --}}
                <div class="mb-6 rounded-xl border-2 p-4 transition-colors"
                     :class="superAdmin ? 'border-amber-400 bg-amber-50' : 'border-slate-200 bg-slate-50'">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="hidden" name="is_super_admin" value="0">
                        <input type="checkbox" name="is_super_admin" value="1"
                               x-model="superAdmin"
                               @change="superAdmin = $event.target.checked"
                               class="h-5 w-5 rounded border-slate-300 text-amber-500 focus:ring-amber-400">
                        <div>
                            <span class="font-semibold text-slate-800" :class="superAdmin ? 'text-amber-800' : ''">
                                ผู้ดูแลระบบสูงสุด (Super Admin)
                            </span>
                            <p class="text-xs text-slate-500 mt-0.5">
                                เมื่อเปิดใช้งาน ผู้ใช้นี้จะมีสิทธิ์เข้าถึงทุกเมนูและทุกการกระทำโดยอัตโนมัติ ไม่ต้องตั้งสิทธิ์รายเมนู
                            </p>
                        </div>
                    </label>
                </div>

                {{-- Menu Permissions (disabled when super admin) --}}
                <div :class="superAdmin ? 'opacity-40 pointer-events-none select-none' : ''">
                    <p class="mb-3 text-sm font-medium text-slate-700" x-show="superAdmin">
                        (สิทธิ์รายเมนูถูกแทนที่ด้วยสิทธิ์ผู้ดูแลระบบสูงสุด)
                    </p>

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
                                                    @checked($perm?->{$field} || $user->is_super_admin)
                                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-2">
                    <x-btn type="submit" variant="primary">บันทึกสิทธิ์</x-btn>
                    <x-btn :href="route('permissions.index')" variant="secondary">ยกเลิก</x-btn>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
