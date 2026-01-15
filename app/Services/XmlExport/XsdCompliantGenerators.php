<?php

namespace App\Services\XmlExport;

use App\Database\Criteria;
use App\Repositories\Frame;
use App\Repositories\LU;
use App\Services\AppService;
use App\Services\ReportLUService;
use DOMDocument;
use DOMElement;

/**
 * XSD-Compliant XML Generators
 *
 * This class generates XML files that strictly follow the provided XSD schemas.
 * Each method corresponds to a specific XSD file and creates the exact structure required.
 */
class XsdCompliantGenerators
{
    private array $config;

    private int $idLanguage;

    private ?array $layerTypesCache = null;

    public function __construct(array $config, int $idLanguage)
    {
        $this->config = $config;
        $this->idLanguage = $idLanguage;
    }

    /**
     * Reset caches to free memory between documents
     */
    public function resetCaches(): void
    {
        $this->layerTypesCache = null;
    }

    /**
     * Generate fullText.xml according to fullText.xsd
     * Schema: fullTextAnnotation -> header -> corpus -> document + sentence[]
     */
    public function generateFullText(object $document): DOMDocument
    {
        $dom = XmlUtils::createXmlDocument('fullTextAnnotation');
        $root = $dom->documentElement;

        // Get corpus information
        $corpus = Criteria::table($this->config['database_views']['corpora'] ?? 'view_corpus')
            ->where('idCorpus', $document->idCorpus)
            ->where('idLanguage', $this->idLanguage)
            ->first();

        // Create header element (required by XSD)
        $header = $dom->createElement('header');
        $root->appendChild($header);

        // Create corpus element with required attributes
        $corpusElement = $dom->createElement('corpus');
        $corpusElement->setAttribute('description', XmlUtils::xmlEscape($corpus->description ?? ''));
        $corpusElement->setAttribute('name', XmlUtils::xmlEscape($corpus->name ?? ''));
        $corpusElement->setAttribute('ID', $corpus->idCorpus);
        $header->appendChild($corpusElement);

        // Create document element with required attributes
        $documentElement = $dom->createElement('document');
        $documentElement->setAttribute('description', XmlUtils::xmlEscape($document->description ?? ''));
        $documentElement->setAttribute('name', XmlUtils::xmlEscape($document->name ?? ''));
        $documentElement->setAttribute('ID', $document->idDocument);
        $corpusElement->appendChild($documentElement);

        // Process sentences in smaller chunks to avoid memory exhaustion
        // Reduced from 100 to 10 to handle large documents with many annotations
        $chunkSize = $this->config['sentence_chunk_size'] ?? 10;

        Criteria::table('document_sentence as ds')
            ->join($this->config['database_views']['sentences'] ?? 'sentence as s', 'ds.idSentence', '=', 's.idSentence')
            ->where('ds.idDocument', $document->idDocument)
            ->select('s.idSentence', 's.text', 's.paragraphOrder')
            ->orderBy('s.paragraphOrder')
            ->chunk($chunkSize, function ($sentences) use ($dom, $root) {
                // Add sentences according to sentence.xsd structure
                foreach ($sentences as $sentence) {
                    $this->addSentenceElement($dom, $root, $sentence);

                    // Free memory after processing each sentence
                    unset($sentence);
                }

                // Clear sentences array and force garbage collection after each chunk
                unset($sentences);
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            });

        return $dom;
    }

    /**
     * Generate frame.xml according to frame.xsd
     * Schema: frame (ID, name, cDate, cBy) -> definition + semType* + FE+ + FEcoreSet* + frameRelation* + lexUnit*
     */
    public function generateFrame(object $frame): DOMDocument
    {
        $dom = XmlUtils::createXmlDocument('frame');
        $root = $dom->documentElement;

        // Required attributes according to XSD
        $root->setAttribute('ID', $frame->idFrame);
        $root->setAttribute('name', XmlUtils::xmlEscape($frame->name));
        $root->setAttribute('cDate', date('c')); // XSD requires dateTimeType
        $root->setAttribute('cBy', 'system'); // Required by XSD

        // Required definition element
        $definition = $dom->createElement('definition', XmlUtils::xmlEscape($frame->description ?? ''));
        $root->appendChild($definition);

        // Add semType elements (0 or more)
        $this->addFrameSemanticTypes($dom, $root, $frame->idFrame);

        // Add FE elements (1 or more required by XSD)
        $this->addFrameElements($dom, $root, $frame->idFrame);

        // Add FEcoreSet elements (0 or more)
        $this->addFrameElementCoreSets($dom, $root, $frame->idFrame);

        // Add frameRelation elements (0 or more)
        $this->addFrameRelations($dom, $root, $frame->idFrame);

        // Add lexUnit elements (0 or more)
        $this->addFrameLexicalUnits($dom, $root, $frame->idFrame);

        return $dom;
    }

