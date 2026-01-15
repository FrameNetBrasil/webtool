<?php

namespace App\Console\Commands\Export;

use App\Database\Criteria;
use App\Services\AppService;
use App\Services\XmlExport\BatchExportManager;
use App\Services\XmlExport\ExportProgressTracker;
use App\Services\XmlExport\ExportQueryBuilder;
use App\Services\XmlExport\XmlExportConfig;
use App\Services\XmlExport\XmlTemplateManager;
use App\Services\XmlExport\XmlUtils;
use App\Services\XmlExport\XsdCompliantGenerators;
use DOMDocument;
use Exception;
use Illuminate\Console\Command;
use SimpleXMLElement;

class ExportXmlFrameworkCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'export:xml-framework
                           {type : Type of export (fulltext|frames|lexunit|corpus|all)}
                           {--id= : Specific ID to export}
                           {--corpus= : Corpus ID filter}
                           {--language=2 : Language ID (default: 2)}
                           {--output= : Output directory}
                           {--validate : Validate against XSD}
                           {--limit= : Process only N items (for batch processing)}
                           {--offset= : Skip first N items (for batch processing)}';

    /**
     * The console command description.
     */
    protected $description = 'Export FrameNet data to XML files with XSD validation support';

    private int $idLanguage;

    private string $outputDir;

    private bool $validateXsd;

    private array $config;

    private ExportProgressTracker $tracker;

    private BatchExportManager $batchManager;

    private XsdCompliantGenerators $generators;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Load configuration
        $this->config = config('export_config', []);

        $this->idLanguage = (int) ($this->option('language') ?? $this->config['default_language'] ?? 2);
        // $this->outputDir = $this->option('output') ?? $this->config['output_directory'] ?? 'exports';
        $this->validateXsd = $this->option('validate') ?? $this->config['validate_xsd'] ?? false;

        $this->outputDir = $this->config['output_directory'];
        if ($this->option('output') != '') {
            $this->outputDir .= '/'.$this->option('output');
        }

        AppService::setCurrentLanguage($this->idLanguage);

        // Initialize progress tracker and batch manager
        $this->tracker = new ExportProgressTracker;
        $this->batchManager = new BatchExportManager(
            $this->config['batch_size'] ?? 100,
            $this->outputDir
        );

        // Initialize XSD-compliant generators
        $this->generators = new XsdCompliantGenerators($this->config, $this->idLanguage);

        // Set performance settings from config
        if (isset($this->config['performance']['memory_limit'])) {
            ini_set('memory_limit', $this->config['performance']['memory_limit']);
        }
        if (isset($this->config['performance']['max_execution_time'])) {
            set_time_limit($this->config['performance']['max_execution_time']);
        }

        $type = $this->argument('type');
        $id = $this->option('id');

        // Validate export type
        if (! in_array($type, XmlExportConfig::getExportTypes())) {
            $this->error("Invalid export type: {$type}");
            $this->info('Available types: '.implode(', ', XmlExportConfig::getExportTypes()));

            return 1;
        }

        $this->info("Starting XML export for type: {$type}");
        $this->info("Language ID: {$this->idLanguage}");
        $this->info("Output directory: {$this->outputDir}");
        $this->info('XSD Validation: '.($this->validateXsd ? 'enabled' : 'disabled'));

        // Create output directory if it doesn't exist
        if (! is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        try {
            switch ($type) {
                case 'fulltext':
                    $this->exportFullText($id);
                    break;
                case 'frames':
                    $this->exportFrames($id);
                    break;
                case 'lexunit':
                    $this->exportLexicalUnits($id);
                    break;
                case 'corpus':
                    $this->exportCorpus($id);
                    break;
                case 'frameIndex':
                    $this->exportFrameIndex();
                    break;
                case 'frRelation':
                    $this->exportFrameRelations();
                    break;

                case 'fulltextIndex':
                    $this->exportFulltextIndex();
                    break;
                case 'luIndex':
                    $this->exportLuIndex();
                    break;
                case 'semTypes':
                    $this->exportSemanticTypes();
                    break;
                case 'all':
                    $this->exportAll();
                    break;
                default:
                    $this->error("Unknown export type: {$type}");

                    return 1;
            }

            // Display final statistics
            $stats = $this->batchManager->getStatistics();
            $this->displayStatistics($stats);

            $this->info('Export completed successfully!');

            return 0;

        } catch (Exception $e) {
            $this->error('Export failed: '.$e->getMessage());
            $this->tracker->fail($e->getMessage());

            if ($this->config['logging']['enabled'] ?? true) {
                $this->logError($e);
            }

            return 1;
        }
    }

    /**
     * Export full text annotations
     */
    private function exportFullText(?string $idDocument = null): void
    {
        // Create type-specific subdirectory
        $typeOutputDir = $this->getTypeOutputDirectory('fulltext');
        $this->ensureDirectoryExists($typeOutputDir);

        // Get query and apply limit/offset for batch processing
        $query = $this->getDocumentsQuery($idDocument);
        $totalCount = $query->count();

        // Apply offset and limit for batch processing
        $offset = (int) ($this->option('offset') ?? 0);
        $limit = (int) ($this->option('limit') ?? $totalCount);

        if ($offset > 0) {
            $query = $this->getDocumentsQuery($idDocument);
            $query->offset($offset);
        }

        if ($limit > 0 && $limit < $totalCount) {
            $query = $this->getDocumentsQuery($idDocument);
            if ($offset > 0) {
                $query->offset($offset);
            }
            $query->limit($limit);
            $totalCount = min($limit, $totalCount - $offset);
        }

        $this->info('Exporting '.$totalCount.' document(s) as full text annotations');
        if ($offset > 0 || $limit > 0) {
            $this->info("Batch processing: offset={$offset}, limit={$limit}");
        }
        $this->info("Output directory: {$typeOutputDir}");

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        // Process in chunks to avoid memory exhaustion
        $chunkSize = $this->config['batch_size'] ?? 100;
        $query = $this->getDocumentsQuery($idDocument);

        if ($offset > 0) {
            $query->offset($offset);
        }
        if ($limit > 0) {
            $query->limit($limit);
        }

        $query->chunk($chunkSize, function ($documents) use ($progressBar) {
            foreach ($documents as $document) {
                $this->exportFullTextDocument($document);
                $progressBar->advance();

                // Free memory
                unset($document);
            }

            // Force garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Export frames data
     */
    private function exportFrames(?string $idFrame = null): void
    {
        // Create type-specific subdirectory
        $typeOutputDir = $this->getTypeOutputDirectory('frames');
        $this->ensureDirectoryExists($typeOutputDir);

        // Get query and apply limit/offset for batch processing
        $query = $this->getFramesQuery($idFrame);
        $totalCount = $query->count();

        // Apply offset and limit for batch processing
        $offset = (int) ($this->option('offset') ?? 0);
        $limit = (int) ($this->option('limit') ?? $totalCount);

        $this->info('Exporting '.$totalCount.' frame(s)');
        if ($offset > 0 || ($limit > 0 && $limit < $totalCount)) {
            $this->info("Batch processing: offset={$offset}, limit={$limit}");
        }
        $this->info("Output directory: {$typeOutputDir}");

        $progressBar = $this->output->createProgressBar(min($limit, $totalCount - $offset));
        $progressBar->start();

        // Process in chunks to avoid memory exhaustion
        $chunkSize = $this->config['batch_size'] ?? 100;
        $query = $this->getFramesQuery($idFrame);

        if ($offset > 0) {
            $query->offset($offset);
        }
        if ($limit > 0) {
            $query->limit($limit);
        }

        $query->chunk($chunkSize, function ($frames) use ($progressBar) {
            foreach ($frames as $frame) {
                $this->exportFrame($frame);
                $progressBar->advance();

                // Free memory
                unset($frame);
            }

            // Force garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Export lexical units
     */
    private function exportLexicalUnits(?string $idLU = null): void
    {
        // Create type-specific subdirectory
        $typeOutputDir = $this->getTypeOutputDirectory('lexunit');
        $this->ensureDirectoryExists($typeOutputDir);

        // Get query and apply limit/offset for batch processing
        $query = $this->getLexicalUnitsQuery($idLU);
        $totalCount = $query->count();

        // Apply offset and limit for batch processing
        $offset = (int) ($this->option('offset') ?? 0);
        $limit = (int) ($this->option('limit') ?? $totalCount);

        $this->info('Exporting '.$totalCount.' lexical unit(s)');
        if ($offset > 0 || ($limit > 0 && $limit < $totalCount)) {
            $this->info("Batch processing: offset={$offset}, limit={$limit}");
        }
        $this->info("Output directory: {$typeOutputDir}");

        $progressBar = $this->output->createProgressBar(min($limit, $totalCount - $offset));
        $progressBar->start();

        // Process in chunks to avoid memory exhaustion
        $chunkSize = $this->config['batch_size'] ?? 100;
        $query = $this->getLexicalUnitsQuery($idLU);

        if ($offset > 0) {
            $query->offset($offset);
        }
        if ($limit > 0) {
            $query->limit($limit);
        }

        $query->chunk($chunkSize, function ($lexicalUnits) use ($progressBar) {
            foreach ($lexicalUnits as $lu) {
                $this->exportLexicalUnit($lu);
                $progressBar->advance();

                // Free memory
                unset($lu);
            }

            // Force garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Export corpus data
     */
    private function exportCorpus(?string $idCorpus = null): void
    {
        // Create type-specific subdirectory
        $typeOutputDir = $this->getTypeOutputDirectory('corpus');
        $this->ensureDirectoryExists($typeOutputDir);

        $corpora = $this->getCorpora($idCorpus);

        $this->info('Exporting '.count($corpora).' corpus/corpora');
        $this->info("Output directory: {$typeOutputDir}");

        foreach ($corpora as $corpus) {
            $this->exportCorpusData($corpus);
        }
    }

    /**
     * Export all data types
     */
    private function exportAll(): void
    {
        $this->info('Exporting all data types...');

        // Export indexes first
        $this->exportFrameIndex();
        $this->exportLuIndex();
        $this->exportFulltextIndex();
        $this->exportSemanticTypes();
        $this->exportFrameRelations();

        // Export individual items
        $this->exportFrames();
        $this->exportLexicalUnits();
        $this->exportCorpus();
        $this->exportFullText();
    }

    /**
     * Save XML document with validation using config
     */
    private function saveXmlDocument(DOMDocument $dom, string $filename, string $schemaType): void
    {
        // Add processing instruction if configured
        $templateConfig = $this->config['templates'][$schemaType] ?? [];
        if ($templateConfig['include_processing_instruction'] ?? false) {
            $stylesheet = $this->config['xml']['stylesheets'][$schemaType] ?? "{$schemaType}.xsl";
            XmlUtils::addProcessingInstruction($dom, 'xml-stylesheet', "type=\"text/xsl\" href=\"{$stylesheet}\"");
        }

        // Validate against XSD if requested and configured
        if ($this->validateXsd) {
            $xsdFile = $this->config['xsd_schemas'][$schemaType] ?? null;
            if ($xsdFile && file_exists($xsdFile)) {
                $errors = XmlUtils::validateXml($dom, $xsdFile);
                if (! empty($errors)) {
                    $this->tracker->addWarning("XML validation failed for {$filename}", implode('; ', $errors));
                    if ($this->config['logging']['enabled'] ?? true) {
                        $this->logValidationErrors($filename, $errors);
                    }
                }
            } else {
                $this->tracker->addWarning("XSD schema file not found: {$xsdFile}");
            }
        }

        // Save the file
        file_put_contents($filename, $dom->saveXML());

        // Log successful export if configured
        if ($this->config['logging']['enabled'] ?? true) {
            $this->logExport($filename);
        }
    }

    /**
     * Get documents query (returns query builder for chunking)
     */
    private function getDocumentsQuery(?string $idDocument = null): \App\Database\Criteria
    {
        $queryBuilder = new ExportQueryBuilder;

        // Apply base filters from config
        $filters = $this->config['filters']['fulltext'] ?? [];

        $query = $queryBuilder
            ->addFilter('idLanguage', '=', $this->idLanguage)
            ->addOrderBy('idDocument')
            ->buildQuery($this->config['database_views']['documents'] ?? 'view_document');

        // Apply active filter if configured
        if ($this->config['filters']['active_only'] ?? true) {
            $query->where('active', 1);
        }

        if ($idDocument) {
            $query->where('idDocument', $idDocument);
        }

        if ($corpusId = $this->option('corpus')) {
            $query->where('idCorpus', $corpusId);
        } elseif (isset($this->config['filters']['default_corpus'])) {
            $query->whereIn('idCorpus', $this->config['filters']['default_corpus']);
        }

        return $query;
    }

    /**
     * Get documents based on filters (legacy method for backward compatibility)
     */
    private function getDocuments(?string $idDocument = null): array
    {
        return $this->getDocumentsQuery($idDocument)->all();
    }

    /**
     * Get frames query (returns query builder for chunking)
     */
    private function getFramesQuery(?string $idFrame = null): \App\Database\Criteria
    {
        $queryBuilder = new ExportQueryBuilder;
        $frameFilters = $this->config['filters']['frames'] ?? [];

        $query = $queryBuilder
            ->addFilter('idLanguage', '=', $this->idLanguage)
            ->addOrderBy('entry')
            ->buildQuery($this->config['database_views']['frames'] ?? 'view_frame');

        // Apply active filter if configured
        if ($this->config['filters']['active_only'] ?? true) {
            $query->where('active', 1);
        }

        // Apply frame-specific filters
        if (! ($frameFilters['include_deprecated'] ?? false)) {
            $query->where('active', 1);
        }

        if ($idFrame) {
            $query->where('idFrame', $idFrame);
        }

        return $query;
    }

    /**
     * Get frames based on filters (legacy method for backward compatibility)
     */
    private function getFrames(?string $idFrame = null): array
    {
        return $this->getFramesQuery($idFrame)->all();
    }

    /**
     * Get lexical units query (returns query builder for chunking)
     */
    private function getLexicalUnitsQuery(?string $idLU = null): \App\Database\Criteria
    {
        $query = Criteria::table($this->config['database_views']['lexical_units'] ?? 'view_lu')
            ->join('view_frame as f', 'lu.idFrame', '=', 'f.idFrame')
            ->join('language as l', 'lu.idLanguage', '=', 'l.idLanguage')
            ->where('f.idLanguage', $this->idLanguage)
            ->where('lu.idLanguage', $this->idLanguage)
            ->where('lu.active', 1)
            ->select('lu.idLU', 'lu.name', 'f.name as frameName', 'lu.idFrame', 'l.language')
            ->orderBy('lu.name');

        $luFilters = $this->config['filters']['lexical_units'] ?? [];

        // Apply active filter if configured
        if ($this->config['filters']['active_only'] ?? true) {
            $query->where('lu.active', 1);
        }

        // Apply frequency filter if configured
        if (isset($luFilters['min_frequency']) && $luFilters['min_frequency'] > 0) {
            $query->where('frequency', '>=', $luFilters['min_frequency']);
        }

        if ($idLU) {
            $query->where('idLU', $idLU);
        }

        return $query;
    }

    /**
     * Get lexical units based on filters (legacy method for backward compatibility)
     */
    private function getLexicalUnits(?string $idLU = null): array
    {
        return $this->getLexicalUnitsQuery($idLU)->all();
    }

    /**
     * Get corpora based on filters
     */
    private function getCorpora(?string $idCorpus = null): array
    {
        $queryBuilder = new ExportQueryBuilder;

        $query = $queryBuilder
            ->addFilter('idLanguage', '=', $this->idLanguage)
            ->addOrderBy('name')
            ->buildQuery($this->config['database_views']['corpora'] ?? 'view_corpus');

        // Apply active filter if configured
        if ($this->config['filters']['active_only'] ?? true) {
            $query->where('active', 1);
        }

        if (count($this->config['filters']['default_corpus']) > 0) {
            $query->whereIN('idCorpus', $this->config['filters']['default_corpus']);
        }
        if ($idCorpus) {
            $query->where('idCorpus', $idCorpus);
        }

        return $query->all();
    }

    /**
     * Export single document as full text annotation using XSD-compliant generator
     */
    private function exportFullTextDocument(object $document): void
    {
        // Use XSD-compliant generator
        $dom = $this->generators->generateFullText($document);

        // Generate filename and save
        $filename = $this->generateFilename('fulltext', $document->idDocument);
        $this->saveXmlDocument($dom, $filename, 'fulltext');

        // Aggressively free memory after each document
        unset($dom, $filename);
        $this->generators->resetCaches();

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Export single frame using XSD-compliant generator
     */
    private function exportFrame(object $frame): void
    {
        // Use XSD-compliant generator
        $dom = $this->generators->generateFrame($frame);

        // Generate filename and save
        $filename = $this->generateFilename('frames', $frame->idFrame);
        $this->saveXmlDocument($dom, $filename, 'frames');
    }

    /**
     * Export single lexical unit using XSD-compliant generator
     */
    private function exportLexicalUnit(object $lu): void
    {
        // Use XSD-compliant generator
        $dom = $this->generators->generateLexUnit($lu);

        // Generate filename and save
        $filename = $this->generateFilename('lexunit', $lu->idLU);
        $this->saveXmlDocument($dom, $filename, 'lexunit');
    }

    /**
     * Export frame index
     */
    private function exportFrameIndex(): void
    {
        $this->info('Exporting frame index');

        $dom = $this->generators->generateFrameIndex();
        $filename = $this->generateFilename('frameIndex', 0);
        $this->saveXmlDocument($dom, $filename, 'frameIndex');

        $this->info("Frame index exported to: {$filename}");
    }

    /**
     * Export frame relations
     */
    private function exportFrameRelations(): void
    {
        $this->info('Exporting frame relations');

        $dom = $this->generators->generateFrameRelations();
        $filename = $this->generateFilename('frRelation', 0);
        $this->saveXmlDocument($dom, $filename, 'frRelation');

        $this->info("Frame relations exported to: {$filename}");
    }

    /**
     * Export fulltext index
     */
    private function exportFulltextIndex(): void
    {
        $this->info('Exporting fulltext index');

        $dom = $this->generators->generateFulltextIndex();
        $filename = $this->generateFilename('fulltextIndex', 0);
        $this->saveXmlDocument($dom, $filename, 'fulltextIndex');

        $this->info("Fulltext index exported to: {$filename}");
    }

    /**
     * Export lexical unit index
     */
    private function exportLuIndex(): void
    {
        $this->info('Exporting lexical unit index');

        $dom = $this->generators->generateLuIndex();
        $filename = $this->generateFilename('luIndex', 0);
        $this->saveXmlDocument($dom, $filename, 'luIndex');

        $this->info("Lexical unit index exported to: {$filename}");
    }

    /**
     * Export semantic types
     */
    private function exportSemanticTypes(): void
    {
        $this->info('Exporting semantic types');

        $dom = $this->generators->generateSemanticTypes();
        $filename = $this->generateFilename('semTypes', 0);
        $this->saveXmlDocument($dom, $filename, 'semTypes');

        $this->info("Semantic types exported to: {$filename}");
    }

    /**
     * Export single lexical unit
     */
    //    private function exportLexicalUnit(object $lu): void
    //    {
    //        $xmlStr = $this->createLexicalUnitXmlTemplate();
    //        $sxe = simplexml_load_string($xmlStr);
    //
    //        $luElement = $sxe->addChild('lexicalUnit');
    //        $luElement->addAttribute('ID', $lu->idLU);
    //        $luElement->addAttribute('name', $lu->name);
    //        $luElement->addAttribute('frameID', $lu->idFrame);
    //        $luElement->addAttribute('frameName', $lu->frameName);
    //
    //        if ($lu->senseDescription) {
    //            $luElement->addChild('senseDescription', htmlspecialchars($lu->senseDescription));
    //        }
    //
    //        // Get valence patterns
    //        $valencePatterns = Criteria::table("view_valencepattern")
    //            ->where("idLU", $lu->idLU)
    //            ->where("idLanguage", $this->idLanguage)
    //            ->all();
    //
    //        if (!empty($valencePatterns)) {
    //            $valencesElement = $luElement->addChild('valences');
    //
    //            $groupedPatterns = [];
    //            foreach ($valencePatterns as $pattern) {
    //                $groupedPatterns[$pattern->idValencePattern][] = $pattern;
    //            }
    //
    //            foreach ($groupedPatterns as $patternId => $patterns) {
    //                $patternElement = $valencesElement->addChild('pattern');
    //                $patternElement->addAttribute('ID', $patternId);
    //                $patternElement->addAttribute('count', $patterns[0]->countPattern);
    //
    //                foreach ($patterns as $valent) {
    //                    $valentElement = $patternElement->addChild('valent');
    //                    $valentElement->addAttribute('FE', $valent->feName);
    //                    $valentElement->addAttribute('GF', $valent->GF ?? '');
    //                    $valentElement->addAttribute('PT', $valent->PT ?? '');
    //                }
    //            }
    //        }
    //
    //        $filename = "{$this->outputDir}/lu_{$lu->idLU}.xml";
    //        $this->saveXmlFile($sxe, $filename, 'lexicalUnit.xsd');
    //    }

    /**
     * Export corpus data
     */
    private function exportCorpusData(object $corpus): void
    {
        $xmlStr = $this->createCorpusXmlTemplate();
        $sxe = simplexml_load_string($xmlStr);

        $corpusElement = $sxe->addChild('corpus');
        $corpusElement->addAttribute('ID', $corpus->idCorpus);
        $corpusElement->addAttribute('name', $corpus->name);

        if ($corpus->description) {
            $corpusElement->addChild('description', htmlspecialchars($corpus->description));
        }

        // Get documents in this corpus
        $documents = Criteria::table('view_document')
            ->where('idCorpus', $corpus->idCorpus)
            ->where('idLanguage', $this->idLanguage)
            ->where('active', 1)
            ->orderBy('name')
            ->all();

        foreach ($documents as $document) {
            $docElement = $corpusElement->addChild('document');
            $docElement->addAttribute('ID', $document->idDocument);
            $docElement->addAttribute('name', $document->name);

            if ($document->description) {
                $docElement->addChild('description', htmlspecialchars($document->description));
            }

            if ($document->author) {
                $docElement->addChild('author', htmlspecialchars($document->author));
            }
        }

        $filename = "{$this->outputDir}/corpus_{$corpus->idCorpus}.xml";
        $this->saveXmlFile($sxe, $filename, 'corpus.xsd');
    }

    /**
     * Add sentence with annotations to XML
     */
    private function addSentenceToXml(SimpleXMLElement $sxe, object $sentence): void
    {
        $s = $sxe->addChild('sentence');
        $s->addAttribute('ID', $sentence->idSentence);
        $s->addChild('text', htmlspecialchars($sentence->text));

        // Get annotation sets for this sentence
        $annotationSets = Criteria::table('view_annotationset')
            ->where('idSentence', $sentence->idSentence)
            ->whereNotNull('idLU')
            ->all();

        foreach ($annotationSets as $annotationSet) {
            $this->addAnnotationSetToXml($s, $annotationSet);
        }
    }

    /**
     * Add annotation set to sentence XML
     */
    private function addAnnotationSetToXml(SimpleXMLElement $sentenceElement, object $annotationSet): void
    {
        // Get LU information
        $lu = Criteria::table('lu')
            ->join('view_frame as f', 'lu.idFrame', '=', 'f.idFrame')
            ->where('idLU', $annotationSet->idLU)
            ->where('f.idLanguage', $this->idLanguage)
            ->select('lu.idLU', 'lu.name', 'lu.idFrame', 'f.name as frameName')
            ->first();

        if (! $lu) {
            return;
        }

        // Get target information
        $target = Criteria::table('view_annotation_text_gl')
            ->where('idAnnotationSet', $annotationSet->idAnnotationSet)
            ->where('name', 'Target')
            ->first();

        $aset = $sentenceElement->addChild('annotationSet');
        $aset->addAttribute('ID', $annotationSet->idAnnotationSet);
        $aset->addAttribute('luID', $lu->idLU);
        $aset->addAttribute('luName', $lu->name);
        $aset->addAttribute('frameID', $lu->idFrame);
        $aset->addAttribute('frameName', $lu->frameName);

        if ($target) {
            $aset->addAttribute('start', $target->startChar);
            $aset->addAttribute('end', $target->endChar);
        }

        // Add FE layer
        $this->addFELayer($aset, $annotationSet->idAnnotationSet);

        // Add other layers
        $this->addGenericLayers($aset, $annotationSet->idAnnotationSet);
    }

    /**
     * Add Frame Element layer
     */
    private function addFELayer(SimpleXMLElement $aset, int $idAnnotationSet): void
    {
        $fes = Criteria::table('view_annotation_text_fe as fe')
            ->join('view_instantiationtype as it', 'it.idInstantiationType', '=', 'fe.idInstantiationType')
            ->where('idAnnotationSet', $idAnnotationSet)
            ->where('it.idLanguage', $this->idLanguage)
            ->where('fe.idLanguage', $this->idLanguage)
            ->select('fe.idFrameElement', 'fe.name', 'fe.startChar', 'fe.endChar', 'it.name as itName')
            ->all();

        if (! empty($fes)) {
            $ly = $aset->addChild('layer');
            $ly->addAttribute('name', 'FE');

            foreach ($fes as $fe) {
                $lb = $ly->addChild('label');
                $lb->addAttribute('ID', $fe->idFrameElement);
                $lb->addAttribute('name', $fe->name);
                $lb->addAttribute('start', $fe->startChar);
                $lb->addAttribute('end', $fe->endChar);

                if ($fe->startChar == -1) {
                    $lb->addAttribute('itype', $fe->itName);
                }
            }
        }
    }

    /**
     * Add generic layers
     */
    private function addGenericLayers(SimpleXMLElement $aset, int $idAnnotationSet): void
    {
        $layerTypes = Criteria::table('view_layertype')
            ->where('idLanguage', $this->idLanguage)
            ->all();

        foreach ($layerTypes as $layerType) {
            $gls = Criteria::table('view_annotation_text_gl')
                ->where('idAnnotationSet', $idAnnotationSet)
                ->where('name', '<>', 'Target')
                ->where('layerTypeEntry', '=', $layerType->entry)
                ->all();

            if (! empty($gls)) {
                $ly = $aset->addChild('layer');
                $ly->addAttribute('name', $layerType->name);

                foreach ($gls as $gl) {
                    $lb = $ly->addChild('label');
                    $lb->addAttribute('ID', $gl->idGenericLabel);
                    $lb->addAttribute('name', $gl->name);
                    $lb->addAttribute('start', $gl->startChar);
                    $lb->addAttribute('end', $gl->endChar);
                }
            }
        }
    }

    /**
     * Create full text XML template
     */
    private function createFullTextXmlTemplate(object $corpus, object $document): string
    {
        // Use template manager instead of hardcoded template
        return XmlTemplateManager::getTemplate('fulltext');
    }

    /**
     * Create frame XML template
     */
    private function createFrameXmlTemplate(): string
    {
        return XmlTemplateManager::getTemplate('frames');
    }

    /**
     * Create lexical unit XML template
     */
    private function createLexicalUnitXmlTemplate(): string
    {
        return XmlTemplateManager::getTemplate('lexunit');
    }

    /**
     * Create corpus XML template
     */
    private function createCorpusXmlTemplate(): string
    {
        return XmlTemplateManager::getTemplate('corpus');
    }

    /**
     * Save XML file with optional XSD validation using config
     */
    private function saveXmlFile(SimpleXMLElement $sxe, string $filename, ?string $xsdFile = null): void
    {
        // Format XML for better readability
        $dom = XmlUtils::formatXml($sxe);

        // Add processing instruction if configured
        $templateConfig = $this->config['templates']['fulltext_header'] ?? [];
        if ($templateConfig['include_processing_instruction'] ?? false) {
            $stylesheet = $this->config['xml']['stylesheets']['fulltext'] ?? 'fullText.xsl';
            XmlUtils::addProcessingInstruction($dom, 'xml-stylesheet', "type=\"text/xsl\" href=\"{$stylesheet}\"");
        }

        // Validate against XSD if requested and configured
        if ($this->validateXsd && $xsdFile) {
            $xsdPath = $this->config['xsd_schemas'][$xsdFile] ?? $xsdFile;
            if (file_exists($xsdPath)) {
                $errors = XmlUtils::validateXml($dom, $xsdPath);
                if (! empty($errors)) {
                    $this->tracker->addWarning("XML validation failed for {$filename}", implode('; ', $errors));
                    if ($this->config['logging']['enabled'] ?? true) {
                        $this->logValidationErrors($filename, $errors);
                    }
                }
            } else {
                $this->tracker->addWarning("XSD schema file not found: {$xsdPath}");
            }
        }

        // Save the file
        file_put_contents($filename, $dom->saveXML());

        // Log successful export if configured
        if ($this->config['logging']['enabled'] ?? true) {
            $this->logExport($filename);
        }
    }

    /**
     * Generate filename using configured patterns
     */
    private function generateFilename(string $type, int $id): string
    {
        $patterns = $this->config['file_naming']['patterns'] ?? [];

        // Handle index files (where id = 0)
        if ($id === 0) {
            $indexPatterns = [
                'frameIndex' => 'frameIndex_{language}.xml',
                'frRelation' => 'frameRelations_{language}.xml',
                'fulltextIndex' => 'fulltextIndex_{language}.xml',
                'luIndex' => 'luIndex_{language}.xml',
                'semTypes' => 'semTypes_{language}.xml',
            ];
            $pattern = $indexPatterns[$type] ?? "{$type}_{language}.xml";
        } else {
            $pattern = $patterns[$type] ?? "{$type}_{id}_{language}.xml";
        }

        $languageCode = $this->config['languages'][$this->idLanguage]['code'] ?? 'unknown';
        $timestamp = date($this->config['file_naming']['date_format'] ?? 'Y-m-d_H-i-s');

        $filename = str_replace([
            '{type}',
            '{id}',
            '{language}',
            '{timestamp}',
            '{document_id}',
            '{frame_id}',
            '{lu_id}',
            '{corpus_id}',
        ], [
            $type,
            $id,
            $languageCode,
            $timestamp,
            $id, // fallback for document_id
            $id, // fallback for frame_id
            $id, // fallback for lu_id
            $id,  // fallback for corpus_id
        ], $pattern);

        // Use type-specific directory for individual files
        $outputDir = $this->getTypeOutputDirectory($type);

        return $outputDir.'/'.$filename;
    }

    /**
     * Get type-specific output directory
     */
    private function getTypeOutputDirectory(string $type): string
    {
        // Map export types to subdirectories
        $subdirectories = [
            'fulltext' => 'fulltext',
            'frames' => 'frames',
            'lexunit' => 'lu',
            'corpus' => 'corpus',
            // Index files go in the root exports directory
            'frameIndex' => '',
            'frRelation' => '',
            'fulltextIndex' => '',
            'luIndex' => '',
            'semTypes' => '',
        ];

        $subdir = $subdirectories[$type] ?? '';

        if ($subdir === '') {
            return $this->outputDir;
        }

        return $this->outputDir.'/'.$subdir;
    }

    /**
     * Ensure directory exists
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Add frame element to XML using configured mappings
     */
    private function addFrameElementToXml(\SimpleXMLElement $parent, object $fe): void
    {
        $feMapping = $this->config['field_mappings']['frame_element'] ?? [];

        $feElement = $parent->addChild('frameElement');
        $feElement->addAttribute('ID', $fe->{$feMapping['id'] ?? 'idFrameElement'});
        $feElement->addAttribute('name', $fe->{$feMapping['name'] ?? 'name'});
        $feElement->addAttribute('coreType', $fe->{$feMapping['core_type'] ?? 'coreType'});

        $descField = $feMapping['description'] ?? 'description';
        if (isset($fe->$descField) && $fe->$descField) {
            $feElement->addChild('description', XmlUtils::xmlEscape($fe->$descField));
        }
    }

    /**
     * Add lexical unit to XML using configured mappings
     */
    private function addLexicalUnitToXml(\SimpleXMLElement $parent, object $lu): void
    {
        $luMapping = $this->config['field_mappings']['lexical_unit'] ?? [];

        $luElement = $parent->addChild('lexicalUnit');
        $luElement->addAttribute('ID', $lu->{$luMapping['id'] ?? 'idLU'});
        $luElement->addAttribute('name', $lu->{$luMapping['name'] ?? 'name'});
        $luElement->addAttribute('POS', $lu->{$luMapping['pos'] ?? 'POS'} ?? '');

        $senseField = $luMapping['sense_description'] ?? 'senseDescription';
        if (isset($lu->$senseField) && $lu->$senseField) {
            $luElement->addChild('senseDescription', XmlUtils::xmlEscape($lu->$senseField));
        }
    }

    /**
     * Display export statistics
     */
    private function displayStatistics(array $stats): void
    {
        $this->info('Export Statistics:');
        $this->info("- Processed items: {$stats['processed']}");
        $this->info("- Files created: {$stats['files_created']}");
        $this->info("- Errors: {$stats['errors']}");
        $this->info("- Duration: {$stats['duration']} seconds");

        if ($stats['processed'] > 0) {
            $rate = round($stats['processed'] / max($stats['duration'], 1), 2);
            $this->info("- Processing rate: {$rate} items/second");
        }
    }

    /**
     * Log error to configured log file
     */
    private function logError(Exception $e): void
    {
        if (! ($this->config['logging']['enabled'] ?? true)) {
            return;
        }

        $logFile = $this->config['logging']['log_file'] ?? storage_path('logs/xml_export.log');
        $logDir = dirname($logFile);

        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $message = sprintf(
            "[%s] ERROR: %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log validation errors
     */
    private function logValidationErrors(string $filename, array $errors): void
    {
        $logFile = $this->config['logging']['log_file'] ?? storage_path('logs/xml_export.log');
        $message = sprintf(
            "[%s] VALIDATION ERRORS for %s:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $filename,
            implode("\n", $errors)
        );

        file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log successful export
     */
    private function logExport(string $filename): void
    {
        if (($this->config['logging']['level'] ?? 'info') === 'debug') {
            $logFile = $this->config['logging']['log_file'] ?? storage_path('logs/xml_export.log');
            $message = sprintf(
                "[%s] INFO: Successfully exported %s\n",
                date('Y-m-d H:i:s'),
                $filename
            );

            file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
        }
    }
}
