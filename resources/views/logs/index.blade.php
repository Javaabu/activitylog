@extends(config('activitylog.layout', 'layouts.app'))

@section(config('activitylog.layout_section', 'content'))
    <div class="flex flex-col gap-6" data-activitylog>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold"><bdi>{{ $title }}</bdi></h1>

            @if($subject)
                <x-forms::link-button :url="route($routes['index'])" color="secondary" size="sm" start-icon="chevron-left">
                    {{ __('All activity') }}
                </x-forms::link-button>
            @endif
        </div>

        @include('activitylog::logs.partials.alerts')

        <x-filters::bar :filters="$filters" :export="$can_export ? $export_formats : false" />

        @include('activitylog::logs.partials.table', ['logs' => $logs, 'empty' => $filters->hasActiveFilters() ? __('No activity matches these filters.') : __('No activity yet')])

        @if($logs->hasPages())
            <x-forms::pagination :paginator="$logs" />
        @endif
    </div>
@endsection
