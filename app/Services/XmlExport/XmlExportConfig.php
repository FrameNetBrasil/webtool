<?php

namespace App\Services\XmlExport;

/**
 * Configuration class for XML exports
 */
class XmlExportConfig
{
    public const EXPORT_TYPES = [
        'fulltext' => 'Full Text Annotations',
        'frames' => 'Frame Definitions',
        'lexunit' => 'Lexical Units',
        'corpus' => 'Corpus Information',
        'frameIndex' => 'Frame Index',
        'frRelation' => 'Frame Relations',
        'fulltextIndex' => 'Fulltext Index',
        'luIndex' => 'Lexical Unit Index',
        'semTypes' => 'Semantic Types',
        'all' => 'All Export Types'
    ];

    public const XML_NAMESPACES = [
        'fn' => 'http://frame.net.ufjf.br',
        'default' => 'http://frame.net.ufjf.br'
    ];

    public const XSD_SCHEMAS = [
        'fulltext' => 'fullText.xsd',
        'frames' => 'frame.xsd',
        'lexunit' => 'lexUnit.xsd',
        'frameIndex' => 'frameIndex.xsd',
        'frRelation' => 'frameRelations.xsd',
        'fulltextIndex' => 'fulltextIndex.xsd',
        'luIndex' => 'luIndex.xsd',
        'semTypes' => 'semTypes.xsd',
    ];

    public const OUTPUT_FORMATS = [
        'individual' => 'Separate file per item',
        'grouped' => 'Group by type',
        'single' => 'Single consolidated file'
    ];

    /**
     * Get XML namespace declarations
     */
    public static function getNamespaceDeclarations(): string
    {
        return 'xmlns:fn="' . self::XML_NAMESPACES['fn'] . '" xmlns="' . self::XML_NAMESPACES['default'] . '"';
    }

    /**
     * Get XSD schema location for a given type
     */
    public static function getXsdSchema(string $type): ?string
    {
        return self::XSD_SCHEMAS[$type] ?? null;
    }

    /**
     * Get valid export types
     */
    public static function getExportTypes(): array
    {
        return array_keys(self::EXPORT_TYPES);
    }
}
