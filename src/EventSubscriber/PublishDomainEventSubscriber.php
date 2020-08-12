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
use Headsnet\DomainEventsBundle\Domain\Model\EventStore;
use Headsnet\DomainEventsBundle\Domain\Model\StoredEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class PublishDomainEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var MessageBusInterface
     */
    private $eventBus;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var LockFactory
     */
    private $lockFactory;

    public function __construct(
        MessageBusInterface $eventBus,
        EventStore $eventStore,
        SerializerInterface $serializer,
        LockFactory $lockFactory
    ) {
        $this->eventBus = $eventBus;
        $this->eventStore = $eventStore;
        $this->serializer = $serializer;
        $this->lockFactory = $lockFactory;
    }

    /**
     * Support publishing events on TERMINATE event of both HttpKernel and Console.
     *
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'publishEventsFromHttp',
            ConsoleEvents::TERMINATE => 'publishEventsFromConsole',
        ];
    }

    public function publishEventsFromHttp(TerminateEvent $event): void
    {
        $this->publishEvents();
    }

    public function publishEventsFromConsole(ConsoleTerminateEvent $event): void
    {
        $this->publishEvents();
    }

    private function publishEvents(): void
    {
        foreach ($this->eventStore->allUnpublished() as $event) {
            $this->publishEvent($event);
        }
    }

    private function publishEvent(StoredEvent $storedEvent): void
    {
        $lock = $this->lockFactory->createLock(
            sprintf('domain-event-%s', $storedEvent->getEventId()->asString())
        );

        if ($lock->acquire()) {
            $domainEvent = $this->serializer->deserialize(
                $storedEvent->getEventBody(),
                $storedEvent->getTypeName(),
                'json'
            );
            assert($domainEvent instanceof DomainEvent);

            $this->eventBus->dispatch($domainEvent);
            $this->eventStore->publish($storedEvent);

            $lock->release();
        }
    }
}
