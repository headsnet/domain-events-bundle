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

interface RecordsEvents
{
    public function record(DomainEvent $event): void;

    /**
     * This ignores duplicate events, based on the event class-string
     */
    public function recordOnce(DomainEvent $event): void;
}
