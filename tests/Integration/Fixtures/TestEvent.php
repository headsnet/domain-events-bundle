<?php

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Integration\Fixtures;

use Headsnet\DomainEventsBundle\Domain\Model\DomainEvent;
use Headsnet\DomainEventsBundle\Domain\Model\Traits\DomainEventTrait;

class TestEvent implements DomainEvent
{
    use DomainEventTrait;

    public function __construct(string $aggregateRootId)
    {
        $this->aggregateRootId = $aggregateRootId;
        $this->occurredOn = (new \DateTimeImmutable())->format(DomainEvent::MICROSECOND_DATE_FORMAT);
    }
}
