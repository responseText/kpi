<x-layouts.guest title="เข้าสู่ระบบ">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg">
                <x-icon name="indicator" class="w-8 h-8" />
            </div>
            <h1 class="text-xl font-bold text-slate-800">ระบบตัวชี้วัดผลงาน</h1>
            <p class="text-sm text-slate-500">โรงพยาบาลทองแสนขัน</p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
                @csrf

                @if ($errors->any())
                    <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">ชื่อผู้ใช้</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                        class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2.5 text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="username">
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-slate-700">รหัสผ่าน</label>
                    <input id="password" name="password" type="password" required
                        class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2.5 text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="••••••••">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    จดจำการเข้าสู่ระบบ
                </label>

                <button type="submit"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    เข้าสู่ระบบ
                </button>
            </form>
        </div>

        <p class="mt-4 text-center text-xs text-slate-400">© {{ now()->year + 543 }} สารสนเทศทางการแพทย์โรงพยาบาลทองแสนขัน</p>
    </div>
</x-layouts.guest>
