<?php
/**
 * This file is part of the Symfony HeadsnetDomainEventsBundle.
 *
 * (c) Headstrong Internet Services Ltd 2019
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\EventSubscriber;

use Headsnet\DomainEventsBundle\Domain\Model\EventStore;
use Headsnet\DomainEventsBundle\Domain\Model\StoredEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
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
    )
    {
        $this->eventBus = $eventBus;
        $this->eventStore = $eventStore;
        $this->serializer = $serializer;
        $this->lockFactory = $lockFactory;
    }

    /**
     * Support publishing events on TERMINATE event of both HttpKernel and Console
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'publishEventsFromHttp',
            ConsoleEvents::TERMINATE => 'publishEventsFromConsole'
        ];
    }

    public function publishEventsFromHttp(PostResponseEvent $event): void
    {
        $this->publishEvents();
    }

    public function publishEventsFromConsole(ConsoleTerminateEvent $event)
    {
        $this->publishEvents();
    }

    private function publishEvents()
    {
        foreach ($this->eventStore->allUnpublished() as $event)
        {
            $this->publishEvent($event);
        }
    }

    private function publishEvent(StoredEvent $event): void
    {
        $lock = $this->lockFactory->createLock(
            sprintf('domain-event-%s', $event->getEventId()->asString())
        );

        if ($lock->acquire())
        {
            $this->eventBus->dispatch(
                $this->serializer->deserialize($event->getEventBody(), $event->getTypeName(), 'json')
            );

            $this->eventStore->publish($event);

            $lock->release();
        }
    }
}
