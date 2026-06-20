<x-layouts.app title="แก้ไขหน่วยวัด KPI" header="แก้ไขหน่วยวัด KPI">
    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('units.update', $unit) }}">
                @csrf
                @method('PUT')
                @include('units._form')
            </form>
        </x-card>
    </div>
</x-layouts.app>
