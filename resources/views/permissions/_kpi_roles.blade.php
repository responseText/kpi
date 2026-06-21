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
            $currentYears = $oldYears !== null ? ($oldYears[$level->id] ?? []) : ($yearMap[$level->id] ?? []);
            $allYears = collect($currentYears)->contains(fn ($v) => $v === null || $v === 'all');
            $selectedYearInts = collect($currentYears)->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (int) $v);
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
                    {{-- ปีที่รับผิดชอบ — มีผลทั้งการดูและจัดการข้อมูลของระดับนี้ตามปีที่เลือก --}}
                    <span class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5">
                        <span class="text-xs font-medium text-slate-500">ปีที่รับผิดชอบ:</span>
                        <label class="inline-flex items-center gap-1 text-xs text-slate-600">
                            <input type="checkbox" name="kpi_level_years[{{ $level->id }}][]" value="all"
                                @checked($allYears)
                                class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            ทุกปี
                        </label>
                        @foreach ($years as $y)
                            <label class="inline-flex items-center gap-1 text-xs text-slate-600">
                                <input type="checkbox" name="kpi_level_years[{{ $level->id }}][]" value="{{ $y }}"
                                    @checked($selectedYearInts->contains((int) $y))
                                    class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                {{ $y }}
                            </label>
                        @endforeach
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
