<?php
declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Domain\Model;

/**
 * Class
 */
class StoredEvent
{
	/**
	 * @var int
	 */
	private $eventId;

	/**
	 * @var \DateTimeImmutable
	 */
	private $occurredOn;

	/**
	 * @var string
	 */
	private $actorId;

	/**
	 * @var \DateTimeImmutable
	 */
	private $publishedOn;

	/**
	 * @var string
	 */
	private $rootId;

	/**
	 * @var string
	 */
	private $typeName;

	/**
	 * @var string
	 */
	private $eventBody;

	/**
	 * @param string             $typeName
	 * @param \DateTimeImmutable $occurredOn
	 * @param string             $rootId
	 * @param string             $eventBody
	 * @param string|null        $actorId
	 */
	public function __construct(
		string             $typeName,
		\DateTimeImmutable $occurredOn,
		string             $rootId,
		string             $eventBody,
		?string            $actorId = null
	)
	{
		$this->typeName = $typeName;
		$this->occurredOn = $occurredOn;
		$this->rootId = $rootId;
		$this->eventBody = $eventBody;
		$this->actorId = $actorId;
	}

	/**
	 * @return int
	 */
	public function getEventId(): int
	{
		return $this->eventId;
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
	 *
	 * @return StoredEvent
	 */
	public function setPublishedOn(\DateTimeImmutable $publishedOn): StoredEvent
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
	public function getAggregateRootId(): string
	{
		return $this->rootId;
	}

	/**
	 * @return string
	 */
	public function getEventBody(): string
	{
		return $this->eventBody;
	}

	/**
	 * @return string
	 */
	public function getActorId(): string
	{
		return $this->actorId;
	}

}
