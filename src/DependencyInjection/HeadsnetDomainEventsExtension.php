<?php
/*
 * This file is part of the Symfony HeadsnetDomainEventsBundle.
 *
 * (c) Headstrong Internet Services Ltd 2020
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Headsnet\DomainEventsBundle\DependencyInjection;

use Headsnet\DomainEventsBundle\Doctrine\DBAL\Types\DateTimeImmutableMicrosecondsType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class HeadsnetDomainEventsExtension extends Extension implements PrependExtensionInterface
{
    private const DBAL_MICROSECONDS_TYPE = 'datetime_immutable_microseconds';

    /**
     * @param array<string, array> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('headsnet_domain_events.legacy_map', $config['legacy_map']);

        $this->useCustomMessageBusIfSpecified($config, $container);
    }

    /**
     * @param array<string, array> $config
     */
    private function useCustomMessageBusIfSpecified(array $config, ContainerBuilder $container): void
    {
        if (isset($config['message_bus']['name'])) {
            $definition = $container->getDefinition('headsnet_domain_events.domain_event_dispatcher_service');
            $definition->replaceArgument(0, new Reference($config['message_bus']['name']));
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->addCustomDBALType($container);
    }

    private function addCustomDBALType(ContainerBuilder $container): void
    {
        $config = [
            'dbal' => [
                'types' => [
                    self::DBAL_MICROSECONDS_TYPE => DateTimeImmutableMicrosecondsType::class,
                ],
            ],
        ];

        $container->prependExtensionConfig('doctrine', $config);
    }
}
