<?php

namespace Phariscope\EventStore\Tests\Bridge\Symfony\Factory;

use Phariscope\EventStore\Bridge\Symfony\Factory\PdoFactory;
use PHPUnit\Framework\TestCase;

class PdoFactoryTest extends TestCase
{
    public function testCreatePdo(): void
    {
        // Arrange
        $sut = new PdoFactory('sqlite::memory:');

        // Act
        $pdo = $sut->createPdo();

        // Assert
        $this->assertInstanceOf(\PDO::class, $pdo);
    }
}