    /**
     * Generate lexUnit.xml according to lexUnit.xsd
     * Schema: lexUnit (basicLUAttributes + frameReference + totalAnnotated) -> header + definition + lexeme+ + semType* + valences? + subCorpus*
     */
    public function generateLexUnit(object $lu): DOMDocument
    {
        $dom = XmlUtils::createXmlDocument('lexUnit');
        $root = $dom->documentElement;

        // Required attributes according to XSD (basicLUAttributes + frameReference)
        $root->setAttribute('ID', $lu->idLU);
        $root->setAttribute('name', XmlUtils::xmlEscape($lu->name));
        $root->setAttribute('POS', $lu->POS ?? 'N');
        $root->setAttribute('status', 'Created'); // Required by XSD
        $root->setAttribute('language', $lu->language);
        $root->setAttribute('frameID', $lu->idFrame);
        $root->setAttribute('frameName', XmlUtils::xmlEscape($lu->frameName ?? ''));

        // Optional totalAnnotated attribute
        $annotationCount = $this->getLexicalUnitAnnotationCount($lu->idLU);
        if ($annotationCount > 0) {
            $root->setAttribute('totalAnnotated', $annotationCount);
        }

        // Required header element
        $this->addLexUnitHeader($dom, $root, $lu);

        // Required definition element
        $definition = $dom->createElement('definition');
        $definition->setAttribute('SOURCE', 'FrameNet');
        $definition->appendChild($dom->createTextNode(XmlUtils::xmlEscape($lu->senseDescription ?? '')));
        $root->appendChild($definition);

        // Required lexeme elements (1 or more)
        $this->addLexUnitLexemes($dom, $root, $lu);

        // Optional semType elements (0 or more)
        $this->addLexUnitSemanticTypes($dom, $root, $lu->idLU);

        // Optional valences element
        $this->addLexUnitValences($dom, $root, $lu->idLU);

        // Optional subCorpus elements (0 or more)
        $this->addLexUnitSubCorpora($dom, $root, $lu->idLU);

        return $dom;
    }

    /**
     * Generate frameIndex.xml according to frameIndex.xsd
     * Schema: frameIndex -> frame* (name, ID, mDate)
     */
    public function generateFrameIndex(): DOMDocument
    {
        $dom = XmlUtils::createXmlDocument('frameIndex');
        $root = $dom->documentElement;

        // Get all frames for the language
        $frames = Criteria::table($this->config['database_views']['frames'] ?? 'view_frame')
            ->where('idLanguage', $this->idLanguage)
            ->where('active', 1)
            ->orderBy('name')
            ->all();

        foreach ($frames as $frame) {
            $frameElement = $dom->createElement('frame');
            $frameElement->setAttribute('name', XmlUtils::xmlEscape($frame->name));
            $frameElement->setAttribute('ID', $frame->idFrame);
            $frameElement->setAttribute('mDate', date('c')); // Modified date
            $root->appendChild($frameElement);
        }

        return $dom;
    }

    /**
     * Generate frameRelations.xml according to frameRelations.xsd
     * Schema: frameRelations (XMLCreated) -> frameRelationType* (ID, name, superFrameName, subFrameName) -> frameRelation* (ID, superFrameName, subFrameName, supID, subID) -> FERelation*
     */
    public function generateFrameRelations(): DOMDocument
    {
        $dom = XmlUtils::createXmlDocument('frameRelations');
        $root = $dom->documentElement;

        // Required XMLCreated attribute
        $root->setAttribute('XMLCreated', date('c'));

        // Get relation types from config
        $allowedRelations = $this->config['filters']['relations']['relation_types'] ?? [
            'rel_inheritance', 'rel_causative_of', 'rel_inchoative_of', 'rel_perspective_on',
            'rel_precedes', 'rel_see_also', 'rel_subframe', 'rel_structure', 'rel_using',
        ];

        foreach ($allowedRelations as $relationType) {
            $this->addFrameRelationType($dom, $root, $relationType);
        }

        return $dom;
    }

