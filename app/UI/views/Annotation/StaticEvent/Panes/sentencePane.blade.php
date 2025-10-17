@php
    $words = [];
    $parts = explode(' ', trim($sentence->text));
    foreach($parts as $i => $word) {
        $words[$i + 1] = [
            'id' => $i + 1,
            'word' => $word,
            'idObject' => 0
        ];
    }
    $idObject = 0;
    foreach($objects as $object) {
        ++$idObject;
        for($i = $object->startWord; $i <= $object->endWord; $i++) {
            $words[$i]['idObject'] = $idObject;
        }
    }
@endphp
<div id="sentencePane" style="width:100%">
    <div id="annotatedSentence"
         style="width: {{$image->width}}px; margin-bottom:8px;display: flex; flex-direction:row; flex-wrap:wrap;">
        @foreach($words as $word)
            <span class="annotatedWord wt-anno-box-color-{{$word['idObject']}}">{{$word['word']}}</span>
        @endforeach
    </div>
</div>

