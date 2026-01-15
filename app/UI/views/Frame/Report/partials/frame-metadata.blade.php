{{--
    Frame Metadata Component - Display frame classification and identification tags
    Parameters:
    - $classification: Classification array containing frame metadata
--}}
<div class="page-metadata">
    <div class="metadata-left">
        @if(isset($classification))
            {{-- Domain and Type tags --}}
            @foreach ($classification as $name => $classes)
                @if(($name == 'rel_framal_domain') || ($name == 'rel_framal_type'))
                    @foreach ($classes as $value)
                        <div class="ui basic label color_domain color_domain_border">
                            {{ $value }}
                        </div>
                    @endforeach
                @endif
            @endforeach
        @endif
        <div class="ui basic label color_{{$frame->idColor}}_border">
            <x-element::namespace :namespace="$frame"></x-element::namespace>
        </div>
    </div>
    <div class="metadata-center">
    </div>

    <div class="metadata-right">
        @if(isset($classification['id']) && isset($classification['id'][0]))
            <div class="ui label color_id">
                {{ $classification['id'][0] }}
            </div>
        @endif

        @if(isset($classification['en']) && isset($classification['en'][0]))
            <div class="ui label wt-tag-en">
                {{ $classification['en'][0] }}
            </div>
        @endif
    </div>
</div>
