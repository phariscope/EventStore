<?php

namespace Phariscope\EventStore\Config;

use Phariscope\EventStore\Persistence\PersistEventInDatabaseSubscriber;
use Symfony\Component\Yaml\Yaml;

class EventStoreConfiguration
{
    /** @var array{sqlite_path?: string, table_name?: string} */
    private array $config;

    /**
     * @param array{sqlite_path?: string, table_name?: string} $config
     */
    private function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function fromFile(string $yamlFilePath): self
    {
        if (!is_file($yamlFilePath) || !is_readable($yamlFilePath)) {
            throw new \InvalidArgumentException(
                "Configuration file not readable: {$yamlFilePath}"
            );
        }

        /** @var array<string,mixed> $parsed */
        $parsed = is_array(Yaml::parseFile($yamlFilePath)) ? Yaml::parseFile($yamlFilePath) : [];

        if (!isset($parsed['event_store']) || !is_array($parsed['event_store'])) {
            throw new \InvalidArgumentException(
                'Missing "event_store" root key in configuration.'
            );
        }

        /** @var array{sqlite_path?: string, table_name?: string} $config */
        $config = $parsed['event_store'];
        return new self($config);
    }

    public function getSqlitePath(): string
    {
        $path = $this->config['sqlite_path'] ?? null;
        if (!is_string($path) || $path === '') {
            throw new \InvalidArgumentException(
                'Configuration key "event_store.sqlite_path" must be a non-empty string.'
            );
        }
        // Allow env expansion like ${DATA_PATH}/events.sqlite or %env(DATA_PATH)%/events.sqlite
        return \Phariscope\EventStore\Util\Environment::expand($path);
    }

    public function getTableName(): string
    {
        $tableName = $this->config['table_name'] ?? 'stored_events';
        if ($tableName === '') {
            throw new \InvalidArgumentException(
                'Configuration key "event_store.table_name" must be a non-empty string when provided.'
            );
        }
        return $tableName;
    }

    public function createSubscriber(): PersistEventInDatabaseSubscriber
    {
        $pdo = new \PDO('sqlite:' . $this->getSqlitePath());
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return new PersistEventInDatabaseSubscriber($pdo, $this->getTableName());
    }
}
