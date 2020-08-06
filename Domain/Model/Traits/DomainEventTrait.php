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

namespace Headsnet\DomainEventsBundle\Domain\Model\Traits;

trait DomainEventTrait
{
    /**
     * @var string
     */
    private $aggregateRootId;

    /**
     * The datetime the event occurred. Please use DomainEvent::MICROSECOND_DATE_FORMAT format
     *
     * @var string
     */
    private $occurredOn;

    /**
     * @var string|null
     */
    private $actorId;

    /**
     * @param string|null $actorId
     */
    public function setActorId(?string $actorId): void
    {
        $this->actorId = $actorId;
    }

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

    /**
     * @return bool
     */
    public function hasActorId(): bool
    {
        return (bool)$this->actorId;
    }
}