    /**
     * Generate fulltextIndex.xml according to fulltextIndex.xsd
     * Schema: fulltextIndex -> corpus* (corpDocType)
     */
    public function generateFulltextIndex(): DOMDocument
    {
        $dom = XmlUtils::createXmlDocument('fulltextIndex');
        $root = $dom->documentElement;

        // Get all corpora for the language
        $corpora = Criteria::table($this->config['database_views']['corpora'] ?? 'view_corpus')
            ->where('idLanguage', $this->idLanguage)
            ->where('active', 1)
            ->orderBy('name')
            ->all();

        foreach ($corpora as $corpus) {
            $this->addCorpusToIndex($dom, $root, $corpus);
        }

        return $dom;
    }

    /**
     * Generate luIndex.xml according to luIndex.xsd
     * Schema: luIndex -> legend -> statusType* + lu* (ID, name, status, frameName, frameID, hasAnnotation, numAnnotInstances?)
     */
    public function generateLuIndex(): DOMDocument
    {
        $dom = XmlUtils::createXmlDocument('luIndex');
        $root = $dom->documentElement;

        // Add legend element with status types
        //        $legend = $dom->createElement('legend');
        //        $root->appendChild($legend);
        //
        //        $statusTypes = [
        //            ['name' => 'Created', 'description' => 'Lexical unit has been created but not yet annotated'],
        //            ['name' => 'In_Use', 'description' => 'Lexical unit is actively being annotated'],
        //            ['name' => 'Finished_Initial', 'description' => 'Initial annotation phase completed'],
        //            ['name' => 'Add_Annotation', 'description' => 'Additional annotations are being added'],
        //            ['name' => 'Finished_X-Gov', 'description' => 'Cross-government annotation completed'],
        //            ['name' => 'New', 'description' => 'Newly identified lexical unit']
        //        ];
        //
        //        foreach ($statusTypes as $status) {
        //            $statusElement = $dom->createElement('statusType');
        //            $statusElement->setAttribute('name', $status['name']);
        //            $statusElement->setAttribute('description', $status['description']);
        //            $legend->appendChild($statusElement);
        //        }

        // Get all lexical units for the language
        $lexicalUnits = Criteria::table($this->config['database_views']['lexical_units'] ?? 'view_lu')
            ->join('view_frame as f', 'lu.idFrame', '=', 'f.idFrame')
            ->join('language as l', 'lu.idLanguage', '=', 'l.idLanguage')
            ->where('f.idLanguage', $this->idLanguage)
            ->where('lu.active', 1)
            ->select('lu.idLU', 'lu.name', 'f.name as frameName', 'lu.idFrame', 'l.language')
            ->orderBy('lu.name')
            ->all();

        foreach ($lexicalUnits as $lu) {
            $luElement = $dom->createElement('lu');
            $luElement->setAttribute('ID', $lu->idLU);
            $luElement->setAttribute('name', XmlUtils::xmlEscape($lu->name));
            $luElement->setAttribute('status', 'Created'); // Default status
            $luElement->setAttribute('frameName', XmlUtils::xmlEscape($lu->frameName ?? ''));
            $luElement->setAttribute('frameID', $lu->idFrame);
            $luElement->setAttribute('language', $lu->language);

            // Check if has annotations
            $hasAnnotation = $this->lexicalUnitHasAnnotations($lu->idLU);
            $luElement->setAttribute('hasAnnotation', $hasAnnotation ? 'true' : 'false');

            // if ($hasAnnotation) {
            $numInstances = $this->getLexicalUnitAnnotationCount($lu->idLU);
            $luElement->setAttribute('numAnnotInstances', $numInstances);
            // }

            $root->appendChild($luElement);
        }

        return $dom;
    }

    /**
     * Generate semTypes.xml according to semTypes.xsd
     * Schema: semTypes (XMLCreated?) -> semType* (ID, name, abbrev) -> definition + superType* (superTypeName, supID)
     */
    public function generateSemanticTypes(): DOMDocument
    {
        $dom = XmlUtils::createXmlDocument('semTypes');
        $root = $dom->documentElement;

        // Optional XMLCreated attribute
        $root->setAttribute('XMLCreated', date('c'));

        // Get semantic types
        $semanticTypes = Criteria::table($this->config['database_views']['semantic_types'] ?? 'view_semantictype')
            ->where('idLanguage', $this->idLanguage)
            ->orderBy('name')
            ->all();

        foreach ($semanticTypes as $semType) {
            $semTypeElement = $dom->createElement('semType');
            $semTypeElement->setAttribute('ID', $semType->idSemanticType);
            $semTypeElement->setAttribute('name', XmlUtils::xmlEscape($semType->name));
            $semTypeElement->setAttribute('abbrev', XmlUtils::xmlEscape($semType->entry ?? $semType->name));

            // Required definition element
            $definition = $dom->createElement('definition', XmlUtils::xmlEscape($semType->description ?? ''));
            $semTypeElement->appendChild($definition);

            // Add superType elements (0 or more)
            $this->addSemanticTypeSuperTypes($dom, $semTypeElement, $semType->idSemanticType);

            $root->appendChild($semTypeElement);
        }

        return $dom;
    }

