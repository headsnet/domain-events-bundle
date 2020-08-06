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

class StoredEvent
{
    /**
     * @var string
     */
    private $eventId;

    /**
     * @var \DateTimeImmutable
     */
    private $occurredOn;

    /**
     * @var \DateTimeImmutable
     */
    private $publishedOn;

    /**
     * @var string
     */
    private $aggregateRoot;

    /**
     * @var string
     */
    private $typeName;

    /**
     * @var string
     */
    private $eventBody;

    /**
     * @param EventId $eventId
     * @param string $typeName
     * @param \DateTimeImmutable $occurredOn
     * @param string $rootId
     * @param string $eventBody
     */
    public function __construct(
        EventId $eventId,
        string $typeName,
        \DateTimeImmutable $occurredOn,
        string $rootId,
        string $eventBody
    ) {
        $this->eventId = $eventId->asString();
        $this->typeName = $typeName;
        $this->occurredOn = $occurredOn;
        $this->aggregateRoot = $rootId;
        $this->eventBody = $eventBody;
    }

    /**
     * @return EventId
     */
    public function getEventId(): EventId
    {
        return EventId::fromString($this->eventId);
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getPublishedOn(): \DateTimeImmutable
    {
        return $this->publishedOn;
    }

    /**
     * @param \DateTimeImmutable $publishedOn
     * @return StoredEvent
     */
    public function setPublishedOn(\DateTimeImmutable $publishedOn): self
    {
        $this->publishedOn = $publishedOn;

        return $this;
    }

    /**
     * @return string
     */
    public function getTypeName(): string
    {
        return $this->typeName;
    }

    /**
     * @return string
     */
    public function getAggregateRoot(): string
    {
        return $this->aggregateRoot;
    }

    /**
     * @return string
     */
    public function getEventBody(): string
    {
        return $this->eventBody;
    }
}
