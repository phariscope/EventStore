<?php

namespace Phariscope\EventStore;

use Phariscope\Event\EventAbstract;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class StoredEvent extends EventAbstract
{
    private int $eventId;
    private string $eventBody;
    private string $typeName;

    public function __construct(
        EventAbstract $eventAbstract,
        ?int $id = null
    ) {
        parent::__construct($eventAbstract->occurredOn());
        $this->eventBody = $this->serializeInJson($eventAbstract);
        $this->typeName = get_class($eventAbstract);
        if ($id !== null) {
            $this->eventId = $id;
        }
    }

    private function serializeInJson(EventAbstract $event): string
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new PropertyNormalizer(), new DateTimeNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        return $serializer->serialize($event, 'json');
    }

    public function getEventBody(): string
    {
        return $this->eventBody;
    }

    public function eventId(): int
    {
        return $this->eventId;
    }

    public function typeName(): string
    {
        return $this->typeName;
    }
}