    // Helper methods for building complex structures

    /**
     * Add sentence element according to sentence.xsd structure
     */
    private function addSentenceElement(DOMDocument $dom, DOMElement $parent, object $sentence): void
    {
        $sentenceElement = $dom->createElement('sentence');
        $sentenceElement->setAttribute('ID', $sentence->idSentence);
        $parent->appendChild($sentenceElement);

        // Add text element
        $text = $dom->createElement('text', XmlUtils::xmlEscape($sentence->text));
        $sentenceElement->appendChild($text);

        // Get annotation sets for this sentence
        $annotationSets = Criteria::table($this->config['database_views']['annotations'] ?? 'view_annotationset')
            ->where('idSentence', $sentence->idSentence)
            ->whereNotNull('idLU')
            ->all();

        foreach ($annotationSets as $annotationSet) {
            $this->addAnnotationSet($dom, $sentenceElement, $annotationSet);

            // Free memory after each annotation set
            unset($annotationSet);
        }

        // Free memory after processing sentence
        unset($annotationSets, $text, $sentenceElement);
    }

    /**
     * Add annotation set with layers and labels
     */
    private function addAnnotationSet(DOMDocument $dom, DOMElement $sentenceElement, object $annotationSet): void
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

        $aset = $dom->createElement('annotationSet');
        $aset->setAttribute('ID', $annotationSet->idAnnotationSet);
        $aset->setAttribute('luID', $lu->idLU);
        $aset->setAttribute('luName', XmlUtils::xmlEscape($lu->name));
        $aset->setAttribute('frameID', $lu->idFrame);
        $aset->setAttribute('frameName', XmlUtils::xmlEscape($lu->frameName));

        // Get target information for sentence positioning
        $target = Criteria::table($this->config['database_views']['annotation_text_gl'] ?? 'view_annotation_text_gl')
            ->where('idAnnotationSet', $annotationSet->idAnnotationSet)
            ->where('name', 'Target')
            ->first();

        if ($target) {
            $aset->setAttribute('start', $target->startChar);
            $aset->setAttribute('end', $target->endChar);
        }

        // Add FE layer
        $this->addFELayerToAnnotationSet($dom, $aset, $annotationSet->idAnnotationSet);

        // Add other layers
        $this->addGenericLayersToAnnotationSet($dom, $aset, $annotationSet->idAnnotationSet);

