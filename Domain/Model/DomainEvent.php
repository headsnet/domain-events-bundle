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
    const MICROSECOND_DATE_FORMAT = 'Y-m-d\TH:i:s.uP';

    /**
     * This is the only setter allowed to mutate the event class. It allows setting the
     * actorId centrally when persisting the event, instead of having to determine it and
     * then assign it separately for each dispatched event.
     */
    public function setActorId(?string $actorId): void;

	/**
	 * The aggregate root that was affected by this event.
	 */
	public function getAggregateRootId(): string;

	/**
	 * The datetime the event occurred. Please use self::MICROSECOND_DATE_FORMAT format
	 */
	public function getOccurredOn(): string;

	/**
	 * The id of the actor that fired this event. Most usually a user id.
	 */
	public function getActorId(): ?string;

}
