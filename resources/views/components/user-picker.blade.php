@props(['name', 'users', 'selected' => [], 'label' => 'เลือกผู้ใช้', 'required' => false])

@php $selectedIds = collect($selected)->map(fn ($v) => (int) $v)->all(); @endphp

<div x-data="{ q: '' }">
    <label class="mb-1 block text-sm font-medium text-slate-700">
        {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
    </label>
    <input type="text" x-model="q" placeholder="ค้นหาชื่อผู้ใช้..."
        class="mb-2 w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    <div class="thin-scroll max-h-56 overflow-y-auto rounded-lg border border-slate-200 p-2">
        @foreach ($users as $u)
            @php $display = $u->display_name . ' (' . $u->name . ')'; @endphp
            <label class="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-slate-50"
                x-show="q === '' || '{{ \Illuminate\Support\Str::lower($display) }}'.includes(q.toLowerCase())">
                <input type="checkbox" name="{{ $name }}" value="{{ $u->id }}"
                    @checked(in_array($u->id, $selectedIds, true))
                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-slate-700">{{ $display }}</span>
            </label>
        @endforeach
    </div>
    @error(str($name)->replace('[]', '')->toString())<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    @error(str($name)->replace('[]', '').'.0')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
