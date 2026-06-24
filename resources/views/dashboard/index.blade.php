@php use App\Models\KpiIndicator; @endphp
@php
    $levelName = $level ? KpiIndicator::LEVELS[$level] : 'ทุกระดับ';

    // ข้อมูลแต่งหน้า (ไอคอน + ไล่สี) ของแต่ละระดับ — ใช้ร่วมทั้งการ์ดสรุปและส่วนแจกแจง
    $levelMeta = [
        'hospital' => ['icon' => 'hospital', 'grad' => 'from-indigo-500 to-blue-600'],
        'province' => ['icon' => 'province', 'grad' => 'from-violet-500 to-fuchsia-600'],
        'ministry' => ['icon' => 'ministry', 'grad' => 'from-sky-500 to-cyan-600'],
    ];

    // ยอดรวมทั้งหมด (ใช้ใน hero + กราฟภาพรวม)
    $totPass = array_sum(array_column($summary, 'pass'));
    $totFail = array_sum(array_column($summary, 'fail'));
    $totPending = array_sum(array_column($summary, 'pending'));
    $totAll = $totPass + $totFail + $totPending;
    $passPct = $totAll > 0 ? round($totPass / $totAll * 100) : 0;

    // ----- สปาร์กไลน์แนวโน้มอัตราผ่านรวมย้อนหลังรายปี (แสดงใต้ตัวเลขอัตราผ่านรวม) -----
    $trendRaw = array_values($passTrend ?? []);
    $trendCount = count($trendRaw);
    $trendFirstYear = $trendCount ? $trendRaw[0]['year'] : null;
    $trendLastYear = $trendCount ? $trendRaw[$trendCount - 1]['year'] : null;
    $trendLastPct = $trendCount ? $trendRaw[$trendCount - 1]['pct'] : null;

    $trend = $trendCount === 1 ? [$trendRaw[0], $trendRaw[0]] : $trendRaw; // ปีเดียว → ลากเป็นเส้นแบน
    $sparkLine = $sparkArea = '';
    $sparkDot = null;
    if (count($trend) >= 2) {
        $sw = 240; $sh = 64; $top = 10; $bottom = 52; $padX = 8; // viewBox เล็กสำหรับสปาร์กไลน์
        $n = count($trend);
        $pts = [];
        foreach (array_values($trend) as $i => $d) {
            $pct = max(0, min(100, (int) ($d['pct'] ?? 0)));
            $pts[] = [
                round($padX + $i / ($n - 1) * ($sw - 2 * $padX), 1),
                round($bottom - $pct / 100 * ($bottom - $top), 1),
            ];
        }
        // เชื่อมจุดเป็นเส้นโค้งลื่น (Catmull-Rom → Bézier)
        $path = 'M ' . $pts[0][0] . ' ' . $pts[0][1];
        for ($i = 0; $i < $n - 1; $i++) {
            [$p0, $p1, $p2, $p3] = [$pts[$i - 1] ?? $pts[$i], $pts[$i], $pts[$i + 1], $pts[$i + 2] ?? $pts[$i + 1]];
            $c1x = round($p1[0] + ($p2[0] - $p0[0]) / 6, 1);
            $c1y = round($p1[1] + ($p2[1] - $p0[1]) / 6, 1);
            $c2x = round($p2[0] - ($p3[0] - $p1[0]) / 6, 1);
            $c2y = round($p2[1] - ($p3[1] - $p1[1]) / 6, 1);
            $path .= " C {$c1x} {$c1y}, {$c2x} {$c2y}, {$p2[0]} {$p2[1]}";
        }
        $sparkLine = $path;
        $sparkArea = $path . " L {$pts[$n - 1][0]} {$sh} L {$pts[0][0]} {$sh} Z";
        $sparkDot = $pts[$n - 1];
    }

    $tabs = [
        ['label' => 'ทั้งหมด',   'route' => 'dashboard',          'icon' => 'dashboard', 'on' => $level === null],
        ['label' => 'กระทรวง',   'route' => 'dashboard.ministry', 'icon' => 'ministry',  'on' => $level === 'ministry'],
        ['label' => 'จังหวัด',    'route' => 'dashboard.province', 'icon' => 'province',  'on' => $level === 'province'],
        ['label' => 'โรงพยาบาล', 'route' => 'dashboard.hospital', 'icon' => 'hospital',  'on' => $level === 'hospital'],
    ];
