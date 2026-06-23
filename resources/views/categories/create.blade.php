<x-layouts.app title="เพิ่มหมวด KPI" header="เพิ่มหมวด KPI">
    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('categories.store') }}">
                @csrf
                @include('categories._form')
            </form>
        </x-card>
    </div>
</x-layouts.app>
