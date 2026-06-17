<x-layouts.app title="แก้ไขกลยุทธ์" header="แก้ไขกลยุทธ์">
    <div class="max-w-3xl">
        <x-card>
            <form method="POST" action="{{ route('sub-strategies.update', $subStrategy) }}">
                @csrf
                @method('PUT')
                @include('sub_strategies._form')
            </form>
        </x-card>
    </div>
</x-layouts.app>
