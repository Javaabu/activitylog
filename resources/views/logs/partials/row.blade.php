<x-forms::table.row name="logs" :model-id="$log->getKey()" no-checkbox data-activity-log="{{ $log->getKey() }}">
    <x-forms::table.cell name="created_at" :label="__('Date')">
        <time datetime="{{ $log->created_at?->toIso8601String() }}" title="{{ $log->created_at?->isoFormat('LLL') }}">{{ $log->created_at?->diffForHumans() }}</time>
    </x-forms::table.cell>
    <x-forms::table.cell name="tag" :label="__('Tag')">
        <x-activitylog::tag :tag="$log->tag()" />
    </x-forms::table.cell>
    <x-forms::table.cell name="description" :label="__('Description')">
        <span dir="auto">{{ $log->describe() }}</span>
    </x-forms::table.cell>
    <x-forms::table.cell name="causer" :label="__('User')">
        @if($log->causer)
            <bdi>{{ \Javaabu\Activitylog\Activitylog::displayName($log->causer) }}</bdi>
        @else
            <span class="text-muted-foreground">{{ $log->causer_type ? __('A deleted user') : __('System') }}</span>
        @endif
    </x-forms::table.cell>
    <x-forms::table.cell name="actions" :label="__('Actions')">
        @if(\Illuminate\Support\Facades\Route::has($routes['show']) && auth()->user()?->can('view', $log))
            <x-forms::link-button :url="route($routes['show'], $log->getKey())" color="secondary" size="sm">
                {{ __('View') }}
            </x-forms::link-button>
        @endif
    </x-forms::table.cell>
</x-forms::table.row>
