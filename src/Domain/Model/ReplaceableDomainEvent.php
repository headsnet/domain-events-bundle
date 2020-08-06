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

/**
 * A Replaceable Domain Event when dispatched will replace any existing
 * event of the same class for the corresponding aggregate root, but only
 * if the previous event is Unpublished.
 *
 * If the previous event instance has been published, a new event will be
 * persisted and subsequently published, unless it itself is replaced.
 */
interface ReplaceableDomainEvent extends DomainEvent
{
}
