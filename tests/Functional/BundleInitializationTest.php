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
use Nyholm\BundleTest\BaseBundleTestCase;
use Nyholm\BundleTest\CompilerPass\PublicServicePass;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

class BundleInitializationTest extends BaseBundleTestCase
{
    protected function getBundleClass()
    {
        return HeadsnetDomainEventsBundle::class;
    }

    public function test_services_are_instantiated_ok(): void
    {
        $this->bootCustomKernel();
        $container = $this->getContainer();

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

    private function bootCustomKernel(): void
    {
        $kernel = $this->createKernel();

        $kernel->addConfigFile(__DIR__.'/config.yml');

        $this->addCompilerPass(new PublicServicePass('|headsnet_domain_events.*|'));

        $kernel->addBundle(FrameworkBundle::class);
        $kernel->addBundle(DoctrineBundle::class);

        $this->bootKernel();
    }
}
