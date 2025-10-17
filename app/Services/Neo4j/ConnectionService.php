<?php

namespace App\Services\Neo4j;

use Illuminate\Support\Facades\Log;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Exception\Neo4jException;
use Laudis\Neo4j\Authentication\Authenticate;
use RuntimeException;

class ConnectionService
{
    private static array $connections = [];

    private static array $config = [];

    public static function connection(?string $name = null): ClientInterface
    {
        $name = $name ?? 'default';

        if (!isset(self::$connections[$name])) {
            self::$connections[$name] = self::createConnection($name);
        }

        return self::$connections[$name];
    }

    private static function createConnection(string $name): ClientInterface
    {
        $config = self::getConnectionConfig($name);

        if (!$config) {
            throw new RuntimeException("Neo4j connection [{$name}] not configured.");
        }

        try {
            $builder = ClientBuilder::create();

            $uri = self::buildUri($config);

            $builder = $builder->withDriver(
                $name,
                $uri,
                Authenticate::basic(
                    $config['username'],
                    $config['password']
                )
            );

            if (!empty($config['database']) && $config['database'] !== 'neo4j') {
                $builder = $builder->withDefaultDriver($name);
            }

            $client = $builder->build();

            self::testConnection($client, $name);

            self::logConnection($name, $config);

            return $client;

        } catch (Neo4jException $e) {
            self::logConnectionError($name, $e);
            throw new RuntimeException("Failed to connect to Neo4j [{$name}]: ".$e->getMessage(), 0, $e);
        }
    }

    private static function getConnectionConfig(string $name): array
    {
        if ($name === 'default') {
            return [
                'host' => config('database.neo4j.host', 'localhost'),
                'port' => config('database.neo4j.port', 7687),
                'username' => config('database.neo4j.username', 'neo4j'),
                'password' => config('database.neo4j.password', ''),
                'database' => config('database.neo4j.database', 'neo4j'),
                'scheme' => config('database.neo4j.scheme', 'bolt'),
                'ssl' => config('database.neo4j.ssl', false),
                'timeout' => config('database.neo4j.timeout', 5),
            ];
        }

        return config("database.neo4j.connections.{$name}", []);
    }

    private static function buildUri(array $config): string
    {
        $scheme = $config['scheme'] ?? 'bolt';
        $host = $config['host'];
        $port = $config['port'];

        if ($host === 'neo4j' && !self::isRunningInContainer()) {
            $host = 'localhost';
        }

        return "{$scheme}://{$host}:{$port}";
    }

    private static function isRunningInContainer(): bool
    {
        return file_exists('/.dockerenv') ||
               (file_exists('/proc/1/cgroup') &&
                str_contains(file_get_contents('/proc/1/cgroup'), 'docker'));
    }

    private static function testConnection(ClientInterface $client, string $name): void
    {
        try {
            $result = $client->run('RETURN 1 as test');
            $record = $result->first();

            if (!$record || $record->get('test') !== 1) {
                throw new RuntimeException("Connection test failed for Neo4j [{$name}]");
            }
        } catch (Neo4jException $e) {
            throw new RuntimeException("Connection test failed for Neo4j [{$name}]: ".$e->getMessage(), 0, $e);
        }
    }

    private static function logConnection(string $name, array $config): void
    {
        if (config('webtool.neo4j.logging.enabled', false)) {
            Log::channel(config('webtool.neo4j.logging.channel', 'daily'))
                ->info('Neo4j connection established', [
                    'connection' => $name,
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'scheme' => $config['scheme'] ?? 'bolt',
                ]);
        }
    }

    private static function logConnectionError(string $name, Neo4jException $e): void
    {
        Log::channel(config('webtool.neo4j.logging.channel', 'daily'))
            ->error('Neo4j connection failed', [
                'connection' => $name,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
    }

    public static function closeAll(): void
    {
        self::$connections = [];
    }

    public static function getStats(): array
    {
        return [
            'active_connections' => count(self::$connections),
            'connection_names' => array_keys(self::$connections),
        ];
    }

    public static function hasConnection(?string $name = null): bool
    {
        $name = $name ?? 'default';

        return isset(self::$connections[$name]);
    }

    public static function isEnabled(): bool
    {
        return config('webtool.neo4j.enabled', false);
    }
}