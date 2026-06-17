<x-layouts.app title="เพิ่มกลยุทธ์" header="เพิ่มกลยุทธ์">
    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('sub-strategies.store') }}">
                @csrf
                @include('sub_strategies._form')
            </form>
        </x-card>
    </div>
</x-layouts.app>
