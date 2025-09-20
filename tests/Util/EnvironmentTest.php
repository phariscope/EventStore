<?php

namespace Phariscope\EventStore\Tests\Util;

use Phariscope\EventStore\Util\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    public function testExpandAbsolutePath(): void
    {
        // Arrange
        $sut = new Environment();
        $path = '/path/to/data/base.sqlite';

        // Act
        $result = $sut->expand($path);

        // Assert
        $this->assertEquals($path, $result);
    }

    public function testExpandEnvVar(): void
    {
        // Arrange
        $sut = new Environment();
        $path = '%env(DATABASE_PATH_FOR_TEST)%/event.sqlite';
        $_ENV['DATABASE_PATH_FOR_TEST'] = '/my/absolut/path/to/database';

        // Act
        $result = $sut->expand($path);

        // Assert
        $this->assertEquals('/my/absolut/path/to/database/event.sqlite', $result);

        // Clean up
        unset($_ENV['DATABASE_PATH_FOR_TEST']);
    }

    public function testExpandRelativePath(): void
    {
        // Arrange
        $sut = new Environment();
        $path = './data/base.sqlite';
        $workingDir = getcwd();

        // Act
        $result = $sut->expand($path);

        // Assert
        $expectedPath = $workingDir . '/data/base.sqlite';
        $this->assertEquals($expectedPath, $result);
    }

    public function testExpandRelativePathWithSubdirectory(): void
    {
        // Arrange
        $sut = new Environment();
        $subDir = './sub1/sub2/';
        $path = $subDir . '../data/base.sqlite';
        $workingDir = getcwd();

        // Act
        $result = $sut->expand($path);

        // Assert
        $expectedPath = $workingDir . '/sub1/data/base.sqlite';
        $this->assertEquals($expectedPath, $result);
    }

    public function testExpandRelativePathWithSubdirectoryAndComplexeDotDot(): void
    {
        // Arrange
        $sut = new Environment();
        $subDir = './sub1/sub2/../sub3/';
        $path = $subDir . '../data/../base.sqlite';
        $workingDir = getcwd();

        // Act
        $result = $sut->expand($path);

        // Assert
        $expectedPath = $workingDir . '/sub1/base.sqlite';
        $this->assertEquals($expectedPath, $result);
    }
}
