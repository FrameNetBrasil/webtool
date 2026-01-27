<?php

namespace App\Console\Commands\DTake;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReadCSVCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dtake:read-csv {--create-sentences : Create sentences, bounding boxes and text spans in the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read all rows from dTake_final_20260219_data_final.csv file and optionally create sentences, bboxes and text spans';

    /**
     * Column name mappings from CSV headers to short names.
     *
     * @var array<string, string>
     */
    protected array $columnMapping = [
        'Image Path' => 'image',
        'GPU' => 'gpu',
        'Entity/Object List' => 'object_en',
        'Entity/Object List (Portuguese)' => 'object_pt',
        'Scene Description (English)' => 'scene_en',
        'Scene Description (Portuguese)' => 'scene_pt',
        'Event Description (English)' => 'event_en',
        'Event Description (Portuguese)' => 'event_pt',
        'Detected_Boxes_Image_Path' => 'detected',
        'Detected_Boxes_Coordinates' => 'bboxes',
        'Original_Image_Coordinates' => 'coord',
        'Consistency Flag' => 'flag',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $csvFile = '/home/ematos/ely/framenet/Dtake/dTake_final_20260219_data_final.csv';

        if (! file_exists($csvFile)) {
            $this->error("CSV file not found: {$csvFile}");

            return Command::FAILURE;
        }

        $this->info("Reading CSV file: {$csvFile}");

        $handle = fopen($csvFile, 'r');
        if ($handle === false) {
            $this->error("Could not open CSV file: {$csvFile}");

            return Command::FAILURE;
        }

        $rowCount = 0;
        $headers = null;

        while (($row = fgetcsv($handle)) !== false) {
            if ($rowCount === 0) {
                // First row contains headers
                $headers = $row;
                $this->info('Headers: '.implode(', ', $headers));
            } else {
                // Process data rows
                $headerCount = count($headers);
                $columnCount = count($row);

                if ($columnCount !== $headerCount) {
                    $this->warn("Row {$rowCount}: Column count mismatch (expected {$headerCount}, got {$columnCount}). Skipping row.");

                    $rowCount++;

                    continue;
                }

                $data = array_combine($headers, $row);

                // Map to short column names
                $mappedData = $this->mapColumnNames($data);

                // Get idDocument for this row
                $idDocument = $this->getIdDocument($mappedData['image']);

                // Add idDocument to the data
                $mappedData['idDocument'] = $idDocument;

                // Create sentences in the database if flag is set
                if ($this->option('create-sentences') && $idDocument !== null) {
                    $this->createSentences($mappedData, $idDocument);
                    $this->createBoundingBoxesAndTextSpans($mappedData, $idDocument);
                    $this->info("Created sentences, bboxes and text spans for row {$rowCount} (idDocument: {$idDocument})");
                }

                // Display each row with short names
                $this->line("Row {$rowCount}: ".json_encode($mappedData));
            }

            $rowCount++;
        }

        fclose($handle);

        $dataRowCount = $rowCount - 1; // Subtract header row
        $this->info("Total rows read: {$dataRowCount} (excluding header)");

        return Command::SUCCESS;
    }

    /**
     * Map CSV column names to short names.
     *
     * @param  array<string, string>  $data
     * @return array<string, string>
     */
    protected function mapColumnNames(array $data): array
    {
        $mapped = [];

        foreach ($data as $originalKey => $value) {
            $shortKey = $this->columnMapping[$originalKey] ?? $originalKey;
            $mapped[$shortKey] = $value;
        }

        return $mapped;
    }

    /**
     * Get the idDocument for a row based on the image path.
     *
     * @param  string  $imagePath  Format: "images/<id>.jpg"
     */
    protected function getIdDocument(string $imagePath): ?int
    {
        // Extract ID from image path (format: "images/<id>.jpg")
        if (! preg_match('/images\/(\d+)\.jpg/', $imagePath, $matches)) {
            $this->warn("Invalid image path format: {$imagePath}");

            return null;
        }

        $id = $matches[1];

        // Get group (first 2 characters of ID)
        $group = substr($id, 0, 2);

        // Compose document name
        $documentName = "Dtake_{$group}_{$id}";

        // Query the database
        $result = DB::select(
            'SELECT idDocument FROM view_document WHERE name = ? AND idLanguage = 1',
            [$documentName]
        );

        if (empty($result)) {
            return null;
        }

        return $result[0]->idDocument;
    }

    /**
     * Create four sentences in the database for scene and event descriptions.
     *
     * @param  array<string, string>  $data
     */
    protected function createSentences(array $data, int $idDocument): void
    {
        // Create scene_en sentence (idOriginMM = 10)
        $jsonData = json_encode([
            'text' => $data['scene_en'],
            'idLanguage' => 2,
            'idDocument' => $idDocument,
        ]);
        $idSentence = Criteria::function('sentence_create(?)', [$jsonData]);
        Criteria::table('sentence')->where('idSentence', $idSentence)->update(['idOriginMM' => 10]);

        // Create scene_pt sentence (idOriginMM = 11)
        $jsonData = json_encode([
            'text' => $data['scene_pt'],
            'idLanguage' => 1,
            'idDocument' => $idDocument,
        ]);
        $idSentence = Criteria::function('sentence_create(?)', [$jsonData]);
        Criteria::table('sentence')->where('idSentence', $idSentence)->update(['idOriginMM' => 11]);

        // Create event_en sentence (idOriginMM = 12)
        $jsonData = json_encode([
            'text' => $data['event_en'],
            'idLanguage' => 2,
            'idDocument' => $idDocument,
        ]);
        $idSentence = Criteria::function('sentence_create(?)', [$jsonData]);
        Criteria::table('sentence')->where('idSentence', $idSentence)->update(['idOriginMM' => 12]);

        // Create event_pt sentence (idOriginMM = 13)
        $jsonData = json_encode([
            'text' => $data['event_pt'],
            'idLanguage' => 1,
            'idDocument' => $idDocument,
        ]);
        $idSentence = Criteria::function('sentence_create(?)', [$jsonData]);
        Criteria::table('sentence')->where('idSentence', $idSentence)->update(['idOriginMM' => 13]);
    }

