<?php

namespace Phariscope\EventStore\Tests\Config;

use Phariscope\EventStore\Config\EventStoreConfiguration;
use PHPUnit\Framework\TestCase;
use Phariscope\EventStore\StoreInterface;

class EventStoreConfigurationTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['DATA_PATH'] = sys_get_temp_dir();
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'esconf_') ?: '';
        $this->assertNotSame('', $this->tmpFile);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_ENV['DATA_PATH']);
        @unlink($this->tmpFile);
    }

    public function testFromFileAndCreateSubscriber(): void
    {
        // Arrange
        $yaml = <<<YAML
event_store:
  dsn: "sqlite::memory:"
  table_name: "test_events"
YAML;
        file_put_contents($this->tmpFile, $yaml);
        $config = EventStoreConfiguration::fromFile($this->tmpFile);

        // Act
        $subscriber = $config->createSubscriber();

        // Assert
        $this->assertInstanceOf(StoreInterface::class, $subscriber->getStore());
    }

    public function testMissingRootKey(): void
    {
        // Arrange
        $yaml = "foo: bar\n";
        file_put_contents($this->tmpFile, $yaml);

        // Expect
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('event_store');

        // Act
        EventStoreConfiguration::fromFile($this->tmpFile);
    }

    public function testInvalidSqlitePath(): void
    {
        // Arrange
        $yaml = <<<YAML
event_store:
  dsn: ""
YAML;
        file_put_contents($this->tmpFile, $yaml);
        $config = EventStoreConfiguration::fromFile($this->tmpFile);

        // Expect
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('dsn');

        // Act
        $config->getDsn();
    }

    public function testEnvExpansion(): void
    {
        // Arrange
        $yaml = <<<'YAML'
event_store:
  dsn: "sqlite:///${DATA_PATH}/events.sqlite"
  table_name: "test_events"
YAML;

        file_put_contents($this->tmpFile, $yaml);

        // Act
        $config = EventStoreConfiguration::fromFile($this->tmpFile);
        $path = $config->getDsn();

        // Assert
        $this->assertStringContainsString('/events.sqlite', $path);
    }
}
