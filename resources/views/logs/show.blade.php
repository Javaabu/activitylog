@extends(config('activitylog.layout', 'layouts.app'))

@section(config('activitylog.layout_section', 'content'))
    <div class="flex flex-col gap-6" data-activitylog data-activity-log="{{ $log->getKey() }}">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">{{ $title }}</h1>

            <div class="flex flex-wrap items-center gap-2">
                @if($can_rollback && \Illuminate\Support\Facades\Route::has($routes['rollback.confirm']))
                    <x-forms::link-button :url="route($routes['rollback.confirm'], $log->getKey())" color="warning" size="sm">
                        {{ __('Roll back') }}
                    </x-forms::link-button>
                @endif

                <x-forms::link-button :url="route($routes['index'])" color="secondary" size="sm" start-icon="chevron-left">
                    {{ __('Back to the activity log') }}
                </x-forms::link-button>
            </div>
        </div>

        @include('activitylog::logs.partials.alerts')

        <x-forms::card>
            <p class="mb-4 text-lg" dir="auto" data-activitylog-description>{{ $log->describe() }}</p>

            @include('activitylog::logs.partials.details', ['log' => $log])
        </x-forms::card>

        <x-forms::card :title="__('Changes')">
            @include('activitylog::logs.partials.diff', ['changes' => $changes])
        </x-forms::card>

        @if($properties)
            <x-forms::card :title="__('Details')">
                <dl class="grid gap-4 sm:grid-cols-2" data-activitylog-properties>
                    @foreach($properties as $key => $value)
                        <div>
                            <dt class="text-sm text-muted-foreground">{{ str($key)->replace('_', ' ')->ucfirst() }}</dt>
                            <dd class="break-words" dir="auto">{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-forms::card>
        @endif
    </div>
@endsection
