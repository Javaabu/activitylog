{{--
    A model's latest logs, for the app's own pages:
    <x-activitylog::history :subject="$invoice" />
    <x-activitylog::history :subject="$invoice" :limit="20" route="admin.logs" />
--}}
@props(['subject', 'limit' => 10, 'route' => 'logs', 'title' => null])
@php
    $user = auth()->user();
    $logs = \Javaabu\Activitylog\Activitylog::activityModel()::query()
        ->visibleTo($user)
        ->forSubject($subject)
        ->with(['causer', 'subject'])
        ->latest('created_at')
        ->latest('id')
        ->limit($limit)
        ->get();
    $routes = [
        'index' => "{$route}.index",
        'show' => "{$route}.show",
    ];
@endphp
<x-forms::card :title="$title ?? __('History')" {{ $attributes->merge(['data-activitylog-history' => $subject->getMorphClass() . ':' . $subject->getKey()]) }}>
    @include('activitylog::logs.partials.table', ['logs' => $logs, 'routes' => $routes])

    @if(\Illuminate\Support\Facades\Route::has($routes['index']) && $user?->can('viewAny', \Javaabu\Activitylog\Activitylog::activityModel()))
        <x-slot:footer>
            <x-forms::link-button :url="route($routes['index'], ['subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey()])" color="secondary" size="sm">
                {{ __('All activity') }}
            </x-forms::link-button>
        </x-slot:footer>
    @endif
</x-forms::card>
