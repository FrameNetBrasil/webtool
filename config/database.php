<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'webtool'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'webtool' => [
            'driver' => 'mariadb',
            // 'platform' => 'pdo_mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'x'),
            'port' => env('DB_PORT', ''),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'formatDate' => '%e/%m/%Y',
            'formatDateWhere' => '%Y/%m/%e',
            'formatTime' => '%T',
            'sequence' => [
                'table' => 'Sequence',
                'name' => 'Name',
                'value' => 'Value',
            ],
            'configurationClass' => 'Doctrine\DBAL\Configuration',
            'options' => [
                \PDO::ATTR_EMULATE_PREPARES => true,
            ],
            //            'options' => extension_loaded('pdo_mysql') ? array_filter([
            //                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            //            ]) : [
            //                \PDO::ATTR_EMULATE_PREPARES => true
            //            ],
        ],

        'webtool41' => [
            'driver' => 'mariadb',
            // 'platform' => 'pdo_mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_41_HOST', 'x'),
            'port' => env('DB_41_PORT', ''),
            'database' => env('DB_41_DATABASE', 'forge'),
            'username' => env('DB_41_USERNAME', 'forge'),
            'password' => env('DB_41_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'formatDate' => '%e/%m/%Y',
            'formatDateWhere' => '%Y/%m/%e',
            'formatTime' => '%T',
            'sequence' => [
                'table' => 'Sequence',
                'name' => 'Name',
                'value' => 'Value',
            ],
            'configurationClass' => 'Doctrine\DBAL\Configuration',
            'options' => [
                \PDO::ATTR_EMULATE_PREPARES => true,
            ],
            //            'options' => extension_loaded('pdo_mysql') ? array_filter([
            //                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            //            ]) : [
            //                \PDO::ATTR_EMULATE_PREPARES => true
            //            ],
        ],

        'webtool42_3' => [
            'driver' => 'mariadb',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST_42_3', 'localhost'),
            'port' => env('DB_PORT_42_3', '33060'),
            'database' => env('DB_DATABASE_42_3', 'forge'),
            'username' => env('DB_USERNAME_42_3', 'forge'),
            'password' => env('DB_PASSWORD_42_3', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'formatDate' => '%e/%m/%Y',
            'formatDateWhere' => '%Y/%m/%e',
            'formatTime' => '%T',
            'sequence' => [
                'table' => 'Sequence',
                'name' => 'Name',
                'value' => 'Value',
            ],
            'configurationClass' => 'Doctrine\DBAL\Configuration',
            'options' => [
                \PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ],

        'webtool37' => [
            'driver' => 'mariadb',
            // 'platform' => 'pdo_mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_37_HOST', 'x'),
            'port' => env('DB_37_PORT', ''),
            'database' => env('DB_37_DATABASE', 'forge'),
            'username' => env('DB_37_USERNAME', 'forge'),
            'password' => env('DB_37_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'formatDate' => '%e/%m/%Y',
            'formatDateWhere' => '%Y/%m/%e',
            'formatTime' => '%T',
            'sequence' => [
                'table' => 'Sequence',
                'name' => 'Name',
                'value' => 'Value',
            ],
            'configurationClass' => 'Doctrine\DBAL\Configuration',
            'options' => [
                \PDO::ATTR_EMULATE_PREPARES => true,
            ],
            //            'options' => extension_loaded('pdo_mysql') ? array_filter([
            //                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            //            ]) : [
            //                \PDO::ATTR_EMULATE_PREPARES => true
            //            ],
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_0900_ai_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Neo4j Database Configuration
    |--------------------------------------------------------------------------
    |
    | Neo4j graph database configuration for enhanced graph operations.
    | This configuration supports the laudis/neo4j-php-client package.
    |
    */

    'neo4j' => [
        'host' => env('NEO4J_HOST', 'localhost'),
        'port' => env('NEO4J_PORT', 7687),
        'username' => env('NEO4J_USERNAME', 'neo4j'),
        'password' => env('NEO4J_PASSWORD', ''),
        'database' => env('NEO4J_DATABASE', 'neo4j'),
        'scheme' => env('NEO4J_SCHEME', 'bolt'),
        'ssl' => env('NEO4J_SSL', false),
        'timeout' => env('NEO4J_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