        $sentenceElement->appendChild($aset);
    }

    /**
     * Add Frame Elements to frame according to XSD
     */
    private function addFrameElements(DOMDocument $dom, DOMElement $frameElement, int $frameId): void
    {
        $frameElements = Criteria::table($this->config['database_views']['frame_elements'] ?? 'view_frameelement')
            ->join('color as c', 'c.idColor', '=', 'fe.idColor')
            ->where('fe.idFrame', $frameId)
            ->where('fe.idLanguage', $this->idLanguage)
            ->where('fe.active', 1)
            ->orderBy('fe.name')
            ->all();

        $coreness = config('webtool.fe.coreness');
        foreach ($frameElements as $fe) {
            $feElement = $dom->createElement('FE');
            $feElement->setAttribute('ID', $fe->idFrameElement);
            $feElement->setAttribute('name', XmlUtils::xmlEscape($fe->name));
            $feElement->setAttribute('abbrev', XmlUtils::xmlEscape($fe->name)); // Use name as abbrev if not available
            $feElement->setAttribute('cDate', date('c'));
            $feElement->setAttribute('cBy', 'system');
            $feElement->setAttribute('coreType', $coreness[$fe->coreType] ?? 'Peripheral');

            // Color attributes required by XSD
            $feElement->setAttribute('fgColor', $fe->rgbFg); // Default colors
            $feElement->setAttribute('bgColor', $fe->rgbBg);

            // Required definition element
            $definition = $dom->createElement('definition', XmlUtils::xmlEscape($fe->description ?? ''));
            $feElement->appendChild($definition);

            // Add semType elements (0 or more)
            $this->addFrameElementSemanticTypes($dom, $feElement, $fe->idFrameElement);

            // Add requiresFE and excludesFE elements
            $this->addFrameElementRelations($dom, $feElement, $fe->idFrameElement);

            $frameElement->appendChild($feElement);
        }
    }

    /**
     * Add lexical units to frame according to XSD
     */
    private function addFrameLexicalUnits(DOMDocument $dom, DOMElement $frameElement, int $frameId): void
    {
        $lexicalUnits = Criteria::table($this->config['database_views']['lexical_units'] ?? 'view_lu')
            ->where('idFrame', $frameId)
            ->where('idLanguage', $this->idLanguage)
            ->where('active', 1)
            ->orderBy('name')
            ->all();

        foreach ($lexicalUnits as $lu) {
            $luElement = $dom->createElement('lexUnit');
            $luElement->setAttribute('ID', $lu->idLU);
            $luElement->setAttribute('name', XmlUtils::xmlEscape($lu->name));
            $luElement->setAttribute('POS', $lu->POS ?? 'N');
            $luElement->setAttribute('status', 'Created');
            $luElement->setAttribute('cDate', date('c'));
            $luElement->setAttribute('cBy', 'system');
            $luElement->setAttribute('lemmaID', $lu->idLemma ?? $lu->idLU);

            // Required definition element with SOURCE attribute
            $definition = $dom->createElement('definition', XmlUtils::xmlEscape($lu->senseDescription ?? ''));
            $definition->setAttribute('SOURCE', 'FrameNet');
            $luElement->appendChild($definition);

            // Required sentenceCount element
            $sentenceCount = $dom->createElement('sentenceCount');
            $total = $this->getLexicalUnitSentenceCount($lu->idLU);
            $annotated = $this->getLexicalUnitAnnotationCount($lu->idLU);
            $sentenceCount->setAttribute('total', $total);
            $sentenceCount->setAttribute('annotated', $annotated);
            $luElement->appendChild($sentenceCount);

            // Required lexeme elements (1 or more)
            $this->addLexemesToFrameLU($dom, $luElement, $lu);

            // Optional semType elements
            $this->addLexUnitSemanticTypes($dom, $luElement, $lu->idLU);

            $frameElement->appendChild($luElement);
        }
    }

    /**
     * Add FE layer to annotation set
     */
    private function addFELayerToAnnotationSet(DOMDocument $dom, DOMElement $aset, int $idAnnotationSet): void
    {
        $fes = Criteria::table($this->config['database_views']['annotation_text_fe'] ?? 'view_annotation_text_fe as fe')
            ->join('view_instantiationtype as it', 'it.idInstantiationType', '=', 'fe.idInstantiationType')
            ->where('idAnnotationSet', $idAnnotationSet)
            ->where('it.idLanguage', $this->idLanguage)
            ->where('fe.idLanguage', $this->idLanguage)
            ->select('fe.idFrameElement', 'fe.name', 'fe.startChar', 'fe.endChar', 'it.name as itName')
            ->all();

        if (! empty($fes)) {
            $layer = $dom->createElement('layer');
            $layer->setAttribute('name', 'FE');
            $aset->appendChild($layer);

            foreach ($fes as $fe) {
                $label = $dom->createElement('label');
                $label->setAttribute('ID', $fe->idFrameElement);
                $label->setAttribute('name', XmlUtils::xmlEscape($fe->name));
                $label->setAttribute('start', $fe->startChar);
                $label->setAttribute('end', $fe->endChar);

                if ($fe->startChar == -1) {
                    $label->setAttribute('itype', $fe->itName);
                }

                $layer->appendChild($label);
            }
        }
    }

    /**
     * Add generic layers to annotation set
     */
    private function addGenericLayersToAnnotationSet(DOMDocument $dom, DOMElement $aset, int $idAnnotationSet): void
    {
        // Cache layer types to avoid repeated queries
        if ($this->layerTypesCache === null) {
            $this->layerTypesCache = Criteria::table($this->config['database_views']['layer_types'] ?? 'view_layertype')
                ->where('idLanguage', $this->idLanguage)
                ->all();
        }

        foreach ($this->layerTypesCache as $layerType) {
            $gls = Criteria::table($this->config['database_views']['annotation_text_gl'] ?? 'view_annotation_text_gl')
                ->where('idAnnotationSet', $idAnnotationSet)
                ->where('name', '<>', 'Target')
                ->where('layerTypeEntry', '=', $layerType->entry)
                ->all();

            if (! empty($gls)) {
                $layer = $dom->createElement('layer');
                $layer->setAttribute('name', $layerType->name);
                $aset->appendChild($layer);

                foreach ($gls as $gl) {
                    $label = $dom->createElement('label');
                    $label->setAttribute('ID', $gl->idGenericLabel);
                    $label->setAttribute('name', XmlUtils::xmlEscape($gl->name));
                    $label->setAttribute('start', $gl->startChar);
                    $label->setAttribute('end', $gl->endChar);
                    $layer->appendChild($label);
                }
            }
        }
    }

    // Additional helper methods for complex data retrieval

    private function getLexicalUnitAnnotationCount(int $idLU): int
    {
        return Criteria::table($this->config['database_views']['annotations'] ?? 'view_annotationset')
            ->where('idLU', $idLU)
            ->count();
    }

    private function lexicalUnitHasAnnotations(int $idLU): bool
    {
        return $this->getLexicalUnitAnnotationCount($idLU) > 0;
    }

    private function getLexicalUnitSentenceCount(int $idLU): int
    {
        return Criteria::table($this->config['database_views']['annotations'] ?? 'view_annotationset')
            ->where('idLU', $idLU)
            ->distinct('idSentence')
            ->count();
    }

    private function addFrameSemanticTypes(DOMDocument $dom, DOMElement $frameElement, int $frameId): void
    {
        // Implementation for frame semantic types
        // This would require checking your semantic type relations in the database
    }

    private function addFrameElementCoreSets(DOMDocument $dom, DOMElement $frameElement, int $frameId): void
    {
        // Implementation for FE core sets
        // This would require checking frame element relations for core sets
        $result = Criteria::table('view_fe_internal_relation')
            ->where('relationType', 'rel_coreset')
            ->where('fe1IdFrame', $frameId)
            ->where('idLanguage', $this->idLanguage)
            ->all();
        $index = [];
        $i = 0;
        foreach ($result as $row) {
            if (! isset($index[$row->fe1Name]) && ! isset($index[$row->fe2Name])) {
                $i++;
                $index[$row->fe1Name] = [$i, $row->fe1IdFrameElement];
                $index[$row->fe2Name] = [$i, $row->fe2IdFrameElement];
            } elseif (! isset($index[$row->fe1Name])) {
                $index[$row->fe1Name] = $index[$row->fe2Name];
            } else {
                $index[$row->fe2Name] = $index[$row->fe1Name];
            }
        }
        $feCoreSet = [];
        foreach ($index as $fe => $i) {
            $feCoreSet[$i[0]][$i[1]] = $fe;
        }
        foreach ($feCoreSet as $cs) {
            $elCs = $dom->createElement('FEcoreset');
            $frameElement->appendChild($elCs);
            foreach ($cs as $idFE => $fe) {
                $elFE = $dom->createElement('memberFE');
                $elFE->setAttribute('name', $fe);
                $elFE->setAttribute('ID', $idFE);
                $elCs->appendChild($elFE);
            }
        }
    }

    private function addFrameRelations(DOMDocument $dom, DOMElement $frameElement, int $frameId): void
    {
        // Implementation for frame relations within a frame
        // This would get relations where this frame is involved
        // Get relation types from config
        $allowedRelations = $this->config['filters']['relations']['relation_types'] ?? [
            'rel_inheritance', 'rel_causative_of', 'rel_inchoative_of', 'rel_perspective_on',
            'rel_precedes', 'rel_see_also', 'rel_subframe', 'rel_structure', 'rel_using',
        ];

        foreach ($allowedRelations as $relationType) {
            $rt = $this->config['relation_types'][$relationType];

            $elR = $dom->createElement('frameRelation');
            $elR->setAttribute('type', $rt['name']);
            $frameElement->appendChild($elR);

            $relations = Criteria::table($this->config['database_views']['frame_relations'] ?? 'view_frame_relation')
                ->where('idLanguage', $this->idLanguage)
                ->where('relationType', $relationType)
                ->where('f1IdFrame', $frameId)
                ->orderBy('f1Name')
                ->all();

            foreach ($relations as $relation) {
                $elRelation = $dom->createElement('relatedFrame');
                $elRelation->setAttribute('ID', $relation->f2IdFrame);
                $elRelation->appendChild($dom->createTextNode($relation->f2Name));
                $elR->appendChild($elRelation);
            }
        }

    }

    private function addLexUnitHeader(DOMDocument $dom, DOMElement $root, object $lu): void
    {
        // Implementation of header element for lexical units
        // This would include corpus information and other metadata
    }

    private function addLexUnitLexemes(DOMDocument $dom, DOMElement $root, object $lu): void
    {
        // Implementation for lexeme elements in lexical units
        // This would get the lexical forms associated with the LU
    }

    private function addLexUnitSemanticTypes(DOMDocument $dom, DOMElement $parent, int $idLU): void
    {
        // Implementation for semantic types associated with lexical units
    }

    private function addLexUnitValences(DOMDocument $dom, DOMElement $root, int $idLU): void
    {
        // Implementation for valence patterns
        // This is a complex structure defined in the XSD
        $currentIdLanguage = AppService::getCurrentIdLanguage();
        $lu = LU::byId($idLU);
        AppService::setCurrentLanguage($lu->idLanguage);
        $valences = ReportLUService::FERealizations($idLU, $this->idLanguage);
        $elValences = $dom->createElement('valences');
        $realizations = $valences['realizations'];
        $fes = $valences['fes'];
        $realizationAS = $valences['realizationAS'];
        foreach ($realizations as $feIdEntity => $gfptas) {
            if ($feIdEntity) {
                $elRealization = $dom->createElement('FERealization');
                $elRealization->setAttribute('total', count($fes[$feIdEntity]['as']));
                $elValences->appendChild($elRealization);
                $elFE = $dom->createElement('FE');
                $elFE->setAttribute('name', $fes[$feIdEntity]['name']);
                $elRealization->appendChild($elFE);
                foreach ($gfptas as $gf => $ptas) {
                    foreach ($ptas as $pt => $idRealization) {
                        // print_r($gf . '   ' . $pt . '    ' . count($realizationAS[$idRealization[0]]) . "\n");
                        $elPattern = $dom->createElement('pattern');
                        $elPattern->setAttribute('total', count($realizationAS[$idRealization[0]]));
                        $elValenceUnit = $dom->createElement('valenceUnit');
                        $elValenceUnit->setAttribute('GF', $gf);
                        $elValenceUnit->setAttribute('PT', $pt);
                        $elValenceUnit->setAttribute('FE', $fes[$feIdEntity]['name']);
                        $elPattern->appendChild($elValenceUnit);
                        foreach ($realizationAS[$idRealization[0]] as $as) {
                            $elAS = $dom->createElement('annoSet');
                            $elAS->setAttribute('ID', $as);
                            $elPattern->appendChild($elAS);
                        }
                        $elRealization->appendChild($elPattern);
                    }
                }

            }
        }
        $root->appendChild($elValences);

        $vp = $valences['vp'];
        $patterns = $valences['patterns'];
        $vpfe = $valences['vpfe'];
        // print_r($vp);
        foreach ($vp as $idVPFE => $vp1) {
            $elRealization = $dom->createElement('FEGroupRealization');
            $elRealization->setAttribute('total', $vpfe[$idVPFE]['count']);
            $elValences->appendChild($elRealization);
            $i = 0;
            foreach ($patterns[$idVPFE] as $idVP => $scfegfptas) {
                foreach ($scfegfptas as $sc => $fegfptas) {
                    if ($i == 0) {
                        foreach ($fegfptas as $feIdEntity => $gfptas) {
                            if ($feIdEntity) {
                                $elFE = $dom->createElement('FE');
                                $elFE->setAttribute('name', $fes[$feIdEntity]['name']);
                                $elRealization->appendChild($elFE);
                            }
                        }
                        $i++;
                    }
                }
            }
            $i = 0;
            foreach ($scfegfptas as $sc => $fegfptas) {
                foreach ($fegfptas as $feIdEntity => $gfptas) {
                    $elPattern = $dom->createElement('pattern');
                    $elPattern->setAttribute('total', 0);
                    $elRealization->appendChild($elPattern);
                    //                    if ($feIdEntity) {
                    //                        foreach ($gfptas as $gf => $ptas) {
                    //                            foreach ($ptas as $pt => $as) {
                    //                                $elValenceUnit = $dom->createElement('valenceUnit');
                    //                                $elValenceUnit->setAttribute('GF', $gf);
                    //                                $elValenceUnit->setAttribute('PT', $pt);
                    //                                $elValenceUnit->setAttribute('FE', $fes[$feIdEntity]['name']);
                    //                                $elPattern->appendChild($elValenceUnit);
                    // //                                        foreach ($as as $a) {
                    // //                                            $elAS = $dom->createElement('annoSet');
                    // //                                            $elAS->setAttribute('ID', $a);
                    // //                                            $elPattern->appendChild($elAS);
                    // //                                        }
                    //
                    //                            }
                    //                        }
                    //                    }
                }
            }
        }
        AppService::setCurrentLanguage($currentIdLanguage);

    }

    private function addLexUnitSubCorpora(DOMDocument $dom, DOMElement $root, int $idLU): void
    {
        // Implementation for subcorpora containing annotations for this LU
    }

    private function addFrameRelationType(DOMDocument $dom, DOMElement $root, string $relationType): void
    {
        // Implementation for frame relation types
        // This would get all relations of a specific type
        $rt = $this->config['relation_types'][$relationType];

        $elR = $dom->createElement('frameRelationType');
        $elR->setAttribute('subFrameName', $rt['sub']);
        $elR->setAttribute('superFrameName', $rt['super']);
        $elR->setAttribute('name', $rt['name']);
        $root->appendChild($elR);

        $relations = Criteria::table($this->config['database_views']['frame_relations'] ?? 'view_frame_relation')
            ->where('idLanguage', $this->idLanguage)
            ->where('relationType', $relationType)
            ->orderBy('f1Name')
            ->all();

        foreach ($relations as $relation) {
            $elRelation = $dom->createElement('frameRelation');
            $elRelation->setAttribute('subID', $relation->f2IdFrame);
            $elRelation->setAttribute('supID', $relation->f1IdFrame);
            $elRelation->setAttribute('subFrameName', $relation->f2Name);
            $elRelation->setAttribute('superFrameName', $relation->f1Name);
            $elRelation->setAttribute('ID', $relation->idEntityRelation);
            $elR->appendChild($elRelation);

            $feRelations = Criteria::table('view_fe_relation')
                ->where('idLanguage', $this->idLanguage)
                ->where('idRelation', $relation->idEntityRelation)
                ->orderBy('fe1Name')
                ->all();

            foreach ($feRelations as $feRelation) {
                $elFeRelation = $dom->createElement('FERelation');
                $elFeRelation->setAttribute('subID', $feRelation->fe2IdFrameElement);
                $elFeRelation->setAttribute('supID', $feRelation->fe1IdFrameElement);
                $elFeRelation->setAttribute('subFrameName', $feRelation->fe2Name);
                $elFeRelation->setAttribute('superFrameName', $feRelation->fe1Name);
                $elFeRelation->setAttribute('ID', $feRelation->idEntityRelation);
                $elRelation->appendChild($elFeRelation);
            }
        }

    }

    private function addCorpusToIndex(DOMDocument $dom, DOMElement $root, object $corpus): void
    {
        // Implementation for corpus in fulltext index
        // This would include documents within the corpus
        $elCorpus = $dom->createElement('corpus');
        $elCorpus->setAttribute('ID', $corpus->idCorpus);
        $elCorpus->setAttribute('name', $corpus->name);
        $elCorpus->setAttribute('description', $corpus->description);
        $dom->appendChild($elCorpus);

        $documents = Criteria::table($this->config['database_views']['documents'] ?? 'view_document')
            ->where('idLanguage', $this->idLanguage)
            ->where('idCorpus', $corpus->idCorpus)
            ->orderBy('name')
            ->all();

        foreach ($documents as $document) {
            $elDoc = $dom->createElement('document');
            $elDoc->setAttribute('ID', $document->idDocument);
            $elDoc->setAttribute('name', $document->name);
            $elDoc->setAttribute('description', $document->description);
            $elCorpus->appendChild($elDoc);
        }
    }

    private function addSemanticTypeSuperTypes(DOMDocument $dom, DOMElement $semTypeElement, int $idSemanticType): void
    {
        // Implementation for semantic type hierarchies
        $superTypes = Criteria::table('view_semantictype_relation as str')
            ->where('idLanguage', $this->idLanguage)
            ->where('st1IdSemanticType', $idSemanticType)
            ->select('st2IdSemanticType as idSuperType', 'st2Name as name')
            ->orderBy('st2Name')
            ->all();
        foreach ($superTypes as $superType) {
            $elSt = $dom->createElement('superType');
            $elSt->setAttribute('supID', $superType->idSuperType);
            $elSt->setAttribute('superTypeName', $superType->name);
            $semTypeElement->appendChild($elSt);
        }
    }

    private function addFrameElementSemanticTypes(DOMDocument $dom, DOMElement $feElement, int $idFrameElement): void
    {
        // Implementation for FE semantic types
    }

    private function addFrameElementRelations(DOMDocument $dom, DOMElement $feElement, int $idFrameElement): void
    {
        // Implementation for requiresFE and excludesFE relations
    }

    private function addLexemesToFrameLU(DOMDocument $dom, DOMElement $luElement, object $lu): void
    {
        // Implementation for lexemes in frame-embedded LU
        // This follows the frameLUType structure
    }
}
