{{-- The logs as a table: $logs (paginator or collection), $routes, optional $empty --}}
<x-forms::table model="logs" no-checkbox data-activitylog-table>
    <x-slot:headers>
        <x-forms::table.heading :label="__('Date')" />
        <x-forms::table.heading :label="__('Tag')" />
        <x-forms::table.heading :label="__('Description')" />
        <x-forms::table.heading :label="__('User')" />
        <x-forms::table.heading :label="__('Actions')" />
    </x-slot:headers>

    <x-slot:rows>
        @forelse($logs as $log)
            @include('activitylog::logs.partials.row', ['log' => $log])
        @empty
            <x-forms::table.empty-row :columns="5" no-checkbox>
                {{ $empty ?? __('No activity yet') }}
            </x-forms::table.empty-row>
        @endforelse
    </x-slot:rows>
</x-forms::table>
