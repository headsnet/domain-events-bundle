<?php

declare(strict_types=1);

namespace Headsnet\DomainEventsBundle\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Headsnet\DomainEventsBundle\Domain\Model\StoredEvent;
use Headsnet\DomainEventsBundle\EventSubscriber\PublishDomainEventSubscriber;
use Headsnet\DomainEventsBundle\HeadsnetDomainEventsBundle;
use Headsnet\DomainEventsBundle\Integration\Fixtures\TestEntity;
use Headsnet\DomainEventsBundle\Integration\Fixtures\TestEvent;
use Nyholm\BundleTest\TestKernel;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests that events are not published during active transactions.
 *
 * @group integration
 */
class EventPublishingDuringTransactionTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PublishDomainEventSubscriber $publishSubscriber;

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

        // Get the publisher subscriber from the test container
        /** @var PublishDomainEventSubscriber $publishSubscriber */
        $publishSubscriber = $container->get('test.headsnet_domain_events.event_subscriber.publisher');
        $this->publishSubscriber = $publishSubscriber;

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testEventsAreNotPublishedDuringOpenTransaction(): void
    {
        $this->entityManager->beginTransaction();

        $this->createAndPersistEntityWithEvent();

        $terminateEvent = $this->createTerminateEvent();
        $this->publishSubscriber->publishEventsFromHttp($terminateEvent);

        $this->assertEventNotPublished();

        $this->entityManager->rollback();
        $this->assertNoEventsExistAfterRollback();
    }

    public function testEventsAreNotPublishedDuringNestedTransactions(): void
    {
        $this->entityManager->beginTransaction();

        try {
            $this->entityManager->beginTransaction();

            try {
                $this->createAndPersistEntityWithEvent();

                $terminateEvent = $this->createTerminateEvent();
                $this->publishSubscriber->publishEventsFromHttp($terminateEvent);

                $this->assertEventNotPublished();

                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw $e;
            }

            self::assertTrue($this->entityManager->getConnection()->isTransactionActive());
            $this->publishSubscriber->publishEventsFromHttp($terminateEvent);
            $this->assertEventNotPublished();

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    private function createAndPersistEntityWithEvent(): void
    {
        $entity = new TestEntity();
        $event = new TestEvent($entity->getId());
        $entity->record($event);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    private function createTerminateEvent(): TerminateEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new TerminateEvent($kernel, new Request(), new Response());
    }

    private function assertEventNotPublished(): void
    {
        $this->entityManager->clear();
        $events = $this->entityManager->getRepository(StoredEvent::class)->findAll();
        self::assertCount(1, $events);

        $publishedOn = null;
        try {
            $publishedOn = $events[0]->getPublishedOn();
        } catch (\Error $e) {
        }
        self::assertNull($publishedOn, 'Event should not be published during active transaction');
    }

    private function assertNoEventsExistAfterRollback(): void
    {
        $this->entityManager->clear();
        $events = $this->entityManager->getRepository(StoredEvent::class)->findAll();
        self::assertCount(0, $events, 'No events should exist after rollback');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        $this->entityManager->close();
    }
}
