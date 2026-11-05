@extends(config('activitylog.layout', 'layouts.app'))

@section(config('activitylog.layout_section', 'content'))
    <div class="flex flex-col gap-6" data-activitylog data-activitylog-rollback="{{ $log->getKey() }}">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">{{ $title }}</h1>

            <x-forms::link-button :url="route($routes['show'], $log->getKey())" color="secondary" size="sm" start-icon="chevron-left">
                {{ __('Back to the log') }}
            </x-forms::link-button>
        </div>

        @include('activitylog::logs.partials.alerts')

        <x-forms::card>
            <p class="mb-4 text-lg" dir="auto">{{ $log->describe() }}</p>

            @include('activitylog::logs.partials.details', ['log' => $log])
        </x-forms::card>

        <x-forms::card :title="__('Roll back to this version')">
            <p class="mb-4 text-muted-foreground">{{ __('These attributes will be restored to the values they had after this log. The rollback is logged.') }}</p>

            @include('activitylog::logs.partials.diff', [
                'changes' => $changes,
                'old_label' => __('Current value'),
                'new_label' => __('Value after rolling back'),
            ])

            <x-slot:footer>
                <x-forms::form method="POST" :action="route($routes['rollback'], $log->getKey())" class="flex flex-wrap items-center gap-2">
                    <x-forms::submit color="warning">{{ __('Roll back') }}</x-forms::submit>
                    <x-forms::link-button :url="route($routes['show'], $log->getKey())" color="secondary">{{ __('Cancel') }}</x-forms::link-button>
                </x-forms::form>
            </x-slot:footer>
        </x-forms::card>
    </div>
@endsection
