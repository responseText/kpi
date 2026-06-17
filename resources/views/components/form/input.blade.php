@props(['name', 'label' => null, 'value' => null, 'type' => 'text', 'required' => false, 'help' => null])

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-slate-700">
            {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
        value="{{ old($name, $value) }}" @if ($required) required @endif
        {{ $attributes->merge(['class' => 'w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500' . ($errors->has($name) ? ' border-red-400' : '')]) }}>
    @if ($help)<p class="mt-1 text-xs text-slate-400">{{ $help }}</p>@endif
    @error($name)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
