<?php

namespace App\Data\Image;

use App\Services\AppService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?string       $name = '',
        public ?string       $currentURL = '',
        public ?string       $originalFile = '',
        public ?int       $width = 0,
        public ?int       $height = 0,
        public ?int       $depth = 0,
        public ?UploadedFile $file = null,
        public ?int          $idLanguage = null,
        public ?int          $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
        $this->originalFile = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        if ($this->name == '') {
            $this->name = $this->originalFile;
        }
        if (is_null($this->idLanguage)) {
            $this->idLanguage = AppService::getCurrentIdLanguage();
        }

        $client = new Client([
            'timeout' => 300.0,
        ]);
        $url = config('webtool.mediaURL');
        $response = $client->request('POST', $url, [
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => Utils::tryFopen($file->getPathname(), 'r'),
                    'filename' => $this->originalFile,
                ]
            ]
        ]);
//        $this->currentURL = trim(str_replace($url . '/', '',(string)$response->getBody()));
        $this->currentURL = trim(str_replace(str_replace('https','http',$url) . '/', '',(string)$response->getBody()));

        $dimensions = $file->dimensions();
        $this->width = $dimensions[0] ?? 0;
        $this->height = $dimensions[1] ?? 0;
        $this->depth = $dimensions['bits'] ?? 0;
    }

}
