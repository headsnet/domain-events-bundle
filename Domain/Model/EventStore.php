<?php
declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Interface
 */
interface EventStore
{

	/**
	 * @param DomainEvent $domainEvent
	 */
	public function append(DomainEvent $domainEvent);

	/**
	 * @param StoredEvent $domainEvent
	 */
	public function publish(StoredEvent $domainEvent);

	/**
	 * @return StoredEvent[]
	 */
	public function allUnpublished(): array;

	/**
	 * @param $eventId
	 *
	 * @return StoredEvent[]|ArrayCollection
	 */
	//public function allStoredEventsSince($eventId): ArrayCollection;

}
