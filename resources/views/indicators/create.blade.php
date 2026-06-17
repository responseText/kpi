<x-layouts.app title="เพิ่มตัวชี้วัด" header="เพิ่มตัวชี้วัด">
    <div class="max-w-4xl">
        <x-card>
            <form method="POST" action="{{ route('indicators.store') }}">
                @csrf
                @include('indicators._form')
            </form>
        </x-card>
    </div>
</x-layouts.app>
