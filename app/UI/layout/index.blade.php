<!DOCTYPE html>
<html id="fnbr-webtool" class="" lang="en">
<head>
    <meta name="Generator" content="Laravel 11.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <title>{!! config('webtool.pageTitle') !!}</title>
    <meta name="description" content="{!! config('webtool.mainTitle') !!}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <style>
        body { visibility: hidden; opacity: 0; }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Mono:wght@100..900&display=swap" rel="stylesheet">

    <!--
    <script type="text/javascript" src="/scripts/htmx/htmx.min.js"></script>
    -->

    <script type="text/javascript" src="https://unpkg.com/htmx.org@2.0.3"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

{{--    <script type="text/javascript" src="/scripts/maestro/manager.js"></script>--}}
    <script type="text/javascript" src="/scripts/pdf/jspdf.debug.js"></script>
    <script type="text/javascript" src="/scripts/pdf/html2canvas.min.js"></script>
    <script type="text/javascript" src="/scripts/pdf/html2pdf.min.js"></script>
{{--    <script defer src="/scripts/alpinejs/cdn.min.js"></script>--}}

    <script type="text/javascript" src="/scripts/jquery-easyui-1.10.17/jquery.easyui.min.js"></script>

    <link rel="stylesheet" type="text/css" href="/scripts/jointjs/dist/joint.css" />
    <script type="text/javascript" src="/scripts/video-js-8.11.5/video.min.js"></script>
    <link href="/scripts/video-js-8.11.5/video-js.css" rel="stylesheet" />

    <script src="/scripts/animation-timeline-js/lib/animation-timeline.js?v=2" type="text/javascript"></script>

    <script src="/scripts/fomantic-ui/semantic.min.js"></script>

    @vite(['resources/js/app.js'])
</head>

<body
    hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
>
{{--@include('components.confirm')--}}

{{$slot}}

<script>
document.addEventListener('DOMContentLoaded', function() {
document.body.style.transition = 'opacity 0.3s ease-in-out';
document.body.style.visibility = 'visible';
document.body.style.opacity = '1';
});
</script>

<!-- App Scripts Go Here -->
<script src="/scripts/lodash/lodash.js"></script>
<script src="/scripts/backbone/backbone.js"></script>
<script src="/scripts/jointjs/dist/joint.js"></script>
<script src="/scripts/dagre/dist/dagre.js"></script>
<script src="/scripts/utils/md5.min.js"></script>
<script src="/scripts/utils/jsfeat.js"></script>
<script src="/scripts/utils/nudged.js"></script>

</body>
</html>
