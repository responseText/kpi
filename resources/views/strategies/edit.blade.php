<x-layouts.app title="แก้ไขยุทธศาสตร์" header="แก้ไขยุทธศาสตร์">
    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('strategies.update', $strategy) }}">
                @csrf
                @method('PUT')
                @include('strategies._form')
            </form>
        </x-card>
    </div>
</x-layouts.app>
