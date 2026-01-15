<?php

namespace App\Console\Commands\Mariane_ad;

use App\Database\Criteria;
use App\Services\AppService;
use App\Services\CosineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CosineMarianeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cosine:mariane';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cosine similarity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '10240M');
        AppService::setCurrentLanguage(1);

        // Mariane_videos

        // PPM1
/*
        $references = [];
        $idReference = 1;
        $fileName = __DIR__ . "/PPM1_videos.csv";
        if (($handle = fopen($fileName, "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $references[trim($line[0])] = $idReference++;
            }
        }
        fclose($handle);
        print_r($references);
        $fileName = __DIR__ . "/frames_ppm1_com.csv";
        if (($handle = fopen($fileName, "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $video = $line[0];
                $frames = explode(";", trim($line[1]));
                $idReference = $references[$video];

                $referenceNode = Criteria::byId('cosine_node', 'idReference', $idReference);
                if ($referenceNode?->idCosineNode) {
                    Criteria::table('cosine_link')
                        ->where('idCosineNodeSource', $referenceNode->idCosineNode)
                        ->delete();
                    Criteria::table('cosine_node')
                        ->where('idCosineNode', $referenceNode->idCosineNode)
                        ->delete();
                }
                $idCosineNodeReference = Criteria::create('cosine_node', [
                    'name' => 'ref_' . $idReference,
                    'type' => 'REF',
                    'idReference' => $idReference,
                ]);

                foreach ($frames as $frame) {
                    $idCosineNodeFrame = Criteria::byId('cosine_node', 'idFrame', $frame)->idCosineNode;
                    Criteria::create('cosine_link', [
                        'idCosineNodeSource' => $idCosineNodeReference,
                        'idCosineNodeTarget' => $idCosineNodeFrame,
                        'value' => 1.0,
                        'type' => 'lu',
                    ]);
                }

            }
        }
        fclose($handle);
        $fileName = __DIR__ . "/frames_ppm1_sem.csv";
        if (($handle = fopen($fileName, "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $video = $line[0];
                $frames = explode(";", trim($line[1]));
                $idReference = $references[$video] + 1000;

                $referenceNode = Criteria::byId('cosine_node', 'idReference', $idReference);
                if ($referenceNode?->idCosineNode) {
                    Criteria::table('cosine_link')
                        ->where('idCosineNodeSource', $referenceNode->idCosineNode)
                        ->delete();
                    Criteria::table('cosine_node')
                        ->where('idCosineNode', $referenceNode->idCosineNode)
                        ->delete();
                }
                print_r($idReference . "\n");
                $idCosineNodeReference = Criteria::create('cosine_node', [
                    'name' => 'ref_' . $idReference,
                    'type' => 'REF',
                    'idReference' => $idReference,
                ]);

                foreach ($frames as $frame) {
                    $idCosineNodeFrame = Criteria::byId('cosine_node', 'idFrame', $frame)->idCosineNode;
                    Criteria::create('cosine_link', [
                        'idCosineNodeSource' => $idCosineNodeReference,
                        'idCosineNodeTarget' => $idCosineNodeFrame,
                        'value' => 1.0,
                        'type' => 'lu',
                    ]);
                }

            }
        }
        fclose($handle);
        $fileName = __DIR__ . "/frames_ppm1_video.csv";
        if (($handle = fopen($fileName, "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $video = $line[0];
                $frames = explode(";", trim($line[1]));
                $idReference = $references[$video] + 2000;

                $referenceNode = Criteria::byId('cosine_node', 'idReference', $idReference);
                if ($referenceNode?->idCosineNode) {
                    Criteria::table('cosine_link')
                        ->where('idCosineNodeSource', $referenceNode->idCosineNode)
                        ->delete();
                    Criteria::table('cosine_node')
                        ->where('idCosineNode', $referenceNode->idCosineNode)
                        ->delete();
                }
                $idCosineNodeReference = Criteria::create('cosine_node', [
                    'name' => 'ref_' . $idReference,
                    'type' => 'REF',
                    'idReference' => $idReference,
                ]);

                foreach ($frames as $frame) {
                    $idCosineNodeFrame = Criteria::byId('cosine_node', 'idFrame', $frame)->idCosineNode;
                    Criteria::create('cosine_link', [
                        'idCosineNodeSource' => $idCosineNodeReference,
                        'idCosineNodeTarget' => $idCosineNodeFrame,
                        'value' => 1.0,
                        'type' => 'lu',
                    ]);
                }

            }
        }
        fclose($handle);

        $result = [];
        foreach ($references as $video => $idReference) {
            $r = CosineService::compareReferences($idReference, $idReference + 1000);
            $result[] = [
                'video' => $video,
                'cosine' => $r->cosine,
            ];
        }
        CosineService::writeToCSV(__DIR__ . "/PPM_1_COM_SEM.csv", $result);

        $result = [];
        foreach ($references as $video => $idReference) {
            $r = CosineService::compareReferences($idReference, $idReference + 2000);
            $result[] = [
                'video' => $video,
                'cosine' => $r->cosine,
            ];
        }
        CosineService::writeToCSV(__DIR__ . "/PPM_1_COM_VIDEO.csv", $result);

        $result = [];
        foreach ($references as $video => $idReference) {
            $r = CosineService::compareReferences($idReference + 1000, $idReference + 2000);
            $result[] = [
                'video' => $video,
                'cosine' => $r->cosine,
            ];
        }
        CosineService::writeToCSV(__DIR__ . "/PPM_1_SEM_VIDEO.csv", $result);
*/

        // PPM7

        $references = [];
        $idReference = 1;
        $fileName = __DIR__ . "/PPM7_videos.csv";
        if (($handle = fopen($fileName, "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $references[trim($line[0])] = 3000 + $idReference++;
            }
        }
        fclose($handle);
        print_r($references);
        $fileName = __DIR__ . "/frames_ppm7_com.csv";
        if (($handle = fopen($fileName, "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $video = $line[0];
                $frames = explode(";", trim($line[1]));
                $idReference = $references[$video];

                $referenceNode = Criteria::byId('cosine_node', 'idReference', $idReference);
                if ($referenceNode?->idCosineNode) {
                    Criteria::table('cosine_link')
                        ->where('idCosineNodeSource', $referenceNode->idCosineNode)
                        ->delete();
                    Criteria::table('cosine_node')
                        ->where('idCosineNode', $referenceNode->idCosineNode)
                        ->delete();
                }
                $idCosineNodeReference = Criteria::create('cosine_node', [
                    'name' => 'ref_' . $idReference,
                    'type' => 'REF',
                    'idReference' => $idReference,
                ]);

                foreach ($frames as $frame) {
                    $idCosineNodeFrame = Criteria::byId('cosine_node', 'idFrame', $frame)->idCosineNode;
                    Criteria::create('cosine_link', [
                        'idCosineNodeSource' => $idCosineNodeReference,
                        'idCosineNodeTarget' => $idCosineNodeFrame,
                        'value' => 1.0,
                        'type' => 'lu',
                    ]);
                }

            }
        }
        fclose($handle);
        $fileName = __DIR__ . "/frames_ppm7_sem.csv";
        if (($handle = fopen($fileName, "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $video = $line[0];
                $frames = explode(";", trim($line[1]));
                $idReference = $references[$video] + 1000;

                $referenceNode = Criteria::byId('cosine_node', 'idReference', $idReference);
                if ($referenceNode?->idCosineNode) {
                    Criteria::table('cosine_link')
                        ->where('idCosineNodeSource', $referenceNode->idCosineNode)
                        ->delete();
                    Criteria::table('cosine_node')
                        ->where('idCosineNode', $referenceNode->idCosineNode)
                        ->delete();
                }
                print_r($idReference . "\n");
                $idCosineNodeReference = Criteria::create('cosine_node', [
                    'name' => 'ref_' . $idReference,
                    'type' => 'REF',
                    'idReference' => $idReference,
                ]);

                foreach ($frames as $frame) {
                    $idCosineNodeFrame = Criteria::byId('cosine_node', 'idFrame', $frame)->idCosineNode;
                    Criteria::create('cosine_link', [
                        'idCosineNodeSource' => $idCosineNodeReference,
                        'idCosineNodeTarget' => $idCosineNodeFrame,
                        'value' => 1.0,
                        'type' => 'lu',
                    ]);
                }

            }
        }
        fclose($handle);
        $fileName = __DIR__ . "/frames_ppm7_video.csv";
        if (($handle = fopen($fileName, "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $video = $line[0];
                $frames = explode(";", trim($line[1]));
                $idReference = $references[$video] + 2000;

                $referenceNode = Criteria::byId('cosine_node', 'idReference', $idReference);
                if ($referenceNode?->idCosineNode) {
                    Criteria::table('cosine_link')
                        ->where('idCosineNodeSource', $referenceNode->idCosineNode)
                        ->delete();
                    Criteria::table('cosine_node')
                        ->where('idCosineNode', $referenceNode->idCosineNode)
                        ->delete();
                }
                $idCosineNodeReference = Criteria::create('cosine_node', [
                    'name' => 'ref_' . $idReference,
                    'type' => 'REF',
                    'idReference' => $idReference,
                ]);

                foreach ($frames as $frame) {
                    $cosineNodeFrame = Criteria::byId('cosine_node', 'idFrame', $frame);
                    if (is_null($cosineNodeFrame)) continue;
                    $idCosineNodeFrame = $cosineNodeFrame->idCosineNode;
                    Criteria::create('cosine_link', [
                        'idCosineNodeSource' => $idCosineNodeReference,
                        'idCosineNodeTarget' => $idCosineNodeFrame,
                        'value' => 1.0,
                        'type' => 'lu',
                    ]);
                }

            }
        }
        fclose($handle);

        $result = [];
        foreach ($references as $video => $idReference) {
            $r = CosineService::compareReferences($idReference, $idReference + 1000);
            $result[] = [
                'video' => $video,
                'cosine' => $r->cosine,
            ];
        }
        CosineService::writeToCSV(__DIR__ . "/PPM_7_COM_SEM.csv", $result);

        $result = [];
        foreach ($references as $video => $idReference) {
            $r = CosineService::compareReferences($idReference, $idReference + 2000);
            $result[] = [
                'video' => $video,
                'cosine' => $r->cosine,
            ];
        }
        CosineService::writeToCSV(__DIR__ . "/PPM_7_COM_VIDEO.csv", $result);

        $result = [];
        foreach ($references as $video => $idReference) {
            $r = CosineService::compareReferences($idReference + 1000, $idReference + 2000);
            $result[] = [
                'video' => $video,
                'cosine' => $r->cosine,
            ];
        }
        CosineService::writeToCSV(__DIR__ . "/PPM_7_SEM_VIDEO.csv", $result);

    }
}
