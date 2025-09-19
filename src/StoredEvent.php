<?php

namespace Phariscope\EventStore;

use Phariscope\Event\Psr14\Event;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Represents an event that has been stored in the event store.
 *
 * This class wraps a domain event with additional metadata needed for storage,
 * including a unique ID, serialized body, and type information.
 */
class StoredEvent extends Event
{
    private int $eventId;
    private string $eventBody;
    private string $typeName;
    private static int $nextId = 1;

    public function __construct(
        Event $eventAbstract,
        ?int $id = null
    ) {
        if ($id !== null && $id <= 0) {
            throw new \InvalidArgumentException('Event ID must be a positive integer, got ' . $id);
        }

        parent::__construct($eventAbstract->occurredOn());
        $this->eventBody = $this->serializeInJson($eventAbstract);
        $this->typeName = get_class($eventAbstract);
        $this->eventId = $id ?? self::$nextId++;
    }

    private function serializeInJson(Event $event): string
    {
        try {
            $encoders = [new JsonEncoder()];
            $normalizers = [new JsonSerializableNormalizer(), new PropertyNormalizer(), new DateTimeNormalizer()];

            $serializer = new Serializer($normalizers, $encoders);
            // Pre-validate custom jsonSerialize() even if interface is not implemented
            if (\is_callable([$event, 'jsonSerialize'])) {
                try {
                    /** @var mixed $jsonSerializableValue */
                    $jsonSerializableValue = \call_user_func([$event, 'jsonSerialize']);
                } catch (\Throwable $t) {
                    throw new \RuntimeException('Failed to serialize event: ' . $t->getMessage(), 0, $t);
                }

                if ($jsonSerializableValue === null || $jsonSerializableValue === '' || $jsonSerializableValue === []) {
                    throw new \RuntimeException('Failed to serialize event: Serialization resulted in empty content');
                }
            }

            $result = $serializer->serialize($event, 'json');

            if ($result === 'null' || trim($result) === '') {
                throw new \RuntimeException('Failed to serialize event: Serialization resulted in empty content');
            }

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to serialize event: ' . $e->getMessage(), 0, $e);
        }
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
