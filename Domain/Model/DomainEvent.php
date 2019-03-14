<?php
declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Domain\Model;

/**
 * Interface
 */
interface DomainEvent
{

	/**
	 * The aggregate root that was affected by this event.
	 *
	 * @return string
	 */
	public function getAggregateRootId(): string;

	/**
	 * The datetime the event occurred. Please use Date::ATOM format
	 *
	 * @return string
	 */
	public function getOccurredOn(): string;

	/**
	 * The id of the actor that fired this event. Most usually a user id.
	 *
	 * @return string|null
	 */
	public function getActorId(): ?string;

}
