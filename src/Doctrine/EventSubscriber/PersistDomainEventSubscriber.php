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

namespace Headsnet\DomainEventsBundle\Doctrine\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;
use Headsnet\DomainEventsBundle\Domain\Model\ContainsEvents;
use Headsnet\DomainEventsBundle\Domain\Model\EventStore;
use Headsnet\DomainEventsBundle\Domain\Model\ReplaceableDomainEvent;

class PersistDomainEventSubscriber implements EventSubscriber
{
    private EventStore $eventStore;

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
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->persistEntityDomainEvents($args);
    }

    private function persistEntityDomainEvents(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        $sources = [
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions(),
        ];

        foreach ($sources as $source) {
            foreach ($source as $entity) {
                if (false === $entity instanceof ContainsEvents) {
                    continue;
                }

                $this->storeRecordedEvents($entity);
            }
        }

        $collectionSources = [
            $uow->getScheduledCollectionDeletions(),
            $uow->getScheduledCollectionUpdates(),
        ];
        foreach ($collectionSources as $source) {
            /** @var PersistentCollection $collection */
            foreach ($source as $collection) {
                $entity = $collection->getOwner();
                if (false === $entity instanceof ContainsEvents) {
                    continue;
                }

                $this->storeRecordedEvents($entity);
            }
        }
    }

    private function storeRecordedEvents(ContainsEvents $entity): void
    {
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
