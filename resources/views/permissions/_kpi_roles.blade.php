{{--
    เลือกบทบาทในระบบ KPI (หลายบทบาท) + กำหนด "ปีที่รับผิดชอบ" ของบทบาทผู้ดูแลรายระดับ
    ต้องมีตัวแปร: $user (ผู้ใช้ที่กำลังแก้), $levels (บทบาทที่เลือกได้), $years (ปี พ.ศ. ให้เลือก)
--}}
@php
    $selectedLevels = collect(old('kpi_level_ids', $user->kpiLevelIds()))->map(fn ($v) => (int) $v);
    $yearMap = $user->kpiLevelYearMap();      // [level_id => [year.., null]]
    $oldYears = old('kpi_level_years');        // null = โหลดครั้งแรก (ยังไม่ submit)
@endphp

<div class="space-y-2">
    @foreach ($levels as $level)
        @php
            $isChecked = $selectedLevels->contains($level->id);
            $currentYears = collect($oldYears !== null ? ($oldYears[$level->id] ?? []) : ($yearMap[$level->id] ?? []));
            // ค่าที่เลือกใน dropdown: ปีตัวเลขแรกที่พบ ไม่งั้นถือเป็น "ทุกปี"
            $firstYear = $currentYears->first(fn ($v) => is_numeric($v));
            $selectedYear = $firstYear !== null ? (string) $firstYear : 'all';
        @endphp
        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
            <input type="checkbox" name="kpi_level_ids[]" value="{{ $level->id }}"
                @checked($isChecked)
                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span class="flex-1">
                <span class="block text-sm font-medium text-slate-800">{{ $level->name }}</span>
                @if ($level->description)
                    <span class="block text-xs text-slate-500">{{ $level->description }}</span>
                @endif

                @if ($level->isYearScoped())
                    {{-- ปีที่รับผิดชอบ — เลือกจาก dropdown; มีผลทั้งการดูและจัดการข้อมูลของระดับนี้ตามปีที่เลือก --}}
                    <span class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">ปีที่รับผิดชอบ:</span>
                        <select name="kpi_level_years[{{ $level->id }}][]" onclick="event.stopPropagation()"
                            class="rounded-lg border-slate-300 py-1 pr-8 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="all" @selected($selectedYear === 'all')>ทุกปี</option>
                            @foreach ($years as $y)
                                <option value="{{ $y }}" @selected($selectedYear === (string) $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </span>
                @endif
            </span>
        </label>
    @endforeach
</div>
<p class="mt-2 text-xs text-slate-400">
    บทบาทควบคุม "ขอบเขต" การเข้าถึง/จัดการข้อมูล (ระดับ + ปีที่รับผิดชอบ) ส่วนสิทธิ์รายเมนูควบคุมว่าทำ เพิ่ม/แก้ไข/ลบ ได้หรือไม่
    — บทบาทผู้ดูแลรายระดับถ้าไม่เลือกปี จะถือว่า "ทุกปี"
</p>
