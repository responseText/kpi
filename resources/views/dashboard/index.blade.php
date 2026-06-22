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

    // ----- กราฟเส้นพื้นหลัง hero: แนวโน้มอัตราผ่านรวมย้อนหลังรายปี -----
    $trend = array_values($passTrend ?? []);
    if (count($trend) === 1) {
        $trend = [$trend[0], $trend[0]]; // มีปีเดียว → ลากเป็นเส้นแบน
    }
    $heroLinePath = $heroAreaPath = '';
    if (count($trend) >= 2) {
        $vw = 1200; $vh = 300; $padY = 40; // viewBox + เว้นขอบบน/ล่าง
        $n = count($trend);
        $pts = [];
        foreach ($trend as $i => $d) {
            $pct = max(0, min(100, (int) ($d['pct'] ?? 0)));
            $pts[] = [
                round($i / ($n - 1) * $vw, 1),
                round($vh - $padY - $pct / 100 * ($vh - 2 * $padY), 1),
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
        $heroLinePath = $path;
        $heroAreaPath = $path . " L {$vw} {$vh} L 0 {$vh} Z";
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
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-600 to-violet-700 p-6 text-white shadow-lg sm:p-8">
        <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-10 h-56 w-56 rounded-full bg-fuchsia-400/20 blur-3xl"></div>

        {{-- กราฟเส้นพื้นหลัง: แนวโน้มอัตราผ่านรวมย้อนหลังรายปี --}}
        @if ($heroLinePath)
            <svg class="pointer-events-none absolute inset-0 h-full w-full" viewBox="0 0 1200 300"
                 preserveAspectRatio="none" fill="none" aria-hidden="true">
                <defs>
                    <linearGradient id="heroTrendFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0.20" />
                        <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <path d="{{ $heroAreaPath }}" fill="url(#heroTrendFill)" />
                <path d="{{ $heroLinePath }}" stroke="#ffffff" stroke-opacity="0.45"
                      stroke-width="2.5" stroke-linecap="round" vector-effect="non-scaling-stroke" />
            </svg>
        @endif

        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-indigo-100">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 backdrop-blur">
                        <x-icon name="dashboard" class="h-5 w-5" />
                    </span>
                    <span class="text-sm font-medium">แดชบอร์ดตัวชี้วัด · {{ $levelName }}</span>
                </div>
                <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">ภาพรวมผลการดำเนินงาน ปี {{ $year }}</h2>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-sm font-medium backdrop-blur">
                        <x-icon name="indicator" class="h-4 w-4" /> {{ $totAll }} ตัวชี้วัด
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-sm font-medium backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-emerald-300"></span> ผ่าน {{ $totPass }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-sm font-medium backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-rose-300"></span> ไม่ผ่าน {{ $totFail }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-sm font-medium backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-slate-200"></span> รอ {{ $totPending }}
                    </span>
                </div>
            </div>

            {{-- อัตราผ่านรวม + ตัวกรองปี --}}
            <div class="flex shrink-0 items-center gap-6">
                <div class="text-center">
                    <div class="text-5xl font-extrabold leading-none">{{ $passPct }}<span class="align-top text-2xl">%</span></div>
                    <div class="mt-1.5 text-xs font-medium text-indigo-100">อัตราผ่านรวม</div>
                    <div class="mx-auto mt-2 h-1.5 w-28 overflow-hidden rounded-full bg-white/20">
                        <div class="h-full rounded-full bg-white transition-all" style="width: {{ $passPct }}%"></div>
                    </div>
                </div>
                <form method="GET">
                    <label class="mb-1 block text-xs font-medium text-indigo-100">ปีงบประมาณ</label>
                    <select name="year" onchange="this.form.submit()"
                        class="rounded-xl border-0 bg-white/95 px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm focus:ring-2 focus:ring-white/70">
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
        <div class="relative mt-6 flex flex-wrap gap-1.5">
            @foreach ($tabs as $t)
                <a href="{{ route($t['route'], ['year' => $year]) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-sm font-medium transition
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

    {{-- กราฟแจกแจงผ่าน/ไม่ผ่าน/รอ รายยุทธศาสตร์และกลยุทธ์ (stacked bar แนวนอน) --}}
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

    {{-- ===================== แจกแจงตามยุทธศาสตร์/กลยุทธ์ รายระดับ ===================== --}}
    @foreach ($summary as $lvlKey => $s)
        @php
            $m = $levelMeta[$lvlKey] ?? ['icon' => 'level', 'grad' => 'from-slate-500 to-slate-700'];
            $strategies = $breakdown[$lvlKey]['strategies'] ?? [];
            $subStrategies = $breakdown[$lvlKey]['subStrategies'] ?? [];
        @endphp
        <div class="mt-8">
            <div class="mb-3 flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br {{ $m['grad'] }} text-white shadow"><x-icon :name="$m['icon']" class="h-5 w-5" /></span>
                <div>
                    <h3 class="font-semibold text-slate-800">แจกแจงตามยุทธศาสตร์/กลยุทธ์</h3>
                    <p class="text-xs text-slate-400">ระดับ{{ KpiIndicator::LEVELS[$lvlKey] ?? $lvlKey }} · ปี {{ $year }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                {{-- ยุทธศาสตร์ --}}
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-icon name="strategy" class="h-4 w-4 text-indigo-500" />
                            <h4 class="font-semibold text-slate-800">ยุทธศาสตร์</h4>
                        </div>
                        <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-sm font-semibold text-indigo-700">{{ count($strategies) }}</span>
                    </div>
                    @if (count($strategies) > 0)
                        <div class="mb-4" style="height: {{ max(120, count($strategies) * 42) }}px;">
                            <canvas class="kpi-breakdown-chart"
                                data-labels="{{ json_encode(array_values(array_map(fn ($r) => $r['name'], $strategies)), JSON_UNESCAPED_UNICODE) }}"
                                data-pass="{{ json_encode(array_values(array_map(fn ($r) => $r['pass'], $strategies))) }}"
                                data-fail="{{ json_encode(array_values(array_map(fn ($r) => $r['fail'], $strategies))) }}"
                                data-pending="{{ json_encode(array_values(array_map(fn ($r) => $r['pending'], $strategies))) }}"></canvas>
                        </div>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="py-2 pr-3">ยุทธศาสตร์</th>
                                    <th class="px-2 py-2 text-center">ผ่าน</th>
                                    <th class="px-2 py-2 text-center">ไม่ผ่าน</th>
                                    <th class="px-2 py-2 text-center">รอ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($strategies as $st)
                                    <tr class="transition hover:bg-slate-50/70">
                                        <td class="py-2.5 pr-3">
                                            <div class="font-medium text-slate-800">{{ $st['name'] }}</div>
                                            @if (!empty($st['code']))<div class="text-xs text-slate-400">{{ $st['code'] }}</div>@endif
                                        </td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-emerald-600">{{ $st['pass'] }}</span></td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-red-600">{{ $st['fail'] }}</span></td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-slate-400">{{ $st['pending'] }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-6 text-center text-slate-400">ยังไม่มีข้อมูล</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- กลยุทธ์ --}}
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-icon name="sub_strategy" class="h-4 w-4 text-violet-500" />
                            <h4 class="font-semibold text-slate-800">กลยุทธ์</h4>
                        </div>
                        <span class="rounded-full bg-violet-50 px-2.5 py-0.5 text-sm font-semibold text-violet-700">{{ count($subStrategies) }}</span>
                    </div>
                    @if (count($subStrategies) > 0)
                        <div class="mb-4" style="height: {{ max(120, count($subStrategies) * 42) }}px;">
                            <canvas class="kpi-breakdown-chart"
                                data-labels="{{ json_encode(array_values(array_map(fn ($r) => $r['name'], $subStrategies)), JSON_UNESCAPED_UNICODE) }}"
                                data-pass="{{ json_encode(array_values(array_map(fn ($r) => $r['pass'], $subStrategies))) }}"
                                data-fail="{{ json_encode(array_values(array_map(fn ($r) => $r['fail'], $subStrategies))) }}"
                                data-pending="{{ json_encode(array_values(array_map(fn ($r) => $r['pending'], $subStrategies))) }}"></canvas>
                        </div>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="py-2 pr-3">กลยุทธ์</th>
                                    <th class="px-2 py-2 text-center">ผ่าน</th>
                                    <th class="px-2 py-2 text-center">ไม่ผ่าน</th>
                                    <th class="px-2 py-2 text-center">รอ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($subStrategies as $sub)
                                    <tr class="transition hover:bg-slate-50/70">
                                        <td class="py-2.5 pr-3">
                                            <div class="font-medium text-slate-800">{{ $sub['name'] }}</div>
                                            @if (!empty($sub['strategy']))<div class="text-xs text-slate-400">{{ $sub['strategy'] }}</div>@endif
                                        </td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-emerald-600">{{ $sub['pass'] }}</span></td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-red-600">{{ $sub['fail'] }}</span></td>
                                        <td class="px-2 py-2.5 text-center"><span class="font-semibold text-slate-400">{{ $sub['pending'] }}</span></td>
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
                        <th class="px-5 py-3">ยุทธศาสตร์</th>
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
                            <td class="px-5 py-3.5 text-slate-600">{{ $ind->subStrategy?->strategy?->name ?? '-' }}</td>
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
