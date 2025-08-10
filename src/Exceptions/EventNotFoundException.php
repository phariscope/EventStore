<?php

namespace Phariscope\EventStore\Exceptions;

class EventNotFoundException extends \Exception
{
    public function __construct(
        string $message = 'No events found in the store',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
