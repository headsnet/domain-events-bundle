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

namespace Headsnet\DomainEventsBundle\Domain\Model;

interface EventStore
{
    public function nextIdentity(): EventId;

    public function append(DomainEvent $domainEvent): void;

    public function replace(DomainEvent $domainEvent): void;

    public function publish(StoredEvent $storedEvent): void;

    /**
     * @return StoredEvent[]
     */
    public function allUnpublished(): array;

    public function refresh(StoredEvent $storedEvent): void;
}
