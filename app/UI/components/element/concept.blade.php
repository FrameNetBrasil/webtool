<div class="flex align-items-center">
    @if ($type == "inf")
        <div><x-icon.concept_inf></x-icon.concept_inf></div>
        <div  class="color_concept_inf">{{$name}}</div>
    @elseif ($type == "sem")
        <div><x-icon.concept_sem></x-icon.concept_sem></div>
        <div  class="color_concept_sem">{{$name}}</div>
    @elseif ($type == "cxn")
        <div><x-icon.concept_cxn></x-icon.concept_cxn></div>
        <div  class="color_concept_cxn">{{$name}}</div>
    @elseif ($type == "str")
        <div><x-icon.concept_str></x-icon.concept_str></div>
        <div  class="color_concept_str">{{$name}}</div>
    @elseif ($type == "def")
        <div><x-icon.concept_def></x-icon.concept_def></div>
        <div  class="color_concept_def">{{$name}}</div>
    @endif
</div>
