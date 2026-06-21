@php
    // ข้อความมาตรฐานเมื่อผู้ใช้ยังไม่ได้รับสิทธิ์ใช้งานเมนู/ส่วนนี้ของระบบ
    $contact = 'กรุณาติดต่อ งานสารสนเทศทางการแพทย์ เพื่อกำหนดสิทธิ์ในการใช้งานระบบ';
@endphp

@auth
    <x-layouts.app title="ไม่มีสิทธิ์เข้าใช้งาน" header="ไม่มีสิทธิ์เข้าใช้งาน">
        <div class="mx-auto max-w-xl">
            <x-card>
                <div class="flex flex-col items-center gap-4 py-8 text-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <x-icon name="permission" class="h-8 w-8" />
                    </span>
                    <h2 class="text-lg font-semibold text-slate-800">คุณยังไม่มีสิทธิ์ใช้งานส่วนนี้</h2>
                    <p class="text-sm leading-relaxed text-slate-600">
                        กรุณาติดต่อ <span class="font-semibold text-slate-800">งานสารสนเทศทางการแพทย์</span>
                        เพื่อกำหนดสิทธิ์ในการใช้งานระบบ
                    </p>
                    <x-btn :href="route('dashboard')" variant="secondary">← กลับหน้าแดชบอร์ด</x-btn>
                </div>
            </x-card>
        </div>
    </x-layouts.app>
@endauth

@guest
    <!DOCTYPE html>
    <html lang="th" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ไม่มีสิทธิ์เข้าใช้งาน — {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex h-full items-center justify-center bg-slate-100 p-6 text-slate-800 antialiased">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200">
            <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <x-icon name="permission" class="h-8 w-8" />
            </span>
            <h1 class="mb-2 text-lg font-semibold text-slate-800">คุณยังไม่มีสิทธิ์ใช้งานส่วนนี้</h1>
            <p class="mb-6 text-sm leading-relaxed text-slate-600">{{ $contact }}</p>
            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-300 transition hover:bg-slate-50">เข้าสู่ระบบ</a>
        </div>
    </body>
    </html>
@endguest
