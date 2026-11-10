@props(['tag'])
@php($svg = \Javaabu\Activitylog\Support\Icons::svg($tag->icon()))
<x-forms::status :color="$tag->color()" data-activity-tag="{{ $tag->key() }}">
    <span class="inline-flex items-center gap-1">
        @if($svg)
            <svg class="size-3.5 shrink-0" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $svg !!}</svg>
        @elseif($tag->icon())
            <i class="{{ $tag->icon() }}" aria-hidden="true"></i>
        @endif
        <span>{{ $tag->label() }}</span>
    </span>
</x-forms::status>
