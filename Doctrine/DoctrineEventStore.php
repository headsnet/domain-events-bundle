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

namespace Headsnet\DomainEventsBundle\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Headsnet\DomainEventsBundle\Domain\Model\DomainEvent;
use Headsnet\DomainEventsBundle\Domain\Model\EventStore;
use Headsnet\DomainEventsBundle\Domain\Model\StoredEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class
 */
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

	/**
	 * @param EntityManagerInterface $entityManager
	 * @param SerializerInterface    $serializer
	 */
	public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
	{
		$this->em = $entityManager;
		$this->repository = $entityManager->getRepository(StoredEvent::class);
		$this->serializer = $serializer;
	}

    /**
     * @param DomainEvent $domainEvent
     */
    public function append(DomainEvent $domainEvent)
    {
        $storedEvent = new StoredEvent(
            get_class($domainEvent),
            \DateTimeImmutable::createFromFormat(DATE_ATOM, $domainEvent->getOccurredOn()),
            $domainEvent->getAggregateRootId(),
            $this->serializer->serialize($domainEvent, 'json')
        );

        $this->em->persist($storedEvent);
        $classMetadata = $this->em->getClassMetadata(StoredEvent::class);
        $this->em->getUnitOfWork()->computeChangeSet($classMetadata, $storedEvent);
    }

	/**
	 * @param StoredEvent $storedEvent
	 *
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

		$qb = $this->em->createQueryBuilder()
			->select('e')
			->from(StoredEvent::class, 'e')
			->where('e.publishedOn IS NULL')
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
