<x-layouts.app title="แก้ไขหมวด KPI" header="แก้ไขหมวด KPI">
    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('categories.update', $category) }}">
                @csrf
                @method('PUT')
                @include('categories._form')
            </form>
        </x-card>
    </div>
</x-layouts.app>
