<x-layouts.app title="แก้ไข KPI หลัก" header="แก้ไข KPI หลัก">
    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('mains.update', $main) }}">
                @csrf
                @method('PUT')
                @include('mains._form')
            </form>
        </x-card>
    </div>
</x-layouts.app>
