@props(['title' => null, 'header' => null])

@php
    use App\Services\PermissionService;
    $navItems = auth()->check() ? app(PermissionService::class)->navigationFor(auth()->user()) : collect();
    $currentRoute = request()->route()?->getName();
@endphp

<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' — ' : '' }}{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">
<div x-data="{ sidebarOpen: false }" class="min-h-full lg:flex">

    {{-- Backdrop (mobile) --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-40 w-64 transform bg-slate-900 text-slate-200 transition-transform duration-200 lg:static lg:translate-x-0 thin-scroll overflow-y-auto"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex h-16 items-center gap-2 px-5 border-b border-white/10">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500 text-white">
                <x-icon name="indicator" class="w-5 h-5" />
            </span>
            <div class="leading-tight">
                <div class="text-sm font-bold text-white">ระบบตัวชี้วัด KPI</div>
                <div class="text-[11px] text-slate-400">รพ.ทองแสนขัน</div>
            </div>
        </div>

        <nav class="p-3 space-y-1">
            @foreach ($navItems as $item)
                @php $active = $currentRoute && $item->route && str($currentRoute)->startsWith(str($item->route)->before('.')); @endphp
                <a href="{{ $item->route && \Illuminate\Support\Facades\Route::has($item->route) ? route($item->route) : '#' }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                          {{ $active ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <x-icon :name="$item->icon ?? 'dot'" class="w-5 h-5 shrink-0" />
                    <span>{{ $item->name }}</span>
                </a>
            @endforeach
        </nav>
    </aside>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6">
            <button @click="sidebarOpen = !sidebarOpen" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                <x-icon name="menu" class="w-6 h-6" />
            </button>

            <div class="min-w-0 flex-1">
                <h1 class="truncate text-base font-semibold text-slate-800">{{ $header ?? $title }}</h1>
            </div>

            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-100">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                        {{ mb_substr(auth()->user()->display_name, 0, 1) }}
                    </span>
                    <span class="hidden text-sm font-medium text-slate-700 sm:block">{{ auth()->user()->display_name }}</span>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute right-0 mt-2 w-48 rounded-xl bg-white py-1 shadow-lg ring-1 ring-slate-200">
                    <div class="border-b border-slate-100 px-4 py-2 text-xs text-slate-500">
                        {{ auth()->user()->name }}
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                            <x-icon name="logout" class="w-4 h-4" /> ออกจากระบบ
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Flash --}}
        <main class="flex-1 p-4 sm:p-6">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">{{ session('error') }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

<style>[x-cloak]{display:none!important;}</style>
</body>
</html>
