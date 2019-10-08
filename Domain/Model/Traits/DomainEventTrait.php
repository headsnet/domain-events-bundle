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

	public function getAggregateRootId(): string
	{
		return $this->aggregateRootId;
	}

	public function getOccurredOn(): string
	{
		return $this->occurredOn;
	}

	public function getActorId(): ?string
	{
		return $this->actorId;
	}

}
