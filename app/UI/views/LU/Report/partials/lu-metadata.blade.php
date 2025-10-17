{{--
    LU Metadata - Tags and identification for LU Report
    Shows language, ID, frame reference, and edit permissions

    Parameters:
    - $lu: LU object
    - $language: Language object
    - $isMaster: Boolean for edit permissions
--}}

<div class="lu-metadata-section">
    <div class="page-metadata">
        <div class="metadata-left">
            <div class="ui label wt-tag-en">
                {{$language->language}}
            </div>
            <div class="ui label wt-tag-id">
                #{{$lu->idLU}}
            </div>
        </div>
        <div class="metadata-right">
            <button class="ui button basic">
                <a href="/report/frame/{{$lu->idFrame}}">
                    <x-element::frame name="{{$lu->frameName}}"></x-element::frame>
                </a>
            </button>
            @if($isMaster)
                <a href="/lu/{{$lu->idLU}}/edit">
                    <button
                        class="ui button red"
                    >
                        Edit
                    </button>
                </a>
            @endif
        </div>
    </div>
</div>
