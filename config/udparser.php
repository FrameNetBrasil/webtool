<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trankit UD Parser Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Trankit Universal Dependencies parser integration.
    | Trankit is accessed via HTTP API for parsing lemmas and sentences
    | to extract dependency trees for MWE (Multiword Expression) pattern
    | recognition.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Trankit Service URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Trankit HTTP service. This service should be
    | running and accessible from the Laravel application.
    |
    | Example: http://localhost:8405
    |
    */
    'trankit_url' => env('TRANKIT_URL', 'http://localhost:8405'),

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | The default language ID for parsing. Language mapping:
    | - 1: Portuguese
    | - 2: English
    |
    | This can be overridden per request.
    |
    */
    'default_language' => env('TRANKIT_DEFAULT_LANGUAGE', 1),

    /*
    |--------------------------------------------------------------------------
    | Language Models
    |--------------------------------------------------------------------------
    |
    | Mapping of language IDs to Trankit model names.
    |
    */
    'languages' => [
        1 => 'portuguese',
        2 => 'english',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the HTTP client used to communicate with Trankit.
    |
    */
    'timeout' => env('TRANKIT_TIMEOUT', 300.0), // 5 minutes default

    /*
    |--------------------------------------------------------------------------
    | Parsing Options
    |--------------------------------------------------------------------------
    |
    | Options for parsing behavior.
    |
    */
    'options' => [
        'handle_punct' => true,      // Handle punctuation preprocessing
        'handle_contractions' => true, // Handle Portuguese contractions
        'use_tokens' => false,        // Use pre-tokenized input
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Whether to cache parsed results to avoid redundant parsing.
    |
    */
    'cache' => [
        'enabled' => env('TRANKIT_CACHE_ENABLED', true),
        'ttl' => env('TRANKIT_CACHE_TTL', 3600), // 1 hour in seconds
    ],
];
