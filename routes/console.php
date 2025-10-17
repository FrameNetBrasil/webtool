<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('schema', function () {
    debug(collect(Schema::getColumns('user'))->pluck('name')->all());
});

/*
 * Multimodal
 */

Artisan::command('webtool40_multimodal_video_process_doc {idDocument}', function ($idDocument) {
    $service = new \App\Offline\Multimodal\videoPreprocess_Livia($idDocument);
    $service->process();
})->purpose('');

Artisan::command('webtool40_multimodal_audio_process_doc {idDocument}', function ($idDocument) {
    $service = new \App\Offline\Multimodal\audioPreprocess_Livia($idDocument);
    $service->process();
})->purpose('');
