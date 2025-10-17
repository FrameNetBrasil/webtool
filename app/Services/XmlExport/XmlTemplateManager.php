<?php

namespace App\Services\XmlExport;

/**
 * XML Template Manager for handling different export templates
 */
class XmlTemplateManager
{
    private static array $templates = [];

    /**
     * Register a template
     */
    public static function registerTemplate(string $type, string $template): void
    {
        self::$templates[$type] = $template;
    }

    /**
     * Get template for export type
     */
    public static function getTemplate(string $type): string
    {
        return self::$templates[$type] ?? self::getDefaultTemplate($type);
    }

    /**
     * Get default templates
     */
    private static function getDefaultTemplate(string $type): string
    {
        $namespaces = XmlExportConfig::getNamespaceDeclarations();

        switch ($type) {
            case 'fulltext':
                return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<?xml-stylesheet type="text/xsl" href="fullText.xsl"?>
<fullTextAnnotation {$namespaces}>
    <header>
        <corpus description="{{corpus_description}}" name="{{corpus_name}}" ID="{{corpus_id}}">
            <document description="{{document_description}}" name="{{document_name}}" ID="{{document_id}}"/>
        </corpus>
    </header>
</fullTextAnnotation>
XML;

            case 'frames':
                return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<frames {$namespaces}>
</frames>
XML;

            case 'lexunit':
                return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<lexicalUnits {$namespaces}>
</lexicalUnits>
XML;

            case 'corpus':
                return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<corpora {$namespaces}>
</corpora>
XML;

            case 'relations':
                return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<frameRelations {$namespaces}>
</frameRelations>
XML;

            case 'valence':
                return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<valencePatterns {$namespaces}>
</valencePatterns>
XML;

            default:
                return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<data {$namespaces}>
</data>
XML;
        }
    }

    /**
     * Replace template variables
     */
    public static function replaceVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{{$key}}}", XmlUtils::xmlEscape($value), $template);
        }
        return $template;
    }
}