    /**
     * Create bounding boxes and text spans for objects.
     *
     * @param  array<string, string>  $data
     */
    protected function createBoundingBoxesAndTextSpans(array $data, int $idDocument): void
    {
        // Parse bboxes JSON
        $bboxes = json_decode($data['bboxes'], true);
        if (empty($bboxes)) {
            $this->warn("No bboxes found for document {$idDocument}");

            return;
        }

        // Group bboxes by label
        $groupedBboxes = [];
        foreach ($bboxes as $bbox) {
            $label = $bbox['label'];
            if (! isset($groupedBboxes[$label])) {
                $groupedBboxes[$label] = [];
            }
            $groupedBboxes[$label][] = $bbox;
        }

        // Parse object lists
        $aObjectEn = explode("\n", $data['object_en']);
        $aObjectPt = explode("\n", $data['object_pt']);

        // Check if both arrays have the same number of elements
        if (count($aObjectEn) !== count($aObjectPt)) {
            $this->warn("Mismatch in object count for document {$idDocument}: EN=".count($aObjectEn).', PT='.count($aObjectPt));
        }

        // Get sentence IDs for scene_en and scene_pt
        $sentenceEn = Criteria::table('document_sentence as ds')
            ->join('sentence as s', 'ds.idSentence', '=', 's.idSentence')
            ->where('ds.idDocument', $idDocument)
            ->where('s.idOriginMM', 10)
            ->first();

        $sentencePt = Criteria::table('document_sentence as ds')
            ->join('sentence as s', 'ds.idSentence', '=', 's.idSentence')
            ->where('ds.idDocument', $idDocument)
            ->where('s.idOriginMM', 11)
            ->first();

        if (! $sentenceEn || ! $sentencePt) {
            $this->warn("Sentences not found for document {$idDocument}");

            return;
        }

        $idSentenceEn = $sentenceEn->idSentence;
        $idSentencePt = $sentencePt->idSentence;

        // Get the image associated with the document
        $image = Criteria::table('document_image')
            ->where('idDocument', $idDocument)
            ->first();

        if (! $image) {
            $this->warn("Image not found for document {$idDocument}");

            return;
        }

        // Create static objects and bounding boxes
        $objectIndex = 0;
        foreach ($groupedBboxes as $label => $bboxList) {
            // Create static object
            $sob = json_encode([
                'name' => $label,
                'scene' => 0,
                'idFlickr30kEntitiesChain' => -1,
                'idLayerType' => 51,
                'nobndbox' => 0,
                'idUser' => 6,
            ]);
            $idStaticObject = Criteria::function('staticobject_create(?)', [$sob]);

            // Associate the static object with the image
            Criteria::create('image_staticobject', ['idImage' => $image->idImage, 'idStaticObject' => $idStaticObject]);

            // Create bounding boxes for this object
            foreach ($bboxList as $bbox) {
                $box = $bbox['box'];
                $json = json_encode([
                    'frameNumber' => 0,
                    'frameTime' => 0,
                    'x' => (int) $box[0],
                    'y' => (int) $box[1],
                    'width' => (int) $box[2] - (int) $box[0],
                    'height' => (int) $box[3] - (int) $box[1],
                    'blocked' => 0,
                    'idStaticObject' => (int) $idStaticObject,
                ]);
                $idBoundingBox = Criteria::function('boundingbox_static_create(?)', [$json]);
            }

            // Create text spans for English
            if ($objectIndex < count($aObjectEn)) {
                $objectNameEn = trim($aObjectEn[$objectIndex]);
                $startChar = mb_stripos($data['scene_en'], $objectNameEn);

                if ($startChar !== false) {
                    $endChar = $startChar + mb_strlen($objectNameEn) - 1;

                    $tsData = json_encode([
                        'startChar' => (int) $startChar,
                        'endChar' => (int) $endChar,
                        'multi' => 0,
                        'idSentence' => $idSentenceEn,
                    ]);
                    $idTextSpan = Criteria::function('textspan_char_create(?)', [$tsData]);

                    // Associate text span with static object
                    DB::insert('INSERT INTO staticobject_textspan (idStaticObject, idTextSpan) VALUES (?, ?)', [$idStaticObject, $idTextSpan]);
                }
            }

            // Create text spans for Portuguese
            if ($objectIndex < count($aObjectPt)) {
                $objectNamePt = trim($aObjectPt[$objectIndex]);
                $startChar = mb_stripos($data['scene_pt'], $objectNamePt);

                if ($startChar !== false) {
                    $endChar = $startChar + mb_strlen($objectNamePt) - 1;

                    $tsData = json_encode([
                        'startChar' => (int) $startChar,
                        'endChar' => (int) $endChar,
                        'multi' => 0,
                        'idSentence' => $idSentencePt,
                    ]);
                    $idTextSpan = Criteria::function('textspan_char_create(?)', [$tsData]);

                    // Associate text span with static object
                    DB::insert('INSERT INTO staticobject_textspan (idStaticObject, idTextSpan) VALUES (?, ?)', [$idStaticObject, $idTextSpan]);
                }
            }

            $objectIndex++;
        }
    }
}
