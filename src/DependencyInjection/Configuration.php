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

namespace Headsnet\DomainEventsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('headsnet_domain_events');

        // @see https://github.com/phpstan/phpstan/issues/844
        // @phpstan-ignore-next-line
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('message_bus')
                    ->children()
                        ->scalarNode('name')
                            ->defaultValue('messenger.bus.event')
                        ->end()
                    ->end()
                ->end() // message_bus
                ->arrayNode('persistence')
                    ->children()
                        ->scalarNode('table_name')
                        ->end()
                    ->end()
                ->end() // persistence
                ->arrayNode('legacy_map')
                    ->normalizeKeys(false)
                    ->scalarPrototype()->end()
                ->end() // legacy_map
            ->end()
        ;

        return $treeBuilder;
    }
}
