<?php

use Google\Cloud\Storage\StorageClient;

class GoogleStorage
{

    public function __construct()
    {
        //$file = trim("/home/framenetbr/devel/fnbr/charon_docker_maestro/apps/webtool/offline/google-cloud/storage/charon-286713-0b09338da74c.json");
        $file = trim(__DIR__ . "/charon-286713-0b09338da74c.json");
        putenv("GOOGLE_APPLICATION_CREDENTIALS=$file");
    }

    /**
     * Upload a file.
     *
     * @param string $bucketName the name of your Google Cloud bucket.
     * @param string $objectName the name of the object.
     * @param string $source the path to the file to upload.
     *
     * @return Psr\Http\Message\StreamInterface
     */
    function upload_object($bucketName, $objectName, $source)
    {
        print_r($bucketName .' ' . $objectName . ' ' . $source);
        try {
            $storage = new StorageClient();
            $file = fopen($source, 'r');
            $bucket = $storage->bucket($bucketName);
            $object = $bucket->upload($file, [
                'name' => $objectName
            ]);
            printf('Uploaded %s to gs://%s/%s' . PHP_EOL, basename($source), $bucketName, $objectName);
        } catch(\Exception $e) {
            print_r($e->getMessage());
        }
    }
}
