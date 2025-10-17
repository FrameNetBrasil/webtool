@php
    $sentence = trim($sentence->text);
    $words = explode(' ', $sentence);
    $title = "";//($idFrame ? "Frame: " . $frame->name : "No Frame")
@endphp
<div id="annotationStaticFrameMode1FETabs" class="grid pl-2">
    @if(count($frames) > 0)

        @foreach($frames as $idFrame => $frame)
            @php($idObject = 0)
            <div class="col-fixed">
                <div class="ui card w-full">
                    <div class="content">
                    <span class="right floated">
                        <x-delete
                            title="delete FE Constraint"
                            onclick="messenger.confirmDelete(`Removing Frame '{{$frame['name']}}'.`, '/annotation/staticEvent/fes/{{$idDocumentSentence}}/{{$idFrame}}')"
                        ></x-delete>
                    </span>
                        <div
                            class="header"
                        >
                            <x-element.frame
                                name="{{$frame['name']}}"
                            ></x-element.frame>
                        </div>
                        <hr>
                        <div class="description">
                            <form>
                                @foreach($objects as $i => $object)
                                    @php($phrase = '')
                                    @if($type != 'No Text')
                                        @for($w = $object->startWord - 1; $w < $object->endWord; $w++)
                                            @php($phrase .= ' '. $words[$w])
                                        @endfor
                                    @endif
                                    @if((count($object->bboxes) > 0) || ($object->name == 'scene'))
                                        <x-card class="m-2 font-bold"
                                                title="Object #{{++$idObject}}: <span class='wt-anno-box-color-{{$idObject}}'>{{$phrase}}</span> ">
                                            @php($value = isset($frame['objects'][$i]) ? $frame['objects'][$i]->idFrameElement : -1)
                                            <x-combobox.fe-frame
                                                id="objects_{{$idFrame}}_{{$object->idAnnotationObject}}"
                                                name="objects[{{$idFrame}}][{{$object->idAnnotationObject}}]"
                                                label=""
                                                :value="$value"
                                                :idFrame="$idFrame"
                                                :hasNull="true"
                                            ></x-combobox.fe-frame>
                                        </x-card>
                                    @else
                                        @php(++$idObject)
                                    @endif
                                @endforeach
                                <hr>
                                <div class="ml-2 mb-2">
                                    <x-button
                                        id="btnSubmitFE{{$i}}"
                                        label="Submit FEs"
                                        hx-put="/annotation/staticEvent/fes/{{$idDocumentSentence}}/{{$idFrame}}"
                                    ></x-button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>


            </div>
        @endforeach
    @else
        <div class="col-fixed">
            No frame.
        </div>
    @endif
</div>
