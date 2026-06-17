<x-layouts.app title="กำหนดสิทธิ์" header="กำหนดสิทธิ์ผู้ใช้">
    <div class="max-w-3xl">
        <x-card :title="$user->display_name" :subtitle="'ชื่อผู้ใช้: ' . $user->name">
            <form method="POST" action="{{ route('permissions.update', $user) }}">
                @csrf
                @method('PUT')

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
        </x-card>
    </div>
</x-layouts.app>
