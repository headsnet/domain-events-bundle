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

namespace Headsnet\DomainEventsBundle\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Interface
 */
interface EventStore
{
    /**
     * {@inheritdoc}
     */
    public function nextIdentity(): EventId;

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
