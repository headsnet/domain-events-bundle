<?php
declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Domain\Model\Traits;

/**
 * Trait
 */
trait DomainEventTrait
{
	/**
	 * @var string
	 */
	private $aggregateRootId;

	/**
	 * The datetime the event occurred. Please use Date::ATOM format
	 *
	 * @var string
	 */
	private $occurredOn;

	/**
	 * @var string|null
	 */
	private $actorId;

	/**
	 * @return string
	 */
	public function getAggregateRootId(): string
	{
		return $this->aggregateRootId;
	}

	/**
	 * @return string
	 */
	public function getOccurredOn(): string
	{
		return $this->occurredOn;
	}

	/**
	 * @return string|null
	 */
	public function getActorId(): ?string
	{
		return $this->actorId;
	}

}
