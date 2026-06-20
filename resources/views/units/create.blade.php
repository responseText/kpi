<x-layouts.app title="เพิ่มหน่วยวัด KPI" header="เพิ่มหน่วยวัด KPI">
    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('units.store') }}">
                @csrf
                @include('units._form')
            </form>
        </x-card>
    </div>
</x-layouts.app>
