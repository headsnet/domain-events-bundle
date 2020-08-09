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
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class HeadsnetDomainEventsExtension implements ExtensionInterface, PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    public function getNamespace()
    {
        // TODO: Implement getNamespace() method.
    }

    public function getXsdValidationBasePath()
    {
        // TODO: Implement getXsdValidationBasePath() method.
    }

    public function getAlias(): string
    {
        return 'headsnet_domain_events';
    }

    public function prepend(ContainerBuilder $container): void
    {
        $config = array(
            'dbal' => [
                'types' => [
                    'datetime_immutable_microseconds' => DateTimeImmutableMicrosecondsType::class
                ]
            ]
        );

        $container->prependExtensionConfig('doctrine', $config);
    }
}
