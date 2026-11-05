{{-- Attribute changes: $changes (Diff rows), $old_label, $new_label --}}
@if($changes)
    <x-forms::table model="changes" no-checkbox data-activitylog-diff>
        <x-slot:headers>
            <x-forms::table.heading :label="__('Attribute')" />
            <x-forms::table.heading :label="$old_label ?? __('Old value')" />
            <x-forms::table.heading :label="$new_label ?? __('New value')" />
        </x-slot:headers>

        <x-slot:rows>
            @foreach($changes as $row)
                <x-forms::table.row name="changes" :model-id="$row['attribute']" no-checkbox data-attribute="{{ $row['attribute'] }}">
                    <x-forms::table.cell name="attribute" :label="__('Attribute')">
                        <span class="font-medium">{{ $row['label'] }}</span>
                    </x-forms::table.cell>
                    <x-forms::table.cell name="old" :label="$old_label ?? __('Old value')">
                        @if($row['old'] !== null)
                            <del class="whitespace-pre-line break-words text-destructive no-underline" dir="auto">{{ $row['old'] }}</del>
                        @endif
                    </x-forms::table.cell>
                    <x-forms::table.cell name="new" :label="$new_label ?? __('New value')">
                        @if($row['new'] !== null)
                            <ins class="whitespace-pre-line break-words text-success no-underline" dir="auto">{{ $row['new'] }}</ins>
                        @endif
                    </x-forms::table.cell>
                </x-forms::table.row>
            @endforeach
        </x-slot:rows>
    </x-forms::table>
@else
    <p class="text-muted-foreground">{{ __('No changes were logged.') }}</p>
@endif
