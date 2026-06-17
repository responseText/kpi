<x-layouts.app title="แก้ไขตัวชี้วัด" header="แก้ไขตัวชี้วัด">
    <div class="max-w-4xl">
        <x-card>
            <form method="POST" action="{{ route('indicators.update', $indicator) }}">
                @csrf
                @method('PUT')
                @include('indicators._form')
            </form>
        </x-card>
    </div>
</x-layouts.app>
