<?php

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Headsnet\DomainEventsBundle\Domain\Model\DomainEvent;
use Headsnet\DomainEventsBundle\Domain\Model\EventStore;
use Headsnet\DomainEventsBundle\Domain\Model\StoredEvent;
use Headsnet\DomainEventsBundle\HeadsnetDomainEventsBundle;
use Headsnet\DomainEventsBundle\Integration\Fixtures\TestEntity;
use Headsnet\DomainEventsBundle\Integration\Fixtures\TestEvent;
use Nyholm\BundleTest\TestKernel;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @group integration
 */
class DomainEventPersistenceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private EventStore $eventStore;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addTestConfig(__DIR__ . '/config.yml');
        $kernel->addTestBundle(FrameworkBundle::class);
        $kernel->addTestBundle(DoctrineBundle::class);
        $kernel->addTestBundle(HeadsnetDomainEventsBundle::class);
        $kernel->handleOptions($options);

        return $kernel;
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;

        /** @var EventStore $eventStore */
        $eventStore = $container->get(EventStore::class);
        $this->eventStore = $eventStore;

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testDomainEventIsPersistedWithCorrectData(): void
    {
        $entity = new TestEntity();
        $event = new TestEvent($entity->getId());

        $entity->record($event);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->entityManager->clear();

        /** @var StoredEvent[] $storedEvents */
        $storedEvents = $this->entityManager->getRepository(StoredEvent::class)->findAll();
        self::assertCount(1, $storedEvents);
        self::assertEquals($event->getAggregateRootId(), $storedEvents[0]->getAggregateRoot());
        /** @var \DateTimeImmutable $expectedDate */
        $expectedDate = \DateTimeImmutable::createFromFormat(DomainEvent::MICROSECOND_DATE_FORMAT, $event->getOccurredOn());
        /** @var \DateTimeImmutable $actualDate */
        $actualDate = $storedEvents[0]->getOccurredOn();
        $platform = $this->entityManager->getConnection()->getDatabasePlatform();

        if ($this->isMicrosecondPlatform($platform)) {
            // MySQL and PostgreSQL support microseconds
            self::assertEquals(
                $expectedDate->format('Y-m-d\TH:i:s.u'),
                $actualDate->format('Y-m-d\TH:i:s.u'),
                'Dates should match with microsecond precision'
            );
        } else {
            // SQLite and others: compare only up to seconds
            self::assertEquals(
                $expectedDate->format('Y-m-d H:i:s'),
                $actualDate->format('Y-m-d H:i:s'),
                'Dates should match up to seconds precision'
            );
        }
        self::assertNull($storedEvents[0]->getPublishedOn());
    }

    public function testReplaceableDomainEventReplacesUnpublishedEvent(): void
    {
        $aggregateId = Uuid::uuid4()->toString();
        $firstEvent = new TestEvent($aggregateId);
        $secondEvent = new TestEvent($aggregateId);

        // Store event
        $this->eventStore->append($firstEvent);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $events = $this->entityManager->getRepository(StoredEvent::class)->findAll();
        self::assertCount(1, $events);
        $firstStoredEventId = $events[0]->getEventId()->asString();

        // Replace event
        $this->eventStore->replace($secondEvent);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $events = $this->entityManager->getRepository(StoredEvent::class)->findAll();
        self::assertCount(1, $events, 'Should still have only one event');
        self::assertNotEquals($firstStoredEventId, $events[0]->getEventId()->asString(), 'Should have a different event ID');
        self::assertEquals($aggregateId, $events[0]->getAggregateRoot());
    }

    public function testPublishedDomainEventIsNotReplaced(): void
    {
        $aggregateId = Uuid::uuid4()->toString();
        $firstEvent = new TestEvent($aggregateId);

        $this->eventStore->append($firstEvent);
        $this->entityManager->flush();
        $this->entityManager->clear();
        $storedEvent = $this->entityManager->getRepository(StoredEvent::class)->findAll()[0];
        $this->eventStore->publish($storedEvent);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Attempt to replace published event
        $secondEvent = new TestEvent($aggregateId);
        $this->eventStore->replace($secondEvent);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $events = $this->entityManager->getRepository(StoredEvent::class)->findAll();
        self::assertCount(2, $events, 'Should have two events');
        self::assertEquals($aggregateId, $events[0]->getAggregateRoot());
        self::assertEquals($aggregateId, $events[1]->getAggregateRoot());
        self::assertNotNull($events[0]->getPublishedOn(), 'First event should be published');
        self::assertNull($events[1]->getPublishedOn(), 'Second event should not be published');
    }

    public function testUnpublishedEventsCanBeRetrieved(): void
    {
        $aggregateId = Uuid::uuid4()->toString();
        $event = new TestEvent($aggregateId);

        $this->eventStore->append($event);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $unpublishedEvents = $this->eventStore->allUnpublished();
        self::assertCount(1, $unpublishedEvents);
        self::assertEquals($aggregateId, $unpublishedEvents[0]->getAggregateRoot());
    }

    private function isMicrosecondPlatform(AbstractPlatform $platform): bool
    {
        $platformClass = get_class($platform);
        $microsecondPlatformClasses = [
            'Doctrine\\DBAL\\Platforms\\MySQLPlatform',
            'Doctrine\\DBAL\\Platforms\\PostgreSQLPlatform'
        ];

        return in_array($platformClass, $microsecondPlatformClasses);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
