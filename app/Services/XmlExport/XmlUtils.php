<?php

namespace App\Services\XmlExport;

/**
 * XML utilities for formatting and validation
 */
class XmlUtils
{
    /**
     * Escape special XML characters
     */
    public static function xmlEscape(string $text): string
    {
        //return htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        return str_replace(['&', '<', '>', '"', "'"], ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'], $text);
    }

    /**
     * Create a properly formatted XML document
     */
    public static function createXmlDocument(string $rootElement, array $attributes = []): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;

        $root = $dom->createElement($rootElement);

        // Add namespace declarations
        $root->setAttribute('xmlns:fn', XmlExportConfig::XML_NAMESPACES['fn']);
        $root->setAttribute('xmlns', XmlExportConfig::XML_NAMESPACES['default']);

        // Add custom attributes
        foreach ($attributes as $name => $value) {
            $root->setAttribute($name, $value);
        }

        $dom->appendChild($root);

        return $dom;
    }

    /**
     * Validate XML against XSD schema
     */
    public static function validateXml(\DOMDocument $dom, string $xsdPath): array
    {
        $errors = [];

        if (!file_exists($xsdPath)) {
            return ["XSD schema file not found: {$xsdPath}"];
        }

        // Enable user error handling
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $isValid = $dom->schemaValidate($xsdPath);

        if (!$isValid) {
            $xmlErrors = libxml_get_errors();
            foreach ($xmlErrors as $error) {
                $errors[] = "Line {$error->line}: {$error->message}";
            }
            libxml_clear_errors();
        }

        return $errors;
    }

    /**
     * Convert SimpleXMLElement to formatted DOMDocument
     */
    public static function formatXml(\SimpleXMLElement $sxe): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($sxe->asXML());

        return $dom;
    }

    /**
     * Add processing instruction to XML document
     */
    public static function addProcessingInstruction(\DOMDocument $dom, string $target, string $data): void
    {
        $pi = $dom->createProcessingInstruction($target, $data);
        $dom->insertBefore($pi, $dom->documentElement);
    }

    /**
     * Create XML filename based on type and ID
     */
    public static function createFilename(string $type, int $id, string $extension = 'xml'): string
    {
        return sprintf('%s_%d.%s', $type, $id, $extension);
    }
}
