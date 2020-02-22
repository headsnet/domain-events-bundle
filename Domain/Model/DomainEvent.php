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

namespace Headsnet\DomainEventsBundle\Domain\Model;

interface DomainEvent
{
	/**
	 * The aggregate root that was affected by this event.
	 */
	public function getAggregateRootId(): string;

	/**
	 * The datetime the event occurred. Please use Date::ATOM format
	 */
	public function getOccurredOn(): string;

	/**
	 * The id of the actor that fired this event. Most usually a user id.
	 */
	public function getActorId(): ?string;

}
