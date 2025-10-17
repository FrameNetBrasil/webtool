<?php

return [
    'db' => env('DB_CONNECTION', 'fnbr'),
    'logSQL' => env('LOG_SQL'),
    'lang' => 1,
    'language' => 'pt',
    'defaultIdLanguage' => 1,
    'defaultPassword' => 'default',
    'pageTitle' => env('APP_TITLE'),
    'mainTitle' => 'FrameNet Brasil Webtool 4.2',
    'headerTitle' => 'Webtool',
    'footer' => '&copy; 2014-2025 FrameNet Brasil Lab, UFJF.',
    'version' => 'v.4.2',
    'mediaURL' => env('APP_MEDIA_URL'),
    'login' => [
        'handler' => env('APP_AUTH'),
        'AUTH0_CLIENT_ID' => env('AUTH0_CLIENT_ID'),
        'AUTH0_CLIENT_SECRET' => env('AUTH0_CLIENT_SECRET'),
        'AUTH0_COOKIE_SECRET' => env('AUTH0_COOKIE_SECRET'),
        'AUTH0_DOMAIN' => env('AUTH0_DOMAIN'),
        'AUTH0_CALLBACK_URL' => env('AUTH0_CALLBACK_URL'),
        'AUTH0_BASE_URL' => env('AUTH0_BASE_URL'),
    ],
    'actions' => [
        'report' => ['Report', '/report', '', [
            'reportframe' => ['Frame', '/report/frame', '', []],
            'reportlu' => ['LU', '/report/lu', '', []],
            'networkstructure' => ['Network', '/network', 'MASTER', []],
            'reportst' => ['SemanticType', '/report/semanticType', '', []],
            'reportc5' => ['MoCCA', '/report/c5', '', []],
            'reporttqr' => ['TQR', '/report/qualia', '', []],
            'cxnreport' => ['Constructions', '/report/cxn', '', []],
            'dashboard' => ['Dashboard', '/dashboard', '', []],
//            'multimodalreport' => ['Multimodal', '/report/multimodal', '', []],
//            'corpusAnnotationReport' => ['Corpus Panes', '/corpus/report', 'corpusreport', '', 1, []],
        ]],
        'grapher' => ['Grapher', '/grapher', '', [
            'framegrapher' => ['Frames', '/grapher/frame', '', []],
            'domaingrapher' => ['Domain', '/grapher/domain', '', []],
            'scenariographer' => ['Scenario', '/grapher/scenario', '', []],
//            'fullgrapher' => ['Frames & CxN', '/grapher', 'fullgrapher', '', '', []],
//            'domaingrapher' => ['Frames by Domain', '/domain/grapher', 'domaingrapher', '', '', []],
//            'ccngrapher' => ['Constructicon', '/ccn/grapher', 'ccngrapher', '', '', []],
        ]],
        'annotation' => ['Annotation', '/annotation', 'MASTER', [
//            'lexicalAnnotation' => ['Frame Mode', '/lexicalAnnotation', 'lexicalAnnotation', '', 1, []],
//            'cnxAnnotation' => ['Construction Mode', '/constructionalAnnotation', 'cxnAnnotation', '', 1, []],
            //'corpusAnnotation' => ['Corpus Mode', '/annotation/corpus', 'MASTER', []],
//            'staticFrameMode1' => ['Static Frame Mode 1', '/annotation/staticFrameMode1', 'MASTER', []],
//            'staticFrameMode2' => ['Static Frame Mode 2', '/annotation/staticFrameMode2', 'MASTER', []],
            'staticEvent' => ['Static Event', '/annotation/staticEvent', 'MASTER', []],
            'fe' => ['FE', '/annotation/fe', 'MASTER', []],
            'dynamicMode' => ['Dynamic Mode', '/annotation/dynamicMode', 'MASTER', []],
            'fullText' => ['Full text', '/annotation/fullText', 'MASTER', []],
            'staticBBox' => ['Static BBox', '/annotation/staticBBox', 'MASTER', []],
            'deixis' => ['Deixis', '/annotation/deixis', 'MASTER', []],
            'annotationSets' => ['AnnotationSets', '/annotation/as', 'MANAGER', []],
//            'layers' => ['Manage Layers', '/layer/formManager', 'fa fa-list fa16px', 'JUNIOR', 1, []],
        ]],
        'structure' => ['Structure', '/structure', 'MASTER', [
            'framestructure' => ['Frame', '/frame', 'MASTER', []],
//            'corpusstructure' => ['Corpus', '/corpus', 'MASTER', []],
//            'lexiconstructure' => ['Lexicon', '/lexicon', 'MASTER', []],
            'lexicon3structure' => ['Lexicon-3', '/lexicon3', 'MASTER', []],
            //'sentence' => ['Sentence', '/sentence', 'MASTER', []],
            'lucandidate' => ['LU candidate', '/luCandidate', 'MASTER', []],
//            'tqr2structure' => ['TQR2', '/tqr2', 'MASTER', []],
            'cxnstructure' => ['Constructicon', '/cxn', 'MASTER', []],
//            'lemmastructure' => ['Lemma', '/lemma', 'MASTER', []],
//            'lexemestructure' => ['Lexeme', '/lexeme', 'MASTER', []],
//            'qualia' => ['Qualia', '/qualia', 'menu-qualia', 'MASTER', 1, []],
//            'constrainttype' => ['Constraint Type', '/constrainttype', 'menu-constraint', 'MASTER', 1, []],
//            'conceptstructure' => ['Concept', '/concept', 'menu-concept', '', 1, []],
//            'decisiontree' => ['Decision tree', '/decisiontree', 'MASTER', []],
        ]],
        'manager' => ['Manager', '/manager', 'MANAGER', [
            'projectDataset' => ['Project/Dataset', '/project', 'MANAGER', []],
            'taskUser' => ['Task/User', '/task', 'MANAGER', []],
            'reframing' => ['Reframing', '/reframing', 'MANAGER', []],
        ]],
        'admin' => ['Admin', '/admin', 'ADMIN', [
            'groupUser' => ['Group/User', '/user', 'ADMIN', []],
            'corpusDocument' => ['Corpus/Document', '/corpus', 'ADMIN', []],
            'videoDocument' => ['Video/Document', '/video', 'ADMIN', []],
            'imageDocument' => ['Image/Document', '/image', 'ADMIN', []],
            'domainSemantictype' => ['Domain/SemanticType', '/semanticType', 'ADMIN', []],
            'layerGenericlabel' => ['Layer/GenericLabel', '/layers', 'ADMIN', []],
            'relations' => ['Relations', '/relations', 'ADMIN', []],
//            'type' => ['Types', '/type', 'ADMIN', []],
//            'genre' => ['Genres', '/genre', 'ADMIN', []],
//            'layer' => ['Layers', '/layer', 'ADMIN', []],
//            'constraint' => ['Constraints', '/constraint', 'ADMIN', []],
        ]],
//        'editor' => ['Editor', 'main/visualeditor', 'edit', 'MASTER', 1, [
//            'frameeditor' => ['Frame Relation', '/visualeditor/frame/main', 'fa fa-list-alt fa16px', 'MASTER', 1, []],
//            'corenesseditor' => ['Coreness', '/visualeditor/frame/coreness', 'fa fa-th-list fa16px', 'MASTER', 1, []],
//            'cxneditor' => ['CxN Relation', '/visualeditor/cxn/main', 'fa fa-list-alt fa16px', 'MASTER', 1, []],
//            'cxnframeeditor' => ['CxN-Frame Relation', '/visualeditor/cxnframe/main', 'fa fa-list-alt fa16px', 'MASTER', 1, []],
//        ]],
        'utils' => ['Utils', '/utils', 'ADMIN', [
//            'importLexWf' => ['Import Wf-Lexeme', '/utils/importLexWf', 'utilimport', 'MASTER', 1, []],
//            'wflex' => ['Search Wf-Lexeme', '/admin/wflex', 'utilwflex', '', 1, []],
//            'registerWfLex' => ['Register Wf-Lexeme', '/utils/registerLexWf', 'registerwflex', 'MASTER', 1, []],
//            'registerLemma' => ['Register Lemma', '/utils/registerLemma', 'registerlemma', 'MASTER', 1, []],
            'importFullText' => ['Import FullText', '/utils/importFullText', 'MASTER', []],
//            'exportCxnFS' => ['Export Cxn as FS', '/utils/exportCxnFS', 'exportcxnfs', 'ADMIN', 1, []],
//            'exportCxnJson' => ['Export Cxn', '/utils/exportCxn', 'exportcxnjson', 'ADMIN', 1, []],
        ]],
    ],
    'languages' => ['pt','en'],
    'user' => ['userPanel', '/admin/user/main', '', [
        'language' => ['Language', '/language', '', [
            '2' => ['English', '/changeLanguage/en', '', []],
            '1' => ['Portuguese', '/changeLanguage/pt', '', []],
            '3' => ['Spanish', '/changeLanguage/es', '', []],
        ]],
        'profile' => ['Profile', '/profile', '', [
            'myprofile' => ['My Profile', '/profile', '', []],
            'logout' => ['Logout', '/logout', '', []],
        ]],
    ]],
    'relations' => [
        'rel_inheritance' => [
            'direct' => "Is inherited by",
            'inverse' => "Inherits from",
            'color' => '#FF0000'
        ],
        'rel_subframe' => [
            'direct' => "Has as subframe",
            'inverse' => "Is subframe of",
            'color' => '#0000FF'
        ],
        'rel_perspective_on' => [
            'direct' => "Is perspectivized in",
            'inverse' => "Perspective on",
            'color' => '#fdbeca'
        ],
        'rel_using' => [
            'direct' => "Is used by",
            'inverse' => "Uses",
            'color' => '#006301'
        ],
        'rel_precedes' => [
            'direct' => "Precedes",
            'inverse' => "Is preceded by",
            'color' => '#000000'
        ],
        'rel_causative_of' => [
            'direct' => "Is causative of",
            'inverse' => "Has as causative",
            'color' => '#fdd101'
        ],
        'rel_inchoative_of' => [
            'direct' => "Is inchoative of",
            'inverse' => "Has as inchoative",
            'color' => '#897201'
        ],
        'rel_see_also' => [
            'direct' => "See also",
            'inverse' => "Has as see_also",
            'color' => '#9e1fee'
        ],
        'rel_inheritance_cxn' => [
            'direct' => "Is inherited by",
            'inverse' => "Inherits from",
            'color' => '#FF0000'
        ],
        'rel_daughter_of' => [
            'direct' => "Is daughter of",
            'inverse' => "Has as daughter",
            'color' => '#0000FF'
        ],
        'rel_subtypeof' => [
            'direct' => "Is subtype of",
            'inverse' => "Has as subtype",
            'color' => '#9e1fee'
        ],
        'rel_standsfor' => [
            'direct' => "Stands for",
            'inverse' => "Has as stands_for",
            'color' => '#9e1fee'
        ],
        'rel_partwhole' => [
            'direct' => "Part of",
            'inverse' => "Has as part",
            'color' => '#9e1fee'
        ],
        'rel_hasconcept' => [
            'direct' => "Has concept",
            'inverse' => "Is concept of",
            'color' => '#9e1fee'
        ],
        'rel_coreset' => [
            'direct' => "CoreSet",
            'inverse' => "CoreSet",
            'color' => '##000'
        ],
        'rel_excludes' => [
            'direct' => "Excludes",
            'inverse' => "Excludes",
            'color' => '#000'
        ],
        'rel_requires' => [
            'direct' => "Requires",
            'inverse' => "Requires",
            'color' => '#000'
        ],
        'rel_structure' => [
            'direct' => "Structure",
            'inverse' => "Structured by",
            'color' => '#000'
        ],
    ],
    'fe' => [
        'icon' => [
            "cty_core" => "black circle",
            "cty_core-unexpressed" => "black dot circle",
            "cty_peripheral" => "black stop circle outline",
            "cty_extra-thematic" => "black circle outline",
        ],
        'coreness' => [
            "cty_core" => "Core",
            "cty_core-unexpressed" => "Core-Unexpressed",
            "cty_peripheral" => "Peripheral",
            "cty_extra-thematic" => "Extra-thematic",
        ]
    ],
    'neo4j' => [
        'enabled' => env('NEO4J_ENABLED', false),
        'auto_sync' => env('NEO4J_AUTO_SYNC', false),
        'batch_size' => env('NEO4J_BATCH_SIZE', 100),
        'retry_attempts' => env('NEO4J_RETRY_ATTEMPTS', 3),
        'logging' => [
            'enabled' => env('NEO4J_LOGGING_ENABLED', false),
            'channel' => env('NEO4J_LOGGING_CHANNEL', 'daily'),
            'level' => env('NEO4J_LOGGING_LEVEL', 'debug'),
        ],
        'default_labels' => [
            'frame' => 'Frame',
            'lexical_unit' => 'LexicalUnit',
            'frame_element' => 'FrameElement',
            'semantic_type' => 'SemanticType',
            'construction' => 'Construction',
        ],
        'default_relationships' => [
            'HAS_ELEMENT' => 'HAS_ELEMENT',
            'INHERITS_FROM' => 'INHERITS_FROM',
            'SUBFRAME_OF' => 'SUBFRAME_OF',
            'USES' => 'USES',
            'IS_PERSPECTIVIZED_IN' => 'IS_PERSPECTIVIZED_IN',
            'SEE_ALSO' => 'SEE_ALSO',
        ]
    ]
];
