<?php
/**
 * This file is part of the Symfony HeadsnetDomainEventsBundle.
 *
 * (c) Headstrong Internet Services Ltd 2020
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Doctrine\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Headsnet\DomainEventsBundle\Domain\Model\ContainsEvents;
use Headsnet\DomainEventsBundle\Domain\Model\EventStore;
use Headsnet\DomainEventsBundle\Domain\Model\ReplaceableDomainEvent;

class PersistDomainEventSubscriber implements EventSubscriber
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @param EventStore $eventStore
     */
    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    /**
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [
            'onFlush'
        ];
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->persistEntityDomainEvents($args);
    }

    /**
     * @param OnFlushEventArgs $args
     */
    private function persistEntityDomainEvents(OnFlushEventArgs $args): void
    {
        $uow = $args->getEntityManager()->getUnitOfWork();

        $sources = [
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions(),
            //$uow->getScheduledCollectionDeletions(),
            //$uow->getScheduledCollectionUpdates()
        ];

        foreach ($sources as $source) {
            foreach ($source as $entity) {
                if (false === $entity instanceof ContainsEvents) {
                    continue;
                }

                foreach ($entity->getRecordedEvents() as $domainEvent) {
                    if ($domainEvent instanceof ReplaceableDomainEvent) {
                        $this->eventStore->replace($domainEvent);
                    } else {
                        $this->eventStore->append($domainEvent);
                    }
                }

                $entity->clearRecordedEvents();
            }
        }
    }
}
