<?php

return [
    /*
    |--------------------------------------------------------------------------
    | XML Export Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the FrameNet XML export
    | system. Modify these settings according to your requirements.
    |
    */

    // Default language for exports (2 = English in your database)
    'default_language' => 2,

    // Output directory for export files
    'output_directory' => storage_path('app/exports'),

    // Batch size for large exports
    'batch_size' => 100,

    // Enable XSD validation by default
    'validate_xsd' => true,

    // XSD schema file locations
    'xsd_schemas' => [
        'fulltext' => resource_path('xsd/fullText.xsd'),
        'frames' => resource_path('xsd/frame.xsd'),
        'lexunit' => resource_path('xsd/lexUnit.xsd'),
        'frameIndex' => resource_path('xsd/frameIndex.xsd'),
        'frRelation' => resource_path('xsd/frameRelations.xsd'),
        'fulltextIndex' => resource_path('xsd/fulltextIndex.xsd'),
        'luIndex' => resource_path('xsd/luIndex.xsd'),
        'semTypes' => resource_path('xsd/semTypes.xsd'),
    ],

    // Export filters and defaults
    'filters' => [
        // Only export active items by default
        'active_only' => false,

        // Default corpus filter (null = all corpora)
        'default_corpus' => [136, 21, 99, 31, 4, 5, 6, 7, 8, 11, 48, 73, 66, 77, 87, 151, 78, 78, 79, 57, 63, 56, 64, 92, 44, 153, 157, 158, 201, 46, 65, 62, 61, 22, 82, 67, 55, 43],

        // Frame export filters
        'frames' => [
            'include_deprecated' => false,
            'include_fe_relations' => true,
            'include_lexical_units' => true,
        ],

        // Lexical unit export filters
        'lexical_units' => [
            'include_valence_patterns' => true,
            'include_subcategorization' => true,
            'min_frequency' => 0,
        ],

        // Full text export filters
        'fulltext' => [
            'include_target_annotations' => true,
            'include_fe_annotations' => true,
            'include_pos_annotations' => true,
            'include_gf_annotations' => true,
            'include_pt_annotations' => true,
        ],

        // Relation export filters
        'relations' => [
            'include_hierarchy' => true,
            'include_frame_relations' => true,
            'include_fe_relations' => true,
            'relation_types' => [
                'rel_inheritance',
                'rel_causative_of',
                'rel_inchoative_of',
                'rel_perspective_on',
                'rel_precedes',
                'rel_see_also',
                'rel_subframe',
                'rel_using',
                'rel_metaphorical_projection',
                //                'rel_coreset',
                //                'rel_excludes',
                //                'rel_requires',
            ],
        ],
    ],

    'relation_types' => [
        'rel_inheritance' => [
            'name' => 'Inheritance',
            'super' => 'Parent',
            'sub' => 'Child',
        ],
        'rel_causative_of' => [
            'name' => 'Causative_of',
            'super' => 'Causative',
            'sub' => 'Inchoative/Stative',
        ],
        'rel_inchoative_of' => [
            'name' => 'Inchoative_of',
            'super' => 'Inchoative',
            'sub' => 'Stative',
        ],
        'rel_perspective_on' => [
            'name' => 'Perspective_on',
            'super' => 'Neutral',
            'sub' => 'Perspectivized',
        ],
        'rel_precedes' => [
            'name' => 'Precedes',
            'super' => 'Earlier',
            'sub' => 'Later',
        ],
        'rel_see_also' => [
            'name' => 'See_also',
            'super' => 'MainEntry',
            'sub' => 'RefferingEntry',
        ],
        'rel_subframe' => [
            'name' => 'Subframe',
            'super' => 'Complex',
            'sub' => 'Component',
        ],
        'rel_using' => [
            'name' => 'Using',
            'super' => 'Parent',
            'sub' => 'Child',
        ],
        'rel_metaphorical_projection' => [
            'name' => 'Metaphor',
            'super' => 'Parent',
            'sub' => 'Child',
        ],
    ],

    // Export format options
    'formats' => [
        'individual' => [
            'description' => 'Separate file per item',
            'supports' => ['fulltext', 'frames', 'lexunit'],
        ],
        'grouped' => [
            'description' => 'Group by type/category',
            'supports' => ['frames', 'lexunit'],
        ],
        'single' => [
            'description' => 'Single consolidated file',
            'supports' => ['frameIndex', 'frRelation', 'fulltextIndex', 'luIndex', 'semTypes'],
        ],
    ],

    // XML namespaces and schema locations
    'xml' => [
        'namespaces' => [
            'fn' => 'http://frame.net.ufjf.br',
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        ],
        'schema_locations' => [
            'fulltext' => 'http://frame.net.ufjf.br fullText.xsd',
            'frames' => 'http://frame.net.ufjf.br frame.xsd',
            'lexunit' => 'http://frame.net.ufjf.br lexicalUnit.xsd',
        ],
        'stylesheets' => [
            'fulltext' => 'fullText.xsl',
            'frames' => 'frame.xsl',
            'lexunit' => 'lexUnit.xsl',
            'frameIndex' => 'frameIndex.xsl',
            'frRelation' => 'frameRelations.xsl',
            'fulltextIndex' => 'fulltextIndex.xsl',
            'luIndex' => 'luIndex.xsl',
            'semTypes' => 'semTypes.xsl',
        ],
    ],

    // Performance settings
    'performance' => [
        'memory_limit' => '2G', // Increased for large exports
        'max_execution_time' => 0, // 0 = no limit
        'chunk_size' => 1000, // For large result sets
        'enable_gc' => true, // Enable garbage collection
    ],

    // Sentence processing chunk size (for memory optimization in fulltext exports)
    // Smaller values use less memory but may be slower
    'sentence_chunk_size' => 10,

    // Logging configuration
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'log_file' => storage_path('logs/xml_export.log'),
        'log_queries' => false, // Log SQL queries (debug only)
    ],

    // Database view mappings for different export types
    'database_views' => [
        'documents' => 'view_document',
        'sentences' => 'view_sentence as s',
        'frames' => 'view_frame',
        'frame_elements' => 'view_frameelement as fe',
        'lexical_units' => 'view_lu as lu',
        'corpora' => 'view_corpus',
        'annotations' => 'view_annotationset',
        'frame_relations' => 'view_frame_relation',
        'fe_relations' => 'view_fe_relation',
        'valence_patterns' => 'view_valencepattern',
        'annotation_text_fe' => 'view_annotation_text_fe as fe',
        'annotation_text_gl' => 'view_annotation_text_gl',
        'instantiation_types' => 'view_instantiationtype',
        'layer_types' => 'view_layertype',
        'semantic_types' => 'view_semantictype',
        'relation_types' => 'view_relationtype',
        'relations' => 'view_relation',
    ],

    // Custom field mappings for XML attributes
    'field_mappings' => [
        'frame' => [
            'id' => 'idFrame',
            'name' => 'name',
            'description' => 'description',
            'active' => 'active',
        ],
        'frame_element' => [
            'id' => 'idFrameElement',
            'name' => 'name',
            'core_type' => 'coreType',
            'description' => 'description',
        ],
        'lexical_unit' => [
            'id' => 'idLU',
            'name' => 'name',
            'frame_id' => 'idFrame',
            'frame_name' => 'frameName',
            'sense_description' => 'senseDescription',
            'pos' => 'POS',
        ],
        'corpus' => [
            'id' => 'idCorpus',
            'name' => 'name',
            'description' => 'description',
        ],
        'document' => [
            'id' => 'idDocument',
            'name' => 'name',
            'description' => 'description',
            'author' => 'author',
        ],
        'sentence' => [
            'id' => 'idSentence',
            'text' => 'text',
            'paragraph_order' => 'paragraphOrder',
        ],
    ],

    // Export templates (can be overridden)
    'templates' => [
        'fulltext_header' => [
            'include_processing_instruction' => true,
            'include_stylesheet' => true,
            'include_corpus_info' => true,
            'include_document_info' => true,
        ],
        'frame_export' => [
            'include_frame_elements' => true,
            'include_lexical_units' => true,
            'include_relations' => false, // Can be heavy
            'include_valence_info' => false,
        ],
        'lexunit_export' => [
            'include_frame_info' => true,
            'include_valence_patterns' => true,
            'include_annotation_examples' => false,
            'max_examples' => 10,
        ],
    ],

    // Validation rules
    'validation' => [
        'required_fields' => [
            'frame' => ['idFrame', 'name'],
            'frame_element' => ['idFrameElement', 'name', 'coreType'],
            'lexical_unit' => ['idLU', 'name', 'idFrame'],
            'sentence' => ['idSentence', 'text'],
        ],
        'max_text_length' => 10000, // Maximum text field length
        'allow_empty_descriptions' => true,
        'validate_references' => true, // Validate foreign key references
    ],

    // Progress tracking
    'progress' => [
        'enabled' => true,
        'update_frequency' => 10, // Update progress every N items
        'store_in_cache' => true,
        'cache_key_prefix' => 'xml_export_progress_',
        'cache_ttl' => 3600, // 1 hour
    ],

    // File naming conventions
    'file_naming' => [
        'date_format' => 'Y-m-d_H-i-s',
        'include_language' => true,
        'include_timestamp' => false,
        'patterns' => [
            'fulltext' => 'fulltext_{document_id}_{language}.xml',
            'frames' => 'frame_{frame_id}_{language}.xml',
            'lexunit' => 'lu_{lu_id}_{language}.xml',
            'corpus' => 'corpus_{corpus_id}_{language}.xml',
            'frameIndex' => 'frameIndex_{language}.xml',
            'frRelation' => 'frRelation_{language}.xml',
            'fulltextIndex' => 'fulltextIndex_{language}.xml',
            'luIndex' => 'luIndex_{language}.xml',
            'semTypes' => 'semTypes_{language}.xml',
            'grouped' => '{type}_grouped_{language}.xml',
            'single' => 'framenet_export_{language}_{timestamp}.xml',
        ],
    ],

    // Error handling
    'error_handling' => [
        'continue_on_error' => true,
        'max_errors' => 100, // Stop export after N errors
        'log_sql_errors' => true,
        'include_error_summary' => true,
        'error_file_suffix' => '_errors.log',
    ],

    // Export metadata
    'metadata' => [
        'include_export_info' => true,
        'include_schema_version' => true,
        'include_database_version' => true,
        'include_timestamp' => true,
        'include_language_info' => true,
        'include_statistics' => true,
        'creator' => 'FrameNet XML Export Tool',
        'version' => '1.0.0',
    ],

    // Language-specific settings
    'languages' => [
        1 => [
            'code' => 'pt',
            'name' => 'Portuguese',
            'direction' => 'ltr',
        ],
        2 => [
            'code' => 'en',
            'name' => 'English',
            'direction' => 'ltr',
        ],
        // Add more languages as needed
    ],

    // Custom export types (extensible)
    'custom_exports' => [
        'semantic_types' => [
            'enabled' => true,
            'view' => 'view_semantictype',
            'template' => 'semanticTypes',
            'filename_pattern' => 'semantic_types_{language}.xml',
        ],
        'concepts' => [
            'enabled' => true,
            'view' => 'view_concept',
            'template' => 'concepts',
            'filename_pattern' => 'concepts_{language}.xml',
        ],
        'construction_elements' => [
            'enabled' => false,
            'view' => 'view_constructionelement',
            'template' => 'constructionElements',
            'filename_pattern' => 'construction_elements_{language}.xml',
        ],
    ],

    // Memory optimization settings
    'optimization' => [
        'use_streaming' => false, // For very large exports
        'clear_query_cache' => true,
        'optimize_joins' => true,
        'prefetch_related' => false,
        'use_raw_queries' => false, // Use raw SQL for performance
    ],
];
