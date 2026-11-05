{{-- Who, what, when of a log: $log, $routes --}}
@php($subject_type = $log->subject_type ? app(\Javaabu\Activitylog\Descriptions\Describer::class)->modelLabel(\Javaabu\Activitylog\Activitylog::morphedModel($log->subject_type) ?? $log->subject_type) : null)
<dl class="grid gap-4 sm:grid-cols-2" data-activitylog-details>
    <div>
        <dt class="text-sm text-muted-foreground">{{ __('Tag') }}</dt>
        <dd><x-activitylog::tag :tag="$log->tag()" /></dd>
    </div>
    <div>
        <dt class="text-sm text-muted-foreground">{{ __('Date') }}</dt>
        <dd><time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->isoFormat('LLL') }}</time></dd>
    </div>
    <div>
        <dt class="text-sm text-muted-foreground">{{ __('User') }}</dt>
        <dd>
            @if($log->causer)
                @php($causer_url = \Javaabu\Activitylog\Activitylog::url($log->causer))
                @if($causer_url)
                    <a href="{{ $causer_url }}" class="hover:underline"><bdi>{{ \Javaabu\Activitylog\Activitylog::displayName($log->causer) }}</bdi></a>
                @else
                    <bdi>{{ \Javaabu\Activitylog\Activitylog::displayName($log->causer) }}</bdi>
                @endif
            @else
                <span class="text-muted-foreground">{{ $log->causer_type ? __('A deleted user') : __('System') }}</span>
            @endif
        </dd>
    </div>
    @if($subject_type)
        <div>
            <dt class="text-sm text-muted-foreground">{{ __('Record') }}</dt>
            <dd class="flex flex-wrap items-center gap-2">
                <span>{{ $subject_type }}</span>
                @if($log->subject)
                    @php($subject_url = \Javaabu\Activitylog\Activitylog::url($log->subject))
                    @if($subject_url)
                        <a href="{{ $subject_url }}" class="font-medium hover:underline"><bdi>{{ \Javaabu\Activitylog\Activitylog::displayName($log->subject) }}</bdi></a>
                    @else
                        <bdi class="font-medium">{{ \Javaabu\Activitylog\Activitylog::displayName($log->subject) }}</bdi>
                    @endif
                @else
                    <bdi>#{{ $log->subject_id }}</bdi>
                    <x-forms::status color="secondary" :label="__('Deleted')" />
                @endif
                @if(\Illuminate\Support\Facades\Route::has($routes['index']))
                    <a href="{{ route($routes['index'], ['subject_type' => $log->subject_type, 'subject_id' => $log->subject_id]) }}" class="text-sm hover:underline">{{ __('History') }}</a>
                @endif
            </dd>
        </div>
    @endif
    @if($log->ip)
        <div>
            <dt class="text-sm text-muted-foreground">{{ __('IP address') }}</dt>
            <dd><bdi class="tabular-nums">{{ $log->ip }}</bdi></dd>
        </div>
    @endif
    @if($log->log_name)
        <div>
            <dt class="text-sm text-muted-foreground">{{ __('Log') }}</dt>
            <dd><bdi>{{ $log->log_name }}</bdi></dd>
        </div>
    @endif
</dl>
