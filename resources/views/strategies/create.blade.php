<x-layouts.app title="เพิ่มยุทธศาสตร์" header="เพิ่มยุทธศาสตร์">
    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('strategies.store') }}">
                @csrf
                @include('strategies._form')
            </form>
        </x-card>
    </div>
</x-layouts.app>