@endphp

<x-layouts.app title="แดชบอร์ด" :header="'แดชบอร์ดตัวชี้วัด · ' . $levelName">
    {{-- ===================== HERO ===================== --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-600 to-violet-700 px-5 py-4 text-white shadow-lg sm:px-7 sm:py-5">
        <div class="pointer-events-none absolute -right-16 -top-24 h-56 w-56 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-10 h-48 w-48 rounded-full bg-fuchsia-400/20 blur-3xl"></div>

        <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-indigo-100">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/15 backdrop-blur">
                        <x-icon name="dashboard" class="h-4 w-4" />
                    </span>
                    <span class="text-xs font-medium sm:text-sm">แดชบอร์ดตัวชี้วัด · {{ $levelName }}</span>
                </div>
                <h2 class="mt-2 text-xl font-bold tracking-tight sm:text-2xl">ภาพรวมผลการดำเนินงาน ปี {{ $year }}</h2>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium backdrop-blur">
                        <x-icon name="indicator" class="h-3.5 w-3.5" /> {{ $totAll }} ตัวชี้วัด
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-emerald-300"></span> ผ่าน {{ $totPass }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-rose-300"></span> ไม่ผ่าน {{ $totFail }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-slate-200"></span> รอ {{ $totPending }}
                    </span>
                </div>
            </div>

            {{-- ขวา: แผงเมตริก อัตราผ่านรวม + กราฟแนวโน้ม + ตัวกรองปี (แนวนอนกระชับ) --}}
            <div class="flex w-full items-center gap-3 rounded-2xl bg-white/10 px-4 py-2.5 ring-1 ring-white/15 backdrop-blur sm:gap-5 lg:w-auto">
                {{-- อัตราผ่านรวม --}}
                <div class="text-center">
                    <div class="text-4xl font-extrabold leading-none sm:text-5xl">{{ $passPct }}<span class="align-top text-xl">%</span></div>
                    <div class="mt-1 text-[11px] font-medium text-indigo-100">อัตราผ่านรวม</div>
                </div>

                {{-- กราฟแนวโน้มอัตราผ่านรวมรายปี --}}
                @if ($sparkLine)
                    <div class="hidden border-l border-white/20 pl-4 sm:block">
                        <div class="mb-0.5 flex items-center justify-between gap-4 text-[10px] font-medium text-indigo-100">
                            <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-white"></span> แนวโน้มรายปี</span>
                            <span class="font-semibold text-white">{{ $trendLastPct }}%</span>
                        </div>
                        <svg viewBox="0 0 240 64" class="block h-auto w-32" fill="none" aria-hidden="true">
                            <defs>
                                <linearGradient id="sparkFill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#ffffff" stop-opacity="0.32" />
                                    <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <path d="{{ $sparkArea }}" fill="url(#sparkFill)" />
                            <path d="{{ $sparkLine }}" stroke="#ffffff" stroke-width="2.5"
                                  stroke-linecap="round" stroke-linejoin="round" />
                            @if ($sparkDot)
                                <circle cx="{{ $sparkDot[0] }}" cy="{{ $sparkDot[1] }}" r="9" fill="#ffffff" fill-opacity="0.25" />
                                <circle cx="{{ $sparkDot[0] }}" cy="{{ $sparkDot[1] }}" r="5" fill="#ffffff" />
                            @endif
                        </svg>
                        <div class="mt-0.5 flex items-center justify-between text-[10px] font-medium text-indigo-200">
                            <span>ปี {{ $trendFirstYear }}</span>
                            @if ($trendLastYear !== $trendFirstYear)<span>ปี {{ $trendLastYear }}</span>@endif
                        </div>
                    </div>
                @endif

                {{-- ตัวกรองปี --}}
                <form method="GET" class="ml-auto border-l border-white/20 pl-4">
                    <label class="mb-1 block text-[11px] font-medium text-indigo-100">ปีงบประมาณ</label>
                    <select name="year" onchange="this.form.submit()"
                        class="rounded-lg border-0 bg-white/95 px-2.5 py-1.5 text-sm font-semibold text-slate-700 shadow-sm focus:ring-2 focus:ring-white/70">
                        @forelse ($years as $y)
                            <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                        @empty
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforelse
                    </select>
                </form>
            </div>
        </div>

        {{-- แท็บเลือกระดับ --}}
        <div class="relative mt-4 flex flex-wrap gap-1.5">
            @foreach ($tabs as $t)
                <a href="{{ route($t['route'], ['year' => $year]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition
                          {{ $t['on'] ? 'bg-white text-indigo-700 shadow-sm' : 'bg-white/10 text-white hover:bg-white/20' }}">
                    <x-icon :name="$t['icon']" class="h-4 w-4" /> {{ $t['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- ===================== การ์ดสรุป ===================== --}}
    @if ($level)
        {{-- เลือกระดับเดียว: แสดงสถิติเด่น 4 ใบเต็มแถวให้ดูสมบูรณ์เหมือนหน้าทั้งหมด --}}
        @php
            $s = $summary[$level];
            $total = (int) $s['total'];
            $pass = (int) $s['pass']; $fail = (int) $s['fail']; $pending = (int) $s['pending'];
            $pct = fn ($n) => $total > 0 ? round($n / $total * 100) : 0;
            $statCards = [
                ['label' => 'ตัวชี้วัดทั้งหมด', 'value' => $total,   'icon' => 'indicator', 'grad' => 'from-indigo-500 to-blue-600',   'sub' => 'ระดับ' . $levelName],
                ['label' => 'ผ่านเกณฑ์',       'value' => $pass,    'icon' => 'result',    'grad' => 'from-emerald-500 to-green-600', 'sub' => $pct($pass) . '% ของทั้งหมด'],
                ['label' => 'ไม่ผ่านเกณฑ์',     'value' => $fail,    'icon' => 'x_circle',  'grad' => 'from-rose-500 to-red-600',      'sub' => $pct($fail) . '% ของทั้งหมด'],
                ['label' => 'รอบันทึกผล',      'value' => $pending, 'icon' => 'clock',     'grad' => 'from-slate-400 to-slate-600',   'sub' => $pct($pending) . '% ของทั้งหมด'],
            ];
        @endphp
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($statCards as $c)
                <div class="group relative overflow-hidden rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-gradient-to-br {{ $c['grad'] }} opacity-10 blur-2xl transition-opacity duration-300 group-hover:opacity-20"></div>
                    <div class="relative flex items-center justify-between">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br {{ $c['grad'] }} text-white shadow-lg ring-1 ring-white/40">
                            <x-icon :name="$c['icon']" class="h-6 w-6" />
                        </span>
                        <span class="text-xs font-medium text-slate-400">{{ $c['sub'] }}</span>
                    </div>
                    <div class="relative mt-4">
                        <div class="text-4xl font-extrabold tracking-tight text-slate-800">{{ $c['value'] }}</div>
                        <div class="mt-0.5 text-sm font-medium text-slate-500">{{ $c['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
    {{-- ทุกระดับ: การ์ดสรุปรายระดับ --}}
    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($summary as $key => $s)
            @php
                $m = $levelMeta[$key] ?? ['icon' => 'level', 'grad' => 'from-slate-500 to-slate-700'];
                $total = (int) $s['total'];
                $pass = (int) $s['pass']; $fail = (int) $s['fail']; $pending = (int) $s['pending'];
                $pct = fn ($n) => $total > 0 ? round($n / $total * 100) : 0;
            @endphp
            <div class="group relative overflow-hidden rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
                {{-- แสงตกแต่งมุมขวาบน --}}
                <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-gradient-to-br {{ $m['grad'] }} opacity-10 blur-2xl transition-opacity duration-300 group-hover:opacity-20"></div>

                <div class="relative flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br {{ $m['grad'] }} text-white shadow-lg ring-1 ring-white/40">
                            <x-icon :name="$m['icon']" class="h-6 w-6" />
                        </span>
                        <div>
                            <h3 class="font-semibold text-slate-800">{{ KpiIndicator::LEVELS[$key] ?? $key }}</h3>
                            <p class="text-xs text-slate-400">ผ่าน {{ $pct($pass) }}% ของทั้งหมด</p>
                        </div>
                    </div>
                    <div class="text-right leading-none">
                        <div class="text-3xl font-extrabold tracking-tight text-slate-800">{{ $total }}</div>
                        <div class="mt-1 text-[11px] font-medium text-slate-400">ตัวชี้วัด</div>
                    </div>
                </div>

                {{-- แถบสัดส่วน ผ่าน/ไม่ผ่าน/รอ --}}
                <div class="relative mt-5 flex h-2.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="bg-emerald-500" style="width: {{ $pct($pass) }}%"></div>
                    <div class="bg-red-500" style="width: {{ $pct($fail) }}%"></div>
                    <div class="bg-slate-300" style="width: {{ $pct($pending) }}%"></div>
                </div>

                {{-- กล่องสถิติ --}}
                <div class="relative mt-4 grid grid-cols-3 gap-2.5 text-center">
                    <div class="rounded-2xl bg-emerald-50 py-3 ring-1 ring-emerald-100">
                        <div class="text-xl font-bold text-emerald-600">{{ $pass }}</div>
                        <div class="text-[11px] font-medium text-emerald-700">ผ่าน</div>
                    </div>
                    <div class="rounded-2xl bg-red-50 py-3 ring-1 ring-red-100">
                        <div class="text-xl font-bold text-red-600">{{ $fail }}</div>
                        <div class="text-[11px] font-medium text-red-700">ไม่ผ่าน</div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 py-3 ring-1 ring-slate-200">
                        <div class="text-xl font-bold text-slate-500">{{ $pending }}</div>
                        <div class="text-[11px] font-medium text-slate-500">รอบันทึก</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ===================== กราฟภาพรวม ===================== --}}
    <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-3">
        {{-- โดนัทภาพรวมสถานะ --}}
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-1">
            <div class="mb-4 flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><x-icon name="result" class="h-5 w-5" /></span>
                <h3 class="font-semibold text-slate-800">ภาพรวมสถานะ</h3>
            </div>
            @if ($totAll > 0)
                <div class="relative mx-auto" style="max-width: 220px;">
                    <canvas id="overviewChart" data-pass="{{ $totPass }}" data-fail="{{ $totFail }}" data-pending="{{ $totPending }}"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <div class="text-3xl font-extrabold text-slate-800">{{ $passPct }}%</div>
                        <div class="text-[11px] font-medium text-slate-400">อัตราผ่าน</div>
                    </div>
                </div>
                <div class="mt-5 grid grid-cols-3 gap-2 text-center text-xs">
                    <div><div class="font-bold text-emerald-600">{{ $totPass }}</div><div class="text-slate-400">ผ่าน</div></div>
                    <div><div class="font-bold text-red-600">{{ $totFail }}</div><div class="text-slate-400">ไม่ผ่าน</div></div>
                    <div><div class="font-bold text-slate-500">{{ $totPending }}</div><div class="text-slate-400">รอบันทึก</div></div>
                </div>
            @else
                <div class="grid h-48 place-items-center text-sm text-slate-400">ยังไม่มีข้อมูลในปีนี้</div>
            @endif
        </div>

        {{-- กราฟแท่งสัดส่วนรายระดับ --}}
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <div class="mb-4 flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><x-icon name="strategy" class="h-5 w-5" /></span>
                <h3 class="font-semibold text-slate-800">สัดส่วนรายระดับ</h3>
            </div>
            @php $chartLabels = array_map(fn ($k) => KpiIndicator::LEVELS[$k] ?? $k, array_keys($summary)); @endphp
            <div style="height: 240px;">
                <canvas id="levelChart"
                    data-labels="{{ json_encode(array_values($chartLabels), JSON_UNESCAPED_UNICODE) }}"
                    data-pass="{{ json_encode(array_values(array_column($summary, 'pass'))) }}"
                    data-fail="{{ json_encode(array_values(array_column($summary, 'fail'))) }}"
                    data-pending="{{ json_encode(array_values(array_column($summary, 'pending'))) }}"></canvas>
            </div>
        </div>
    </div>

    @if ($totAll > 0)
    <script>
        window.addEventListener('load', () => {
            if (!window.Chart) return;
            const ov = document.getElementById('overviewChart');
            new Chart(ov, {
                type: 'doughnut',
                data: { labels: ['ผ่าน', 'ไม่ผ่าน', 'รอบันทึก'],
                    datasets: [{ data: [ov.dataset.pass, ov.dataset.fail, ov.dataset.pending],
                        backgroundColor: ['#10b981', '#ef4444', '#cbd5e1'], borderWidth: 0 }] },
                options: { plugins: { legend: { display: false } }, cutout: '72%' }
            });
            const lv = document.getElementById('levelChart');
            new Chart(lv, {
                type: 'bar',
                data: { labels: JSON.parse(lv.dataset.labels),
                    datasets: [
                        { label: 'ผ่าน', data: JSON.parse(lv.dataset.pass), backgroundColor: '#10b981', borderRadius: 6, maxBarThickness: 72 },
                        { label: 'ไม่ผ่าน', data: JSON.parse(lv.dataset.fail), backgroundColor: '#ef4444', borderRadius: 6, maxBarThickness: 72 },
                        { label: 'รอบันทึก', data: JSON.parse(lv.dataset.pending), backgroundColor: '#cbd5e1', borderRadius: 6, maxBarThickness: 72 },
                    ] },
                options: { responsive: true, maintainAspectRatio: false,
                    scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } },
                    plugins: { legend: { position: 'bottom' } } }
            });
        });
    </script>
    @endif

    {{-- กราฟแท่งจัดกลุ่ม: ตัวชี้วัดผ่าน/ไม่ผ่าน/รอ รายยุทธศาสตร์ (แท่งแยก ไม่ซ้อน) --}}
    <script>
        window.addEventListener('load', () => {
            if (!window.Chart) return;
            document.querySelectorAll('.kpi-strategy-chart').forEach((cv) => {
                new Chart(cv, {
                    type: 'bar',
                    data: { labels: JSON.parse(cv.dataset.labels),
                        datasets: [
                            { label: 'ผ่าน', data: JSON.parse(cv.dataset.pass), backgroundColor: '#10b981', borderRadius: 6, maxBarThickness: 46 },
                            { label: 'ไม่ผ่าน', data: JSON.parse(cv.dataset.fail), backgroundColor: '#ef4444', borderRadius: 6, maxBarThickness: 46 },
                            { label: 'รอบันทึก', data: JSON.parse(cv.dataset.pending), backgroundColor: '#cbd5e1', borderRadius: 6, maxBarThickness: 46 },
                        ] },
                    options: { responsive: true, maintainAspectRatio: false,
                        scales: {
                            x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 0, minRotation: 0,
                                callback: function (v) { const s = this.getLabelForValue(v); return s.length > 16 ? s.slice(0, 16) + '…' : s; } } },
                            y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'จำนวนตัวชี้วัด' } },
                        },
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: { callbacks: { title: (items) => items[0].label } },
                        } }
                });
            });
        });
    </script>

    {{-- กราฟแจกแจงผ่าน/ไม่ผ่าน/รอ รายหมวด KPI และ KPI หลัก (stacked bar แนวนอน) --}}
    <script>
        window.addEventListener('load', () => {
            if (!window.Chart) return;
            document.querySelectorAll('.kpi-breakdown-chart').forEach((cv) => {
                new Chart(cv, {
                    type: 'bar',
                    data: { labels: JSON.parse(cv.dataset.labels),
                        datasets: [
                            { label: 'ผ่าน', data: JSON.parse(cv.dataset.pass), backgroundColor: '#10b981', borderRadius: 5 },
                            { label: 'ไม่ผ่าน', data: JSON.parse(cv.dataset.fail), backgroundColor: '#ef4444', borderRadius: 5 },
                            { label: 'รอบันทึก', data: JSON.parse(cv.dataset.pending), backgroundColor: '#cbd5e1', borderRadius: 5 },
                        ] },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                        scales: { x: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { display: false } }, y: { stacked: true, grid: { display: false } } },
                        plugins: { legend: { position: 'bottom' } } }
                });
            });
        });
    </script>

    {{-- ===================== แจกแจงตามหมวด KPI / KPI หลัก รายระดับ ===================== --}}
    @foreach ($summary as $lvlKey => $s)
        @php
            $m = $levelMeta[$lvlKey] ?? ['icon' => 'level', 'grad' => 'from-slate-500 to-slate-700'];
            $strategies = $breakdown[$lvlKey]['strategies'] ?? [];
            $categories = $breakdown[$lvlKey]['categories'] ?? [];
            $mains = $breakdown[$lvlKey]['mains'] ?? [];
            $stratPass = array_sum(array_column($strategies, 'pass'));
            $stratFail = array_sum(array_column($strategies, 'fail'));
            $stratPending = array_sum(array_column($strategies, 'pending'));
        @endphp

        {{-- ===== กราฟแท่งผ่าน/ไม่ผ่าน รายยุทธศาสตร์ ===== --}}
        <div class="mt-8">
            <div class="mb-3 flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br {{ $m['grad'] }} text-white shadow"><x-icon name="strategy" class="h-5 w-5" /></span>
                <div>
                    <h3 class="font-semibold text-slate-800">ผลการประเมินตามยุทธศาสตร์</h3>
                    <p class="text-xs text-slate-400">ระดับ{{ KpiIndicator::LEVELS[$lvlKey] ?? $lvlKey }} · ปี {{ $year }}</p>
                </div>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <x-icon name="strategy" class="h-4 w-4 text-indigo-500" />
                        <h4 class="font-semibold text-slate-800">จำนวนตัวชี้วัดผ่าน / ไม่ผ่าน แยกตามยุทธศาสตร์</h4>
                        <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-sm font-semibold text-indigo-700">{{ count($strategies) }} ยุทธศาสตร์</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs font-medium">
                        <span class="inline-flex items-center gap-1.5 text-emerald-600"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span> ผ่าน {{ $stratPass }}</span>
                        <span class="inline-flex items-center gap-1.5 text-red-600"><span class="h-2.5 w-2.5 rounded-sm bg-red-500"></span> ไม่ผ่าน {{ $stratFail }}</span>
                        <span class="inline-flex items-center gap-1.5 text-slate-500"><span class="h-2.5 w-2.5 rounded-sm bg-slate-300"></span> รอ {{ $stratPending }}</span>
                    </div>
                </div>
                @if (count($strategies) > 0)
                    <div style="height: {{ max(260, count($strategies) * 70) }}px;">
                        <canvas class="kpi-strategy-chart"
                            data-labels="{{ json_encode(array_values(array_map(fn ($r) => ($r['code'] ? $r['code'].' ' : '').$r['name'], $strategies)), JSON_UNESCAPED_UNICODE) }}"
                            data-pass="{{ json_encode(array_values(array_map(fn ($r) => $r['pass'], $strategies))) }}"
                            data-fail="{{ json_encode(array_values(array_map(fn ($r) => $r['fail'], $strategies))) }}"
                            data-pending="{{ json_encode(array_values(array_map(fn ($r) => $r['pending'], $strategies))) }}"></canvas>
                    </div>
                    {{-- ตารางสรุปใต้กราฟ --}}
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="py-2 pr-3">ยุทธศาสตร์</th>
                                    <th class="px-2 py-2 text-center">ทั้งหมด</th>
                                    <th class="px-2 py-2 text-center">ผ่าน</th>
                                    <th class="px-2 py-2 text-center">ไม่ผ่าน</th>
                                    <th class="px-2 py-2 text-center">รอ</th>
                                    <th class="px-2 py-2 text-center">อัตราผ่าน</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($strategies as $st)
                                    @php $stPct = $st['total'] > 0 ? round($st['pass'] / $st['total'] * 100) : 0; @endphp
                                    <tr class="transition hover:bg-slate-50/70">
                                        <td class="py-2.5 pr-3">
                                            <div class="font-medium text-slate-800">{{ $st['name'] }}</div>
                                            @if (!empty($st['code']))<div class="text-xs text-slate-400">{{ $st['code'] }}</div>@endif
                                        </td>
                                        <td class="px-2 py-2.5 text-center font-semibold text-slate-600">{{ $st['total'] }}</td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-emerald-600">{{ $st['pass'] }}</span></td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-red-600">{{ $st['fail'] }}</span></td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-slate-400">{{ $st['pending'] }}</span></td>
                                        <td class="px-2 py-2.5 text-center">
                                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $stPct >= 80 ? 'bg-emerald-50 text-emerald-700' : ($stPct >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">{{ $stPct }}%</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="grid h-40 place-items-center text-sm text-slate-400">ยังไม่มีข้อมูลยุทธศาสตร์ในระดับนี้</div>
                @endif
            </div>
        </div>

        <div class="mt-8">
            <div class="mb-3 flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br {{ $m['grad'] }} text-white shadow"><x-icon :name="$m['icon']" class="h-5 w-5" /></span>
                <div>
                    <h3 class="font-semibold text-slate-800">แจกแจงตามหมวด KPI / KPI หลัก</h3>
                    <p class="text-xs text-slate-400">ระดับ{{ KpiIndicator::LEVELS[$lvlKey] ?? $lvlKey }} · ปี {{ $year }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-1">
                {{-- หมวด KPI --}}
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-icon name="category" class="h-4 w-4 text-indigo-500" />
                            <h4 class="font-semibold text-slate-800">หมวด KPI</h4>
                        </div>
                        <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-sm font-semibold text-indigo-700">{{ count($categories) }}</span>
                    </div>
                    @if (count($categories) > 0)
                        <div class="mb-4" style="height: {{ max(120, count($categories) * 42) }}px;">
                            <canvas class="kpi-breakdown-chart"
                                data-labels="{{ json_encode(array_values(array_map(fn ($r) => $r['name'], $categories)), JSON_UNESCAPED_UNICODE) }}"
                                data-pass="{{ json_encode(array_values(array_map(fn ($r) => $r['pass'], $categories))) }}"
                                data-fail="{{ json_encode(array_values(array_map(fn ($r) => $r['fail'], $categories))) }}"
                                data-pending="{{ json_encode(array_values(array_map(fn ($r) => $r['pending'], $categories))) }}"></canvas>
                        </div>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="py-2 pr-3">หมวด KPI</th>
                                    <th class="px-2 py-2 text-center">ผ่าน</th>
                                    <th class="px-2 py-2 text-center">ไม่ผ่าน</th>
                                    <th class="px-2 py-2 text-center">รอ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($categories as $cat)
                                    <tr class="transition hover:bg-slate-50/70">
                                        <td class="py-2.5 pr-3">
                                            <div class="font-medium text-slate-800">{{ $cat['name'] }}</div>
                                            @if (!empty($cat['code']))<div class="text-xs text-slate-400">{{ $cat['code'] }}</div>@endif
                                        </td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-emerald-600">{{ $cat['pass'] }}</span></td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-red-600">{{ $cat['fail'] }}</span></td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-slate-400">{{ $cat['pending'] }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-6 text-center text-slate-400">ยังไม่มีข้อมูล</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- KPI หลัก --}}
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-icon name="main" class="h-4 w-4 text-violet-500" />
                            <h4 class="font-semibold text-slate-800">KPI หลัก</h4>
                        </div>
                        <span class="rounded-full bg-violet-50 px-2.5 py-0.5 text-sm font-semibold text-violet-700">{{ count($mains) }}</span>
                    </div>
                    @if (count($mains) > 0)
                        <div class="mb-4" style="height: {{ max(120, count($mains) * 42) }}px;">
                            <canvas class="kpi-breakdown-chart"
                                data-labels="{{ json_encode(array_values(array_map(fn ($r) => $r['name'], $mains)), JSON_UNESCAPED_UNICODE) }}"
                                data-pass="{{ json_encode(array_values(array_map(fn ($r) => $r['pass'], $mains))) }}"
                                data-fail="{{ json_encode(array_values(array_map(fn ($r) => $r['fail'], $mains))) }}"
                                data-pending="{{ json_encode(array_values(array_map(fn ($r) => $r['pending'], $mains))) }}"></canvas>
                        </div>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="py-2 pr-3">KPI หลัก</th>
                                    <th class="px-2 py-2 text-center">ผ่าน</th>
                                    <th class="px-2 py-2 text-center">ไม่ผ่าน</th>
                                    <th class="px-2 py-2 text-center">รอ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($mains as $mn)
                                    <tr class="transition hover:bg-slate-50/70">
                                        <td class="py-2.5 pr-3">
                                            <div class="font-medium text-slate-800">{{ $mn['name'] }}</div>
                                            @if (!empty($mn['category']))<div class="text-xs text-slate-400">{{ $mn['category'] }}</div>@endif
                                        </td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-emerald-600">{{ $mn['pass'] }}</span></td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-red-600">{{ $mn['fail'] }}</span></td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-slate-400">{{ $mn['pending'] }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-6 text-center text-slate-400">ยังไม่มีข้อมูล</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- ===================== ตารางตัวชี้วัด ===================== --}}
    <div class="mt-8 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-6 py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><x-icon name="indicator" class="h-5 w-5" /></span>
                <div>
                    <h3 class="font-semibold text-slate-800">รายการตัวชี้วัด</h3>
                    <p class="text-xs text-slate-400">{{ $levelName }} · ปี {{ $year }}</p>
                </div>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600">{{ $indicators->count() }} รายการ</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/80 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">ตัวชี้วัด</th>
                        <th class="px-5 py-3">ระดับ</th>
                        <th class="px-5 py-3">หมวด KPI</th>
                        <th class="px-5 py-3">รูปแบบ</th>
                        <th class="px-5 py-3 text-center">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($indicators as $ind)
                        <tr class="transition hover:bg-indigo-50/40">
                            <td class="px-6 py-3.5">
                                <div class="font-medium text-slate-800">{{ $ind->name }}</div>
                                @if ($ind->code)<div class="text-xs text-slate-400">{{ $ind->code }}</div>@endif
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $ind->level_label }}</td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $ind->main?->category?->name ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $ind->year_type_label }} · {{ $ind->period_type_label }}</td>
                            <td class="px-5 py-3.5 text-center"><x-status-badge :status="$statuses[$ind->id] ?? 'pending'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">ยังไม่มีตัวชี้วัดในปีนี้</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
