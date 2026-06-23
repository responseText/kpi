<x-layouts.app title="เพิ่ม KPI หลัก" header="เพิ่ม KPI หลัก">
    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('mains.store') }}">
                @csrf
                @include('mains._form')
            </form>
        </x-card>
    </div>
</x-layouts.app>
