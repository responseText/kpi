<x-layouts.app title="นำเข้าข้อมูล (Excel)" header="นำเข้าข้อมูล (Excel)">
    @php
        $activeType = session('import_type');
        $res = session('import_result');
    @endphp

    {{-- คำอธิบายภาพรวม + ลำดับการนำเข้า --}}
    <x-card class="mb-5">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                <x-icon name="import" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
                <h2 class="font-semibold text-slate-800">นำเข้าข้อมูล KPI จากไฟล์ Excel รายปี</h2>
                <p class="mt-1 text-sm text-slate-600">
                    สำหรับ <span class="font-medium">ผู้ดูแลระบบสูงสุด</span> และ <span class="font-medium">ผู้ดูแลตัวชี้วัดทั้งหมด</span>
                    ดาวน์โหลดเทมเพลตของแต่ละประเภท กรอกข้อมูลในชีต “ข้อมูล” แล้วอัปโหลดกลับเข้าระบบ
                </p>
                <p class="mt-2 text-xs text-amber-700 bg-amber-50 ring-1 ring-amber-200 rounded-lg px-3 py-2">
                    แนะนำให้นำเข้าตามลำดับ เพราะข้อมูลชั้นล่างอ้างอิงชั้นบน:
                    <span class="font-medium">ยุทธศาสตร์ → กลยุทธ์ → หมวด KPI → KPI หลัก → ตัวชี้วัด → ค่าเป้าหมาย</span>
                    — หากมีข้อผิดพลาดแม้แถวเดียว ระบบจะไม่บันทึกทั้งไฟล์และแจ้งเลขแถวที่ต้องแก้ (นำเข้าไฟล์เดิมซ้ำได้ ระบบจะอัปเดตของเดิมไม่สร้างซ้ำ)
                </p>
            </div>
        </div>
    </x-card>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="space-y-5">
        @foreach ($types as $type)
            <div id="type-{{ $type->key() }}">
                <x-card padding="p-0">
                    <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-start lg:justify-between">
                        {{-- ซ้าย: ข้อมูลประเภท --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">
                                    {{ $type->order() }}
                                </span>
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                    <x-icon :name="$type->icon()" class="h-5 w-5" />
                                </span>
                                <h3 class="truncate text-base font-semibold text-slate-800">{{ $type->label() }}</h3>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">{{ $type->description() }}</p>

                            <details class="group mt-3">
                                <summary class="inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                    <x-icon name="search" class="h-3.5 w-3.5" /> ดูคอลัมน์และเงื่อนไขการกรอก
                                </summary>
                                <div class="mt-3 overflow-x-auto rounded-lg ring-1 ring-slate-200">
                                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                                        <thead class="bg-slate-50 text-left text-slate-500">
                                            <tr>
                                                <th class="px-3 py-2">คอลัมน์</th>
                                                <th class="px-3 py-2 text-center">บังคับ</th>
                                                <th class="px-3 py-2">คำอธิบาย</th>
                                                <th class="px-3 py-2">ค่าที่อนุญาต</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($type->columns() as $col)
                                                <tr>
                                                    <td class="px-3 py-2 font-medium text-slate-700 whitespace-nowrap">{{ $col->header }}</td>
                                                    <td class="px-3 py-2 text-center">
                                                        @if ($col->required)
                                                            <span class="font-bold text-red-600">✔</span>
                                                        @else
                                                            <span class="text-slate-300">–</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-slate-600">{{ $col->note }}</td>
                                                    <td class="px-3 py-2 text-slate-500">{{ $col->allowed }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if ($type->instructions())
                                        <ul class="list-disc space-y-1 bg-slate-50 px-6 py-3 text-xs text-slate-600">
                                            @foreach ($type->instructions() as $line)
                                                <li>{{ $line }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </details>
                        </div>

                        {{-- ขวา: ดาวน์โหลด + อัปโหลด --}}
                        <div class="flex shrink-0 flex-col gap-2 lg:w-72">
                            <x-btn :href="route('imports.template', $type->key())" variant="secondary">
                                <x-icon name="download" class="h-4 w-4" /> ดาวน์โหลดเทมเพลต
                            </x-btn>

                            <form method="POST" action="{{ route('imports.store', $type->key()) }}"
                                  enctype="multipart/form-data" class="flex flex-col gap-2 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                                @csrf
                                <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                                       class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-600 file:px-3 file:py-2 file:text-xs file:font-medium file:text-white hover:file:bg-indigo-700" />
                                <x-btn type="submit" variant="primary" class="justify-center">
                                    <x-icon name="upload" class="h-4 w-4" /> นำเข้า{{ $type->label() }}
                                </x-btn>
                            </form>
                        </div>
                    </div>

                    {{-- แผงผลลัพธ์ของประเภทที่เพิ่งนำเข้า --}}
                    @if ($activeType === $type->key() && $res)
                        @if ($res['success'])
                            <div class="border-t border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                                <div class="flex items-center gap-2 font-semibold">
                                    <x-icon name="result" class="h-5 w-5" /> นำเข้าสำเร็จ
                                </div>
                                <p class="mt-1">
                                    เพิ่มใหม่ <span class="font-bold">{{ $res['created'] }}</span> รายการ,
                                    อัปเดต <span class="font-bold">{{ $res['updated'] }}</span> รายการ
                                    (จากข้อมูล {{ $res['rowsRead'] }} แถว)
                                </p>
                            </div>
                        @else
                            <div class="border-t border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                                <div class="flex items-center gap-2 font-semibold">
                                    <x-icon name="x_circle" class="h-5 w-5" /> นำเข้าไม่สำเร็จ — ไม่มีการบันทึกข้อมูล (กรุณาแก้ไขแล้วนำเข้าใหม่)
                                </div>
                                @if (! empty($res['fatal']))
                                    <p class="mt-1">{{ $res['fatal'] }}</p>
                                @endif
                                @if (! empty($res['errors']))
                                    <div class="mt-3 max-h-72 overflow-y-auto rounded-lg ring-1 ring-red-200">
                                        <table class="min-w-full divide-y divide-red-100 bg-white text-xs">
                                            <thead class="bg-red-100/60 text-left text-red-700">
                                                <tr>
                                                    <th class="w-20 px-3 py-2">แถวที่</th>
                                                    <th class="px-3 py-2">ข้อผิดพลาด</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-red-50">
                                                @foreach ($res['errors'] as $err)
                                                    <tr>
                                                        <td class="px-3 py-2 font-medium text-red-700">{{ $err['row'] }}</td>
                                                        <td class="px-3 py-2 text-slate-700">{{ implode(' • ', $err['messages']) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif
                </x-card>
            </div>
        @endforeach
    </div>
</x-layouts.app>
