<?php

namespace Phariscope\EventStore\Tests\Bridge\Symfony\DependencyInjection;

use Phariscope\EventStore\Bridge\Symfony\DependencyInjection\Configuration;
use Phariscope\EventStore\Util\Environment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Processor;

use function SafePHP\strval;

class ConfigurationTest extends TestCase
{
    public function testConfigurationParameters(): void
    {
        // Arrange
        $sut = new Configuration();

        // Act
        $tree = $sut->getConfigTreeBuilder();

        // Assert
        $root = $tree->getRootNode();
        $this->assertInstanceOf(ArrayNodeDefinition::class, $root);
        $children = $root->getChildNodeDefinitions();
        $this->assertArrayHasKey('sqlite_path', $children);
        $this->assertArrayHasKey('table_name', $children);
    }

    public function testConfigurationWithValues(): void
    {
        // Arrange
        $sut = new Configuration();
        $processor = new Processor();

        // Act
        $config = $processor->processConfiguration($sut, [
            ['sqlite_path' => '/tmp/test.db', 'table_name' => 'events']
        ]);

        // Assert
        $this->assertEquals('/tmp/test.db', $config['sqlite_path']);
        $this->assertEquals('events', $config['table_name']);
    }

    public function testConfigurationDefaultValues(): void
    {
        // Arrange
        $sut = new Configuration();
        $processor = new Processor();

        // Act
        $config = $processor->processConfiguration($sut, [
            ['sqlite_path' => '/tmp/test.db'] // Seul sqlite_path est fourni
        ]);

        // Assert
        $this->assertEquals('/tmp/test.db', $config['sqlite_path']);
        $this->assertEquals('stored_events', $config['table_name']); // Valeur par défaut
    }

    public function testConfigurationWithEnvironmentVariable(): void
    {
        // Arrange
        $sut = new Configuration();
        $processor = new Processor();
        $_ENV['TEST_DB_PATH'] = '/var/lib/test.sqlite';

        // Act
        $config = $processor->processConfiguration($sut, [
            ['sqlite_path' => '${TEST_DB_PATH}', 'table_name' => 'events']
        ]);

        // Assert
        $this->assertEquals('${TEST_DB_PATH}', $config['sqlite_path']);
        $expandedPath = Environment::expand(strval($config['sqlite_path']));
        $this->assertEquals('/var/lib/test.sqlite', $expandedPath);

        // Clean up
        unset($_ENV['TEST_DB_PATH']);
    }

    public function testConfigurationWithSymfonyStyleEnvironmentVariable(): void
    {
        // Arrange
        $sut = new Configuration();
        $processor = new Processor();
        $_ENV['DATABASE_PATH'] = '/opt/data/app.db';

        // Act
        $config = $processor->processConfiguration($sut, [
            ['sqlite_path' => '%env(DATABASE_PATH)%']
        ]);

        // Assert
        $this->assertEquals('%env(DATABASE_PATH)%', $config['sqlite_path']);
        $expandedPath = Environment::expand(strval($config['sqlite_path']));
        $this->assertEquals('/opt/data/app.db', $expandedPath);

        // Cleanup
        unset($_ENV['DATABASE_PATH']);
    }
}
