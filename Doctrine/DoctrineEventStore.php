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

namespace Headsnet\DomainEventsBundle\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Headsnet\DomainEventsBundle\Domain\Model\DomainEvent;
use Headsnet\DomainEventsBundle\Domain\Model\EventId;
use Headsnet\DomainEventsBundle\Domain\Model\EventStore;
use Headsnet\DomainEventsBundle\Domain\Model\StoredEvent;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\SerializerInterface;

final class DoctrineEventStore implements EventStore
{
	/**
	 * @var EntityManagerInterface
	 */
	private $em;

	/**
	 * @var EntityRepository
	 */
	private $repository;

	/**
	 * @var SerializerInterface
	 */
	private $serializer;

	public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
	{
		$this->em = $entityManager;
		$this->repository = $entityManager->getRepository(StoredEvent::class);
		$this->serializer = $serializer;
	}

    /**
     * @throws \Exception
     */
    public function nextIdentity(): EventId
    {
        return EventId::fromUuid(Uuid::uuid4());
    }

    /**
     * @throws \Exception
     */
    public function append(DomainEvent $domainEvent)
    {
        $storedEvent = new StoredEvent(
            $this->nextIdentity(),
            get_class($domainEvent),
            \DateTimeImmutable::createFromFormat(DomainEvent::MICROSECOND_DATE_FORMAT, $domainEvent->getOccurredOn()),
            $domainEvent->getAggregateRootId(),
            $this->serializer->serialize($domainEvent, 'json')
        );

        $this->em->persist($storedEvent);
        $classMetadata = $this->em->getClassMetadata(StoredEvent::class);
        $this->em->getUnitOfWork()->computeChangeSet($classMetadata, $storedEvent);
    }

    /**
     * @throws \Exception
     */
    public function replace(DomainEvent $domainEvent)
    {
        $previous = $this->repository->findOneBy([
            'aggregateRoot' => $domainEvent->getAggregateRootId(),
            'typeName' => get_class($domainEvent),
            'publishedOn' => null
        ]);

        if ($previous)
        {
            $this->em->remove($previous);
        }

        $this->append($domainEvent);
    }

	/**
	 * @throws \Exception
	 */
	public function publish(StoredEvent $storedEvent)
	{
		$storedEvent->setPublishedOn(new \DateTimeImmutable());
		$this->em->persist($storedEvent);
		$this->em->flush();
	}

	/**
	 * @return StoredEvent[]
	 */
	public function allUnpublished(): array
	{
        if (false === $this->em->getConnection()->getSchemaManager()->tablesExist(['event']))
        {
            return [];
        }

        // Make "now" 1 second in the future, so events for immediate publishing are always published immediately.
        // This is because Doctrine does not yet support microseconds on DateTime fields
        // @see https://github.com/doctrine/dbal/issues/2873
        $now = new \DateTimeImmutable();
        $now = $now->add(new \DateInterval('PT1S'));

		$qb = $this->em->createQueryBuilder()
			->select('e')
			->from(StoredEvent::class, 'e')
			->where('e.publishedOn IS NULL')
			->andWhere('e.occurredOn < :now')
			->setParameter('now', $now)
			->orderBy('e.eventId');

		return $qb->getQuery()->getResult();
	}

	/**
	 * @return StoredEvent[]|ArrayCollection
	 */
	/*public function allStoredEventsSince($eventId): ArrayCollection
	{
		$qb = $this->em->createQueryBuilder()
			->select('e')
			->from(StoredEvent::class, 'e')
			->orderBy('e.eventId');

		if ($eventId)
		{
			$qb
				->where('se.eventId > :event_id')
				->setParameter('event_id', $eventId);
		}

		return $qb->getQuery()->getResult();
	}*/

}
