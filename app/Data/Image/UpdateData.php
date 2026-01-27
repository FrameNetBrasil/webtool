<?php

namespace App\Data\Image;

use App\Database\Criteria;
use App\Services\AppService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public int          $idImage,
        public ?string       $name = '',
        public ?int          $idDocument = 0,
        public ?string       $currentURL = '',
        public ?string       $originalFile = '',
        public ?int          $width = 0,
        public ?int          $height = 0,
        public ?int          $depth = 0,
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
        if (!is_null($this->idDocument)) {
            $document = Criteria::table("view_document")
                ->where("idDocument", $this->idDocument)
                ->where("idLanguage", $this->idLanguage)
                ->first();
            $this->name = $document->name . '_' . $this->name;
        }

        $client = new Client([
            'timeout' => 300.0,
        ]);
        $url = config('webtool.mediaURL');
        $response = $client->request('POST', $url, [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => Utils::tryFopen($file->getPathname(), 'r'),
                    'filename' => $this->originalFile,
                ]
            ]
        ]);
        debug($response);
//        $this->currentURL = trim(str_replace($url . '/', '',(string)$response->getBody()));
        $this->currentURL = trim(str_replace(str_replace('https', 'http', $url) . '/', '', (string)$response->getBody()));

        $dimensions = $file->dimensions();
        $this->width = $dimensions[0] ?? 0;
        $this->height = $dimensions[1] ?? 0;
        $this->depth = $dimensions['bits'] ?? 0;
    }
}
