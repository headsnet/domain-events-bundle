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

namespace Headsnet\DomainEventsBundle\Doctrine;

use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Headsnet\DomainEventsBundle\Doctrine\Event\PreAppendEvent;
use Headsnet\DomainEventsBundle\Domain\Model\DomainEvent;
use Headsnet\DomainEventsBundle\Domain\Model\EventId;
use Headsnet\DomainEventsBundle\Domain\Model\EventStore;
use Headsnet\DomainEventsBundle\Domain\Model\StoredEvent;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DoctrineEventStore implements EventStore
{
    private EntityManagerInterface $em;

    private SerializerInterface $serializer;

    private EventDispatcherInterface $eventDispatcher;

    private string $tableName;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        EventDispatcherInterface $eventDispatcher,
        string $tableName
    ) {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $this->tableName = $tableName;
    }

    public function nextIdentity(): EventId
    {
        return EventId::fromUuid(Uuid::uuid4());
    }

    public function append(DomainEvent $domainEvent): void
    {
        $occurredOn = DateTimeImmutable::createFromFormat(
            DomainEvent::MICROSECOND_DATE_FORMAT,
            $domainEvent->getOccurredOn()
        );
        assert($occurredOn instanceof DateTimeImmutable);

        $this->eventDispatcher->dispatch(new PreAppendEvent($domainEvent));

        $storedEvent = new StoredEvent(
            $this->nextIdentity(),
            get_class($domainEvent),
            $occurredOn,
            $domainEvent->getAggregateRootId(),
            $this->serializer->serialize($domainEvent, 'json')
        );

        $this->em->persist($storedEvent);
        $classMetadata = $this->em->getClassMetadata(StoredEvent::class);
        $this->em->getUnitOfWork()->computeChangeSet($classMetadata, $storedEvent);
    }

    public function replace(DomainEvent $domainEvent): void
    {
        $repository = $this->em->getRepository(StoredEvent::class);
        $replaceableEvents = $repository->findBy([
            'aggregateRoot' => $domainEvent->getAggregateRootId(),
            'typeName' => get_class($domainEvent),
            'publishedOn' => null,
        ]);

        foreach ($replaceableEvents as $replaceableEvent) {
            $this->em->remove($replaceableEvent);
        }

        $this->append($domainEvent);
    }

    public function publish(StoredEvent $storedEvent): void
    {
        $storedEvent->setPublishedOn(new DateTimeImmutable());
        $this->em->persist($storedEvent);
        $this->em->flush();
    }

    /**
     * @return StoredEvent[]
     */
    public function allUnpublished(): array
    {
        try {
            if (false === $this->em->getConnection()->createSchemaManager()->tablesExist([$this->tableName])) {
                return []; // Connection does exist, but the events table does not exist.
            }
        } catch (ConnectionException $connectionException) {
            return []; // Connection itself does not exist
        }

        // Make "now" 1 second in the future, so events for immediate publishing are always published immediately.
        // This is because Doctrine does not yet support microseconds on DateTime fields
        // @see https://github.com/doctrine/dbal/issues/2873
        $now = new DateTimeImmutable();
        $now = $now->add(new DateInterval('PT1S'));

        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(StoredEvent::class, 'e')
            ->where('e.publishedOn IS NULL')
            ->andWhere('e.occurredOn < :now')
            ->setParameter('now', $now)
            ->orderBy('e.occurredOn');

        return $qb->getQuery()->getResult();
    }

    public function refresh(StoredEvent $storedEvent): void
    {
        $this->em->refresh($storedEvent);
    }
}
