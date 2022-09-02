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

namespace Headsnet\DomainEventsBundle\Functional;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Headsnet\DomainEventsBundle\Doctrine\DoctrineEventStore;
use Headsnet\DomainEventsBundle\Doctrine\EventSubscriber\PersistDomainEventSubscriber;
use Headsnet\DomainEventsBundle\EventSubscriber\PublishDomainEventSubscriber;
use Headsnet\DomainEventsBundle\HeadsnetDomainEventsBundle;
use Nyholm\BundleTest\TestKernel;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class BundleInitializationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @param array<string, string> $options
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

    public function testInitBundle(): void
    {
        $kernel = self::bootKernel();

        $container = $kernel->getContainer();

        $services = [
            'test.headsnet_domain_events.event_subscriber.publisher' => PublishDomainEventSubscriber::class,
            'test.headsnet_domain_events.event_subscriber.persister' => PersistDomainEventSubscriber::class,
            'test.headsnet_domain_events.repository.event_store_doctrine' => DoctrineEventStore::class,
        ];

        foreach ($services as $id => $class) {
            $this->assertTrue($container->has($id));
            $s = $container->get($id);
            $this->assertInstanceOf($class, $s);
        }
    }
}
