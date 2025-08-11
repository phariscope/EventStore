<?php

namespace Phariscope\EventStore\Tests\Exceptions;

use Phariscope\EventStore\Exceptions\EventNotFoundException;
use PHPUnit\Framework\TestCase;

class EventNotFoundExceptionTest extends TestCase
{
    public function testDefaultMessage(): void
    {
        $exception = new EventNotFoundException();
        $this->assertEquals('No events found in the store', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testCustomMessage(): void
    {
        $exception = new EventNotFoundException('Custom error message');
        $this->assertEquals('Custom error message', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testCustomMessageAndCode(): void
    {
        $exception = new EventNotFoundException('Custom error', 404);
        $this->assertEquals('Custom error', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new EventNotFoundException('Wrapper error', 500, $previous);

        $this->assertEquals('Wrapper error', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
