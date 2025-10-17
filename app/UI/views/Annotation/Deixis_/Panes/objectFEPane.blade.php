    @php($label = isset($frameName) ? $frameName.'.FE' : 'FE')
    <x-combobox.fe-frame id="idFrameElement" :idFrame="$idFrame ?? 0" :label="$label"></x-combobox.fe-frame>
