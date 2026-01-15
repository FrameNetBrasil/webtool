<?php

namespace App\Data\Video;

use App\Services\AppService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?string       $title = '',
        public ?string       $currentURL = '',
        public ?string       $sha1Name = '',
        public ?string       $originalFile = '',
        public ?UploadedFile $file = null,
        public ?int          $idLanguage = null,
        public ?int          $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
        $this->originalFile = $file->getClientOriginalName();
//        $md5 = md5($file->getClientOriginalName());
//        $extension = $file->getClientOriginalExtension();
        $this->sha1Name = sha1($this->originalFile);
        //$file->storeAs('videos', $fileName);

        $client = new Client([
            'timeout' => 300.0,
        ]);
        $url = config('webtool.mediaURL');
        debug($url);
        debug($file);
        $response = $client->request('POST', $url, [
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => Utils::tryFopen($file->getPathname(), 'r'),
                    'filename' => $this->originalFile,
                ]
            ]
        ]);
        $this->currentURL = trim(str_replace(str_replace('https','http',$url) . '/', '',(string)$response->getBody()));
    }

}
