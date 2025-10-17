<?php

namespace App\Data\Video;

use App\Services\AppService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class UploadData extends Data
{
    public function __construct(
        public ?int          $idVideo = null,
        public ?string       $currentURL = '',
        public ?UploadedFile $file = null,
        public ?int          $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
        $originalName = $file->getClientOriginalName();
//        $this->originalFile = $file->getClientOriginalName();
//        $this->sha1Name = sha1($file->getClientOriginalName());
//        $extension = $file->getClientOriginalExtension();
//        $fileName = $this->sha1Name . '_original' . '.' . $extension;
//        $file->storeAs('videos', $fileName);
        $client = new Client([
            'timeout' => 300.0,
        ]);
        $url = config('webtool.mediaURL');
        $response = $client->request('POST', $url, [
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => Utils::tryFopen($file->getPathname(), 'r'),
                    'filename' => $originalName,
                ]
            ]
        ]);
        $this->currentURL = trim(str_replace(str_replace('https','http',$url) . '/', '',(string)$response->getBody()));
    }

}
