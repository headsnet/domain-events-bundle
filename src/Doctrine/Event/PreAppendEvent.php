<?php

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Doctrine\Event;

use Headsnet\DomainEventsBundle\Domain\Model\DomainEvent;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class PreAppendEvent.
 *
 * @author Wolfgang Klinger <wolfgang@wazum.com>
 */
class PreAppendEvent extends Event
{
    protected DomainEvent $domainEvent;

    public function __construct(DomainEvent $domainEvent)
    {
        $this->domainEvent = $domainEvent;
    }

    public function getDomainEvent(): DomainEvent
    {
        return $this->domainEvent;
    }
}
