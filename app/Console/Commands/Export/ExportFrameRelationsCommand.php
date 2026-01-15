<?php

namespace App\Console\Commands\Export;

use App\Database\Criteria;
use App\Services\AppService;
use App\Services\ExportProgressTracker;
use App\Services\XmlExportConfig;
use App\Services\XmlUtils;
use DOMDocument;
use Exception;
use Illuminate\Console\Command;

class ExportFrameRelationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'export:frame-relations
                           {--frame= : Specific frame ID to export relations for}
                           {--relation-type= : Specific relation type to export}
                           {--language=2 : Language ID (default: 2)}
                           {--output=exports : Output directory}
                           {--format=individual : Output format (individual|grouped|single)}
                           {--validate : Validate against XSD}
                           {--include-hierarchy : Include frame hierarchy}';

    /**
     * The console command description.
     */
    protected $description = 'Export frame relations to XML files';

    private int $idLanguage;

    private string $outputDir;

    private string $format;

    private bool $validateXsd;

    private bool $includeHierarchy;

    private array $config;

    private ExportProgressTracker $tracker;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Load configuration
        $this->config = config('export_config', []);

        $this->idLanguage = (int) ($this->option('language') ?? $this->config['default_language'] ?? 2);
        $this->outputDir = $this->option('output') ?? $this->config['output_directory'] ?? 'exports';
        $this->format = $this->option('format') ?? 'individual';
        $this->validateXsd = $this->option('validate') ?? $this->config['validate_xsd'] ?? false;
        $this->includeHierarchy = $this->option('include-hierarchy') ?? ($this->config['filters']['relations']['include_hierarchy'] ?? false);

        AppService::setCurrentLanguage($this->idLanguage);
        $this->tracker = new ExportProgressTracker;

        // Set performance settings from config
        if (isset($this->config['performance']['memory_limit'])) {
            ini_set('memory_limit', $this->config['performance']['memory_limit']);
        }
        if (isset($this->config['performance']['max_execution_time'])) {
            set_time_limit($this->config['performance']['max_execution_time']);
        }

        // Create output directory
        if (! is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        $this->info('Starting frame relations export');
        $this->info("Language ID: {$this->idLanguage}");
        $this->info("Output format: {$this->format}");
        $this->info('XSD Validation: '.($this->validateXsd ? 'enabled' : 'disabled'));

        try {
            switch ($this->format) {
                case 'individual':
                    return $this->exportIndividualRelations();
                case 'grouped':
                    return $this->exportGroupedRelations();
                case 'single':
                    return $this->exportSingleFile();
                default:
                    $this->error("Unknown format: {$this->format}");

                    return 1;
            }
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
     * Export individual relation files
     */
    private function exportIndividualRelations(): int
    {
        $frames = $this->getFramesWithRelations();

        if (empty($frames)) {
            $this->warn('No frames with relations found');

            return 0;
        }

        $this->tracker->startStep('Exporting individual frame relations', count($frames));
        $progressBar = $this->output->createProgressBar(count($frames));
        $progressBar->start();

        foreach ($frames as $frame) {
            try {
                $this->exportFrameRelations($frame);
                $this->tracker->updateProgress("Frame {$frame->idFrame}");
                $progressBar->advance();
            } catch (Exception $e) {
                $this->tracker->addError("Failed to export frame {$frame->idFrame}: ".$e->getMessage());
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();
        $this->tracker->complete();

        $progress = $this->tracker->getProgress();
        $this->info("Exported {$progress['processed_items']} frame relation files");

        if (! empty($progress['errors'])) {
            $this->warn('Encountered '.count($progress['errors']).' errors');
        }

        return 0;
    }

    /**
     * Export relations grouped by type
     */
    private function exportGroupedRelations(): int
    {
        $relationTypes = $this->getRelationTypes();

        $this->tracker->startStep('Exporting grouped relations', count($relationTypes));
        $progressBar = $this->output->createProgressBar(count($relationTypes));
        $progressBar->start();

        foreach ($relationTypes as $relationType) {
            try {
                $this->exportRelationTypeGroup($relationType);
                $this->tracker->updateProgress("Relation type {$relationType->relationType}");
                $progressBar->advance();
            } catch (Exception $e) {
                $this->tracker->addError("Failed to export relation type {$relationType->relationType}: ".$e->getMessage());
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();
        $this->tracker->complete();

        return 0;
    }

    /**
     * Export all relations to a single file
     */
    private function exportSingleFile(): int
    {
        $this->tracker->startStep('Exporting single relations file', 1);

        try {
            $dom = XmlUtils::createXmlDocument('frameRelations');
            $root = $dom->documentElement;

            // Add metadata
            $metadata = $dom->createElement('metadata');
            $metadata->setAttribute('exportDate', date('c'));
            $metadata->setAttribute('language', $this->idLanguage);
            $metadata->setAttribute('includeHierarchy', $this->includeHierarchy ? 'true' : 'false');
            $root->appendChild($metadata);

            // Export by relation type
            $relationTypes = $this->getRelationTypes();

            foreach ($relationTypes as $relationType) {
                $this->addRelationTypeToDocument($dom, $relationType);
            }

            // Add frame hierarchy if requested
            if ($this->includeHierarchy) {
                $this->addFrameHierarchy($dom);
            }

            $filename = "{$this->outputDir}/frame_relations_all.xml";
            $this->saveXmlDocument($dom, $filename);

            $this->tracker->updateProgress('All relations', 1);
            $this->tracker->complete();

            $this->info("Exported all frame relations to: {$filename}");

            return 0;

        } catch (Exception $e) {
            $this->tracker->fail($e->getMessage());
            throw $e;
        }
    }

    /**
     * Get frames that have relations
     */
    private function getFramesWithRelations(): array
    {
        $relationView = $this->config['database_views']['frame_relations'] ?? 'view_frame_relation';
        $frameView = $this->config['database_views']['frames'] ?? 'view_frame';

        $query = Criteria::table("{$relationView} as fr")
            ->join("{$frameView} as f1", 'fr.f1IdFrame', '=', 'f1.idFrame')
            ->where('fr.idLanguage', $this->idLanguage)
            ->select('f1.*')
            ->distinct();

        if ($frameId = $this->option('frame')) {
            $query->where('f1.idFrame', $frameId);
        }

        if ($relationType = $this->option('relation-type')) {
            $query->where('fr.relationType', $relationType);
        } else {
            // Use configured relation types
            $allowedRelations = $this->config['filters']['relations']['relation_types'] ?? [];
            if (! empty($allowedRelations)) {
                $query->whereIn('fr.relationType', $allowedRelations);
            }
        }

        return $query->orderBy('f1.entry')->all();
    }

    /**
     * Get available relation types
     */
    private function getRelationTypes(): array
    {
        $relationTypeView = $this->config['database_views']['relation_types'] ?? 'view_relationtype';
        $relationView = $this->config['database_views']['relations'] ?? 'view_relation';

        $query = Criteria::table("{$relationTypeView} as rt")
            ->join("{$relationView} as r", 'rt.idRelationType', '=', 'r.idRelationType')
            ->where('rt.idLanguage', $this->idLanguage)
            ->select('rt.*')
            ->distinct();

        if ($relationType = $this->option('relation-type')) {
            $query->where('rt.entry', $relationType);
        } else {
            // Use configured relation types
            $allowedRelations = $this->config['filters']['relations']['relation_types'] ?? [
                'rel_causative_of',
                'rel_inchoative_of',
                'rel_inheritance',
                'rel_perspective_on',
                'rel_precedes',
                'rel_see_also',
                'rel_subframe',
                'rel_structure',
                'rel_using',
                'rel_metaphorical_projection',
            ];
            $query->whereIn('rt.entry', $allowedRelations);
        }

        return $query->orderBy('rt.entry')->all();
    }

    /**
     * Export relations for a specific frame
     */
    private function exportFrameRelations(object $frame): void
    {
        $dom = XmlUtils::createXmlDocument('frameRelations');
        $root = $dom->documentElement;

        // Add frame information
        $frameElement = $dom->createElement('frame');
        $frameElement->setAttribute('ID', $frame->idFrame);
        $frameElement->setAttribute('name', $frame->name);
        $root->appendChild($frameElement);

        // Get outgoing relations
        $outgoingRelations = $this->getFrameRelations($frame->idFrame, 'outgoing');
        if (! empty($outgoingRelations)) {
            $outgoingElement = $dom->createElement('outgoingRelations');
            $frameElement->appendChild($outgoingElement);

            foreach ($outgoingRelations as $relation) {
                $this->addRelationElement($dom, $outgoingElement, $relation, 'outgoing');
            }
        }

        // Get incoming relations
        $incomingRelations = $this->getFrameRelations($frame->idFrame, 'incoming');
        if (! empty($incomingRelations)) {
            $incomingElement = $dom->createElement('incomingRelations');
            $frameElement->appendChild($incomingElement);

            foreach ($incomingRelations as $relation) {
                $this->addRelationElement($dom, $incomingElement, $relation, 'incoming');
            }
        }

        $filename = "{$this->outputDir}/frame_relations_{$frame->idFrame}.xml";
        $this->saveXmlDocument($dom, $filename);
    }

    /**
     * Export relations grouped by type
     */
    private function exportRelationTypeGroup(object $relationType): void
    {
        $dom = XmlUtils::createXmlDocument('relationTypeGroup');
        $root = $dom->documentElement;

        // Add relation type information
        $typeElement = $dom->createElement('relationType');
        $typeElement->setAttribute('ID', $relationType->idRelationType);
        $typeElement->setAttribute('name', $relationType->entry);
        $typeElement->setAttribute('nameCanonical', $relationType->nameCanonical ?? '');
        $typeElement->setAttribute('nameDirect', $relationType->nameDirect ?? '');
        $typeElement->setAttribute('nameInverse', $relationType->nameInverse ?? '');

        if ($relationType->description) {
            $descElement = $dom->createElement('description', XmlUtils::xmlEscape($relationType->description));
            $typeElement->appendChild($descElement);
        }

        $root->appendChild($typeElement);

        // Get all relations of this type
        $relations = Criteria::table('view_frame_relation')
            ->where('idLanguage', $this->idLanguage)
            ->where('relationType', $relationType->entry)
            ->orderBy('f1Name', 'f2Name')
            ->all();

        $relationsElement = $dom->createElement('relations');
        $typeElement->appendChild($relationsElement);

        foreach ($relations as $relation) {
            $relationElement = $dom->createElement('relation');
            $relationElement->setAttribute('ID', $relation->idEntityRelation);

            // Source frame
            $sourceElement = $dom->createElement('sourceFrame');
            $sourceElement->setAttribute('ID', $relation->f1IdFrame);
            $sourceElement->setAttribute('name', $relation->f1Name);
            $relationElement->appendChild($sourceElement);

            // Target frame
            $targetElement = $dom->createElement('targetFrame');
            $targetElement->setAttribute('ID', $relation->f2IdFrame);
            $targetElement->setAttribute('name', $relation->f2Name);
            $relationElement->appendChild($targetElement);

            $relationsElement->appendChild($relationElement);
        }

        $filename = "{$this->outputDir}/relation_type_{$relationType->idRelationType}.xml";
        $this->saveXmlDocument($dom, $filename);
    }

    /**
     * Get frame relations (outgoing or incoming)
     */
    private function getFrameRelations(int $frameId, string $direction = 'outgoing'): array
    {
        $query = Criteria::table('view_frame_relation')
            ->where('idLanguage', $this->idLanguage);

        if ($direction === 'outgoing') {
            $query->where('f1IdFrame', $frameId);
        } else {
            $query->where('f2IdFrame', $frameId);
        }

        if ($relationType = $this->option('relation-type')) {
            $query->where('relationType', $relationType);
        }

        return $query->orderBy('relationType')->all();
    }

    /**
     * Add relation element to DOM
     */
    private function addRelationElement(DOMDocument $dom, \DOMElement $parent, object $relation, string $direction): void
    {
        $relationElement = $dom->createElement('relation');
        $relationElement->setAttribute('ID', $relation->idEntityRelation);
        $relationElement->setAttribute('type', $relation->relationType);
        $relationElement->setAttribute('direction', $direction);

        if ($direction === 'outgoing') {
            $relatedFrame = $dom->createElement('relatedFrame');
            $relatedFrame->setAttribute('ID', $relation->f2IdFrame);
            $relatedFrame->setAttribute('name', $relation->f2Name);
        } else {
            $relatedFrame = $dom->createElement('relatedFrame');
            $relatedFrame->setAttribute('ID', $relation->f1IdFrame);
            $relatedFrame->setAttribute('name', $relation->f1Name);
        }

        $relationElement->appendChild($relatedFrame);

        // Add relation properties
        if ($relation->nameCanonical) {
            $relationElement->setAttribute('nameCanonical', $relation->nameCanonical);
        }
        if ($relation->nameDirect) {
            $relationElement->setAttribute('nameDirect', $relation->nameDirect);
        }
        if ($relation->nameInverse) {
            $relationElement->setAttribute('nameInverse', $relation->nameInverse);
        }

        $parent->appendChild($relationElement);
    }

    /**
     * Add relation type to document
     */
    private function addRelationTypeToDocument(DOMDocument $dom, object $relationType): void
    {
        $root = $dom->documentElement;

        $typeElement = $dom->createElement('relationType');
        $typeElement->setAttribute('ID', $relationType->idRelationType);
        $typeElement->setAttribute('name', $relationType->entry);

        // Get relations of this type
        $relations = Criteria::table('view_frame_relation')
            ->where('idLanguage', $this->idLanguage)
            ->where('relationType', $relationType->entry)
            ->all();

        foreach ($relations as $relation) {
            $relationElement = $dom->createElement('relation');
            $relationElement->setAttribute('ID', $relation->idEntityRelation);
            $relationElement->setAttribute('sourceFrame', $relation->f1Name);
            $relationElement->setAttribute('sourceFrameID', $relation->f1IdFrame);
            $relationElement->setAttribute('targetFrame', $relation->f2Name);
            $relationElement->setAttribute('targetFrameID', $relation->f2IdFrame);

            $typeElement->appendChild($relationElement);
        }

        $root->appendChild($typeElement);
    }

    /**
     * Add frame hierarchy to document
     */
    private function addFrameHierarchy(DOMDocument $dom): void
    {
        $root = $dom->documentElement;
        $hierarchyElement = $dom->createElement('frameHierarchy');

        // Get inheritance relations to build hierarchy
        $inheritanceRelations = Criteria::table('view_frame_relation')
            ->where('idLanguage', $this->idLanguage)
            ->where('relationType', 'rel_inheritance')
            ->orderBy('f1Name')
            ->all();

        // Build hierarchy tree (simplified version)
        $hierarchy = [];
        foreach ($inheritanceRelations as $relation) {
            if (! isset($hierarchy[$relation->f2IdFrame])) {
                $hierarchy[$relation->f2IdFrame] = [
                    'name' => $relation->f2Name,
                    'children' => [],
                ];
            }
            $hierarchy[$relation->f2IdFrame]['children'][] = [
                'id' => $relation->f1IdFrame,
                'name' => $relation->f1Name,
            ];
        }

        // Add to XML
        foreach ($hierarchy as $parentId => $parentData) {
            $parentElement = $dom->createElement('parentFrame');
            $parentElement->setAttribute('ID', $parentId);
            $parentElement->setAttribute('name', $parentData['name']);

            foreach ($parentData['children'] as $child) {
                $childElement = $dom->createElement('childFrame');
                $childElement->setAttribute('ID', $child['id']);
                $childElement->setAttribute('name', $child['name']);
                $parentElement->appendChild($childElement);
            }

            $hierarchyElement->appendChild($parentElement);
        }

        $root->appendChild($hierarchyElement);
    }

    /**
     * Save XML document with validation using config
     */
    private function saveXmlDocument(DOMDocument $dom, string $filename): void
    {
        // Add XSL processing instruction if configured
        $templateConfig = $this->config['templates']['frame_export'] ?? [];
        if ($templateConfig['include_stylesheet'] ?? false) {
            $stylesheet = $this->config['xml']['stylesheets']['relations'] ?? 'frameRelations.xsl';
            XmlUtils::addProcessingInstruction($dom, 'xml-stylesheet', "type=\"text/xsl\" href=\"{$stylesheet}\"");
        }

        // Validate if requested and configured
        if ($this->validateXsd) {
            $xsdFile = XmlExportConfig::getXsdSchema('relations');
            if ($xsdFile) {
                $xsdPath = $this->config['xsd_schemas']['relations'] ?? $xsdFile;
                if (file_exists($xsdPath)) {
                    $errors = XmlUtils::validateXml($dom, $xsdPath);
                    if (! empty($errors)) {
                        $this->tracker->addWarning("Validation errors for {$filename}", implode('; ', $errors));
                        if ($this->config['logging']['enabled'] ?? true) {
                            $this->logValidationErrors($filename, $errors);
                        }
                    }
                } else {
                    $this->tracker->addWarning("XSD schema file not found: {$xsdPath}");
                }
            }
        }

        file_put_contents($filename, $dom->saveXML());

        // Log successful export if configured
        if ($this->config['logging']['enabled'] ?? true) {
            $this->logExport($filename);
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
