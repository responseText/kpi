@props(['name', 'label' => null, 'value' => null, 'required' => false, 'rows' => 3])

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-slate-700">
            {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @if ($required) required @endif
        {{ $attributes->merge(['class' => 'w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500' . ($errors->has($name) ? ' border-red-400' : '')]) }}>{{ old($name, $value) }}</textarea>
    @error($name)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
