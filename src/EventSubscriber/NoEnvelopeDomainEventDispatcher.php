<?php
/*
 * This file is part of the Symfony HeadsnetDomainEventsBundle.
 *
 * (c) Headstrong Internet Services Ltd 2020
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\EventSubscriber;

use Headsnet\DomainEventsBundle\Domain\Model\DomainEvent;
use Symfony\Component\Messenger\MessageBusInterface;

class NoEnvelopeDomainEventDispatcher implements DomainEventDispatcher
{
    /**
     * @var MessageBusInterface
     */
    private $eventBus;

    public function __construct(MessageBusInterface $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    public function dispatch(DomainEvent $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
